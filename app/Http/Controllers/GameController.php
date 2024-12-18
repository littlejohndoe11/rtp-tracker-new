<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Provider;
use App\Services\RtpScraperService;
use Illuminate\Http\Request;

class GameController extends Controller
{
    protected $rtpScraper;

    public function __construct(RtpScraperService $rtpScraper)
    {
        $this->rtpScraper = $rtpScraper;
    }

    public function index(Request $request)
    {
        $query = Game::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhereHas('provider', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
        }

        $games = $query->with('provider')
            ->orderBy('current_rtp', 'desc')  // Order by RTP
            ->get();

        // Get unique providers
        $providers = Provider::orderBy('name')->pluck('name');

        // Get unique categories (split and flatten the array)
        $categories = Game::pluck('category')
            ->flatMap(function ($categories) {
                return array_map('trim', explode(',', $categories));
            })
            ->unique()
            ->values();

        return view('games.index', compact('games', 'providers', 'categories'));
    }

    public function scrapeRTP()
    {
        try {
            $scrapeResult = $this->rtpScraper->scrapeAll();

            Log::info('Scrape Results:', [
                'success_count' => count($scrapeResult['success']['games'] ?? []),
                'error_count' => count($scrapeResult['errors'] ?? []),
                'first_game' => $scrapeResult['success']['games'][0] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data processed successfully',
                'scrape_results' => $scrapeResult
            ]);

        } catch (\Exception $e) {
            Log::error('Scrape failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
