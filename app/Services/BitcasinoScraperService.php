<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Provider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Exception;
use Illuminate\Support\Str;

class BitcasinoScraperService
{
    protected array $headers = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language' => 'tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
        'Connection' => 'keep-alive',
        'Upgrade-Insecure-Requests' => '1',
    ];

    protected string $baseUrl = 'https://bitcasino.io';
    protected string $gamesListUrl = 'https://bitcasino.io/tr/games';
    protected RtpMonitorService $rtpMonitor;
    protected int $delayBetweenRequests = 2000000; // 2 seconds

    public function __construct(RtpMonitorService $rtpMonitor)
    {
        $this->rtpMonitor = $rtpMonitor;
    }

    public function scrapeAll(): array
    {
        try {
            Log::info('Starting Bitcasino scraping process');

            $gameUrls = $this->scrapeGameUrls();
            Log::info('Found ' . count($gameUrls) . ' game URLs');

            $games = [];
            $errors = [];

            foreach ($gameUrls as $gameUrl) {
                try {
                    $gameData = $this->scrapeGameDetails($gameUrl);
                    if ($gameData) {
                        $games[] = $gameData;
                    }
                    usleep($this->delayBetweenRequests);
                } catch (Exception $e) {
                    Log::error("Error scraping game URL: {$gameUrl}", [
                        'error' => $e->getMessage()
                    ]);
                    $errors[] = [
                        'url' => $gameUrl,
                        'error' => $e->getMessage()
                    ];
                }
            }

            if (!empty($games)) {
                $this->updateDatabase($games);
                Log::info("Successfully updated database with " . count($games) . " games");
            }

            return [
                'success' => [
                    'games' => $games,
                    'changes' => $this->rtpMonitor->checkForChanges($games)
                ],
                'errors' => $errors
            ];

        } catch (Exception $e) {
            Log::error("Error in scraping process: " . $e->getMessage());
            return [
                'success' => [],
                'errors' => ['main' => $e->getMessage()]
            ];
        }
    }

    protected function scrapeGameUrls(): array
    {
        $urls = [];
        try {
            $response = Http::withHeaders($this->headers)
                ->get($this->gamesListUrl);

            if (!$response->successful()) {
                throw new Exception("Failed to fetch games list: " . $response->status());
            }

            $crawler = new Crawler($response->body());

            // Find all game links
            $crawler->filter('a[href^="/tr/play/"]')->each(function (Crawler $node) use (&$urls) {
                $href = $node->attr('href');
                if ($href) {
                    $urls[] = $this->baseUrl . $href;
                }
            });

        } catch (Exception $e) {
            Log::error("Error scraping game URLs: " . $e->getMessage());
            throw $e;
        }

        return array_unique($urls);
    }

    protected function scrapeGameDetails(string $url): ?array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get($url);

            if (!$response->successful()) {
                throw new Exception("Failed to fetch game details: " . $response->status());
            }

            $crawler = new Crawler($response->body());

            // Extract game details
            $name = $this->extractGameName($crawler);
            $provider = $this->extractProvider($crawler);
            $rtpData = $this->extractRtpData($crawler);
            $info = $this->extractGameInfo($crawler);

            return [
                'name' => $name,
                'provider' => $provider,
                'current_rtp' => $rtpData['current'] ?? 0,
                'daily_rtp' => $rtpData['24h'] ?? 0,
                'weekly_rtp' => $rtpData['week'] ?? 0,
                'monthly_rtp' => $rtpData['month'] ?? 0,
                'theoretical_rtp' => $rtpData['general'] ?? 0,
                'risk_level' => $info['risk'] ?? '',
                'paylines' => $info['paylines'] ?? '',
                'category' => $this->extractCategory($url),
                'last_updated' => now()
            ];

        } catch (Exception $e) {
            Log::error("Error scraping game details from URL {$url}: " . $e->getMessage());
            return null;
        }
    }

    protected function extractGameName(Crawler $crawler): string
    {
        try {
            // Find game name from the h1 element inside the header section
            return trim($crawler->filter('h1.font-headline.text-moon-18')->text(''));
        } catch (Exception $e) {
            Log::warning("Could not extract game name: " . $e->getMessage());
            return '';
        }
    }

    protected function extractProvider(Crawler $crawler): string
    {
        try {
            // Find provider name from the p element with text-moon-10-caption class
            return trim($crawler->filter('p.text-moon-10-caption')->text(''));
        } catch (Exception $e) {
            Log::warning("Could not extract provider name: " . $e->getMessage());
            return '';
        }
    }

    protected function extractRtpData(Crawler $crawler): array
    {
        try {
            $rtpData = [];

            // Extract 24h RTP
            $crawler->filter('.grid-cols-3 > div')->each(function (Crawler $node, $i) use (&$rtpData) {
                $label = strtolower(trim($node->filter('span[data-translation]')->text('')));
                $value = trim($node->filter('span.font-medium')->text(''));

                if (strpos($label, '24') !== false) {
                    $rtpData['24h'] = $this->parseRtpValue($value);
                } elseif (strpos($label, 'hafta') !== false) {
                    $rtpData['week'] = $this->parseRtpValue($value);
                } elseif (strpos($label, 'ay') !== false) {
                    $rtpData['month'] = $this->parseRtpValue($value);
                }
            });

            // Extract general RTP
            $crawler->filter('.grid.grid-flow-col')->each(function (Crawler $node) use (&$rtpData) {
                $label = strtolower(trim($node->filter('p.text-moon-16.text-trunks')->text('')));
                if (strpos($label, 'rtp') !== false) {
                    $rtpData['general'] = $this->parseRtpValue($node->filter('p.text-moon-16.text-bulma')->text(''));
                }
            });

            return $rtpData;
        } catch (Exception $e) {
            Log::warning("Could not extract RTP data: " . $e->getMessage());
            return [];
        }
    }

    protected function extractGameInfo(Crawler $crawler): array
    {
        try {
            $info = [
                'risk' => '',
                'paylines' => '',
                'hit_ratio' => '',
                'min_bet' => '',
                'max_bet' => ''
            ];

            $crawler->filter('.grid.grid-flow-col')->each(function (Crawler $node) use (&$info) {
                $label = strtolower(trim($node->filter('p.text-moon-16.text-trunks')->text('')));
                $value = trim($node->filter('p.text-moon-16.text-bulma')->text(''));

                if (strpos($label, 'risk') !== false) {
                    $info['risk'] = $value;
                } elseif (strpos($label, 'ödeme çizgileri') !== false) {
                    $info['paylines'] = $value;
                } elseif (strpos($label, 'isabet') !== false) {
                    $info['hit_ratio'] = $this->parseRtpValue($value);
                } elseif (strpos($label, 'min - maks') !== false) {
                    preg_match('/(\d+\.?\d*)\s*-\s*(\d+\.?\d*)/i', $value, $matches);
                    if (count($matches) >= 3) {
                        $info['min_bet'] = $matches[1];
                        $info['max_bet'] = $matches[2];
                    }
                }
            });

            return $info;
        } catch (Exception $e) {
            Log::warning("Could not extract game info: " . $e->getMessage());
            return [];
        }
    }

    protected function parseRtpValue(string $text): float
    {
        $text = preg_replace('/[^0-9.]/', '', $text);
        return !empty($text) ? (float) $text : 0.0;
    }

    protected function updateDatabase(array $games): void
    {
        foreach ($games as $gameData) {
            try {
                // Create or get provider
                $provider = Provider::firstOrCreate(
                    ['name' => $gameData['provider']],
                    ['slug' => Str::slug($gameData['provider'])]
                );

                // Update or create game
                Game::updateOrCreate(
                    [
                        'name' => $gameData['name'],
                        'provider_id' => $provider->id
                    ],
                    [
                        'slug' => Str::slug($gameData['name']),
                        'current_rtp' => $gameData['current_rtp'],
                        'daily_rtp' => $gameData['daily_rtp'],
                        'theoretical_rtp' => $gameData['theoretical_rtp'],
                        'category' => $gameData['category'],
                        'last_updated' => $gameData['last_updated']
                    ]
                );

            } catch (Exception $e) {
                Log::error("Failed to update game: {$gameData['name']}", [
                    'error' => $e->getMessage(),
                    'data' => $gameData
                ]);
            }
        }
    }
}
