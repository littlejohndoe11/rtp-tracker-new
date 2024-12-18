<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RtpChange extends Model
{
    protected $fillable = [
        'game_id',
        'old_rtp',
        'new_rtp',
        'old_daily_rtp',
        'new_daily_rtp',
        'old_weekly_rtp',
        'new_weekly_rtp',
        'old_monthly_rtp',
        'new_monthly_rtp',
        'change_percentage',
        'detected_at'
    ];

    protected $casts = [
        'old_rtp' => 'float',
        'new_rtp' => 'float',
        'old_daily_rtp' => 'float',
        'new_daily_rtp' => 'float',
        'old_weekly_rtp' => 'float',
        'new_weekly_rtp' => 'float',
        'old_monthly_rtp' => 'float',
        'new_monthly_rtp' => 'float',
        'change_percentage' => 'float',
        'detected_at' => 'datetime'
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
