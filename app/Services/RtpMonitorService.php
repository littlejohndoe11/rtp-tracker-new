<?php
namespace App\Services;

use App\Models\Game;
use App\Models\RtpChange;
use Illuminate\Support\Facades\Log;

class RtpMonitorService
{
    protected float $significantChangeThreshold = 1.0; // 1% change threshold

    public function checkForChanges(array $newData): array
    {
        $changes = [];
        $significantChanges = [];

        foreach ($newData as $gameData) {
            try {
                $game = Game::where('name', $gameData['name'])
                    ->where('provider_id', function ($query) use ($gameData) {
                        $query->select('id')
                            ->from('providers')
                            ->where('name', $gameData['provider']);
                    })
                    ->first();

                if (!$game) {
                    continue;
                }

                // Check for RTP changes
                $rtpChanged = abs($game->current_rtp - $gameData['current_rtp']) > 0.01;
                $dailyRtpChanged = abs($game->daily_rtp - $gameData['daily_rtp']) > 0.01;
                $weeklyRtpChanged = abs($game->weekly_rtp - $gameData['weekly_rtp']) > 0.01;
                $monthlyRtpChanged = abs($game->monthly_rtp - $gameData['monthly_rtp']) > 0.01;

                if ($rtpChanged || $dailyRtpChanged || $weeklyRtpChanged || $monthlyRtpChanged) {
                    // Calculate change percentage
                    $changePercentage = $rtpChanged ?
                        (($gameData['current_rtp'] - $game->current_rtp) / $game->current_rtp) * 100 : 0;

                    // Record the change
                    $change = RtpChange::create([
                        'game_id' => $game->id,
                        'old_rtp' => $game->current_rtp,
                        'new_rtp' => $gameData['current_rtp'],
                        'old_daily_rtp' => $game->daily_rtp,
                        'new_daily_rtp' => $gameData['daily_rtp'],
                        'change_percentage' => $changePercentage,
                        'detected_at' => now(),
                    ]);

                    $changes[] = $change;

                    if (abs($changePercentage) >= $this->significantChangeThreshold) {
                        $significantChanges[] = [
                            'game' => $game,
                            'change' => $change,
                        ];
                    }
                }

                // Update game with new info
                $game->update([
                    'current_rtp' => $gameData['current_rtp'],
                    'daily_rtp' => $gameData['daily_rtp'],
                    'weekly_rtp' => $gameData['weekly_rtp'],
                    'monthly_rtp' => $gameData['monthly_rtp'],
                    'risk_level' => $gameData['risk_level'] ?? null,
                    'paylines' => $gameData['paylines'] ?? null,
                    'last_updated' => now()
                ]);

            } catch (\Exception $e) {
                Log::error("Error monitoring RTP changes for game: {$gameData['name']}", [
                    'error' => $e->getMessage(),
                    'data' => $gameData
                ]);
            }
        }

        return [
            'total_changes' => count($changes),
            'significant_changes' => count($significantChanges),
            'changes' => $changes,
        ];
    }
}
