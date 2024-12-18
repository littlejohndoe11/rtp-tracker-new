<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Provider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class RtpScraperService
{
    protected string $baseUrl = 'https://bitcasino.io';
    protected string $gamesListUrl = 'https://bitcasino.io/tr/games';
    protected RtpMonitorService $rtpMonitor;
    protected int $maxRetries = 3;
    protected int $minDelay = 10;  // Increased minimum delay
    protected int $maxDelay = 15;  // Increased maximum delay
    protected array $headers;
    protected int $backoffDelay = 60; // Increased backoff delay
    protected int $maxConsecutive403s = 2;
    protected int $consecutive403s = 0;
    protected int $maxPages = 122;

    protected array $acceptLanguages = [
        'tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
        'en-US,en;q=0.9,tr;q=0.8',
        'tr;q=0.9,en-US;q=0.8,en;q=0.7'
    ];

    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0'
    ];

    protected array $referers = [
        'https://bitcasino.io/',
        'https://bitcasino.io/tr',
        'https://bitcasino.io/tr/games',
        'https://www.google.com/',
    ];

    public function __construct(RtpMonitorService $rtpMonitor)
    {
        $this->rtpMonitor = $rtpMonitor;
        $this->initializeHeaders();
    }

    protected function initializeHeaders(): void
    {
        $this->rotateHeaders();
    }

    protected function rotateHeaders(): void
    {
        $this->headers = [
            'User-Agent' => $this->userAgents[array_rand($this->userAgents)],
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => $this->acceptLanguages[array_rand($this->acceptLanguages)],
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Referer' => $this->referers[array_rand($this->referers)],
            'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'Cache-Control' => 'max-age=0'
        ];
    }

    protected function randomDelay(): void
    {
        // Add a more random delay pattern
        $baseDelay = rand($this->minDelay * 1000000, $this->maxDelay * 1000000);
        $randomExtra = rand(0, 2000000); // Add 0-2 seconds random extra
        usleep($baseDelay + $randomExtra);
    }

    protected function refreshSession(): bool
    {
        try {
            Log::info("Refreshing session...");
            $this->rotateHeaders();

            $response = Http::withHeaders($this->headers)
                ->withoutVerifying()
                ->get($this->baseUrl);

            if ($response->successful()) {
                // Convert cookie objects to array format expected by HTTP client
                $this->cookies = collect($response->cookies())->mapWithKeys(function ($cookie) {
                    return [$cookie->getName() => $cookie->getValue()];
                })->all();

                Cache::put('scraper_session', [
                    'cookies' => $this->cookies,
                    'headers' => $this->headers
                ], now()->addSeconds($this->sessionLifetime));

                $this->consecutiveErrors = 0;
                sleep(rand($this->minDelay, $this->maxDelay));
                return true;
            }
        } catch (Exception $e) {
            Log::error("Session refresh failed: " . $e->getMessage());
        }
        return false;
    }

    protected function makeRequest(string $url, int $attempt = 1): string
    {
        if ($attempt > $this->maxRetries) {
            throw new Exception("Max retries exceeded for URL: $url");
        }

        try {
            // If we've hit too many 403s, take a longer break
            if ($this->consecutive403s >= $this->maxConsecutive403s) {
                $backoffTime = $this->backoffDelay * ($this->consecutive403s - $this->maxConsecutive403s + 1);
                Log::warning("Too many 403s, backing off for {$backoffTime} seconds...");
                sleep($backoffTime);
                $this->consecutive403s = 0;
                $this->rotateHeaders();
            }

            Log::info("Attempting request to: $url (Attempt $attempt)");

            $response = Http::withHeaders($this->headers)
                ->withoutVerifying()
                ->get($url);

            if ($response->status() === 403) {
                $this->consecutive403s++;
                throw new Exception("403 Forbidden - Access Denied");
            }

            if ($response->successful()) {
                $this->consecutive403s = 0;
                $this->randomDelay();
                return $response->body();
            }

            throw new Exception("Failed to fetch: " . $response->status());

        } catch (Exception $e) {
            Log::warning("Request attempt $attempt failed: " . $e->getMessage());

            if ($attempt < $this->maxRetries) {
                if (strpos($e->getMessage(), '403') !== false) {
                    $this->backoffDelay *= 2; // Double the backoff time
                    Log::info("403 received, increasing backoff to {$this->backoffDelay} seconds");
                }

                $delay = $e->getMessage() === "403 Forbidden - Access Denied"
                    ? $this->backoffDelay
                    : rand($this->minDelay, $this->maxDelay);

                Log::info("Waiting {$delay} seconds before retry...");
                sleep($delay);
                $this->rotateHeaders();
                return $this->makeRequest($url, $attempt + 1);
            }

            throw $e;
        }
    }

    public function scrapeAll(): array
    {
        ini_set('memory_limit', '256M');
        $totalGames = 0;
        $errors = [];
        $lastProcessedPage = 0;

        try {
            Log::info('Starting batch processing of games');

            for ($page = 1; $page <= $this->maxPages; $page++) {
                Log::info("Processing page $page");

                try {
                    $pageUrls = [];
                    $pageUrl = $page === 1 ? $this->gamesListUrl : $this->gamesListUrl . "?page=" . $page;

                    $html = $this->makeRequest($pageUrl);
                    $crawler = new Crawler($html);

                    // Try to find game links
                    $gameLinks = $crawler->filter('a[href*="/tr/play/"]')->each(function (Crawler $node) {
                        $href = $node->attr('href');
                        if ($href && !Str::startsWith($href, ['http://', 'https://'])) {
                            return $this->baseUrl . $href;
                        }
                        return $href;
                    });

                    $pageUrls = array_filter($gameLinks);
                    $crawler->clear();
                    unset($crawler);

                    if (empty($pageUrls)) {
                        Log::info("No games found on page $page, stopping pagination");
                        break;
                    }

                    Log::info("Found " . count($pageUrls) . " games on page $page");

                    foreach ($pageUrls as $url) {
                        try {
                            $gameData = $this->scrapeGameDetails($url);
                            if ($gameData) {
                                $this->updateGame($gameData);
                                $totalGames++;
                                Log::info("Successfully processed game: " . $gameData['name']);
                            }
                            $this->randomDelay();
                        } catch (Exception $e) {
                            if (strpos($e->getMessage(), '403') !== false) {
                                Log::warning("Got 403, taking a break...");
                                sleep($this->backoffDelay);
                                $page--; // Retry this page
                                break;
                            }

                            $errors[] = [
                                'url' => $url,
                                'error' => $e->getMessage()
                            ];
                        }
                    }

                    $lastProcessedPage = $page;
                    Log::info("Completed processing page $page. Total games: $totalGames");

                    // Take a longer break between pages
                    sleep(rand(15, 25));

                } catch (Exception $e) {
                    if (strpos($e->getMessage(), '403') !== false) {
                        Log::warning("Page processing stopped at page $page due to 403 error");
                        sleep($this->backoffDelay * 2);
                        $page--; // Retry this page
                        continue;
                    }

                    $errors[] = [
                        'page' => $page,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return [
                'success' => [
                    'total_games' => $totalGames,
                    'last_processed_page' => $lastProcessedPage
                ],
                'errors' => $errors
            ];

        } catch (Exception $e) {
            Log::error("Error in scraping process: " . $e->getMessage());
            return [
                'success' => [
                    'total_games' => $totalGames,
                    'last_processed_page' => $lastProcessedPage
                ],
                'errors' => ['main' => $e->getMessage()]
            ];
        }
    }

    protected function scrapeGameUrls(): array
    {
        $allUrls = [];

        try {
            for ($page = 1; $page <= $this->maxPages; $page++) {
                $pageUrl = $page === 1 ? $this->gamesListUrl : $this->gamesListUrl . "?page=" . $page;
                Log::info("Scraping page $page: $pageUrl");

                try {
                    $html = $this->makeRequest($pageUrl);
                    $crawler = new Crawler($html);

                    // Try different selectors that might contain game links
                    $gameLinks = $crawler->filter('a[href*="/tr/play/"]');

                    if ($gameLinks->count() === 0) {
                        Log::warning("No game links found on page $page using primary selector, trying alternative...");
                        $gameLinks = $crawler->filter('a[href*="/play/"]');
                    }

                    if ($gameLinks->count() === 0) {
                        Log::warning("No games found on page $page, stopping pagination");
                        break;
                    }

                    $pageUrls = [];
                    $gameLinks->each(function (Crawler $node) use (&$pageUrls) {
                        $href = $node->attr('href');
                        if ($href) {
                            // Ensure the URL is absolute
                            if (!Str::startsWith($href, ['http://', 'https://'])) {
                                $href = $this->baseUrl . $href;
                            }
                            $pageUrls[] = $href;
                        }
                    });

                    $uniquePageUrls = array_unique($pageUrls);
                    $allUrls = array_merge($allUrls, $uniquePageUrls);

                    Log::info("Found " . count($uniquePageUrls) . " unique games on page $page. Total: " . count($allUrls));

                    // Add delay between pages
                    if ($page < $this->maxPages) {
                        $delay = rand($this->minDelay, $this->maxDelay);
                        Log::info("Waiting $delay seconds before next page...");
                        sleep($delay);
                    }

                } catch (Exception $e) {
                    Log::error("Error processing page $page: " . $e->getMessage());
                    // Continue to next page instead of breaking completely
                    continue;
                }
            }

        } catch (Exception $e) {
            Log::error("Error in scrapeGameUrls: " . $e->getMessage());
            throw $e;
        }

        return array_unique($allUrls);
    }

    protected function processGames(array $gameUrls): array
    {
        $games = [];
        $errors = [];

        foreach ($gameUrls as $url) {
            try {
                $gameData = $this->scrapeGameDetails($url);
                if ($gameData) {
                    $games[] = $gameData;
                }
            } catch (Exception $e) {
                Log::error("Error processing game URL $url: " . $e->getMessage());
                $errors[] = [
                    'url' => $url,
                    'error' => $e->getMessage()
                ];
            }

            // Add small delay between game requests
            usleep(500000); // 0.5 seconds
        }

        return [
            'games' => $games,
            'errors' => $errors
        ];
    }

    protected function scrapeGameDetails(string $url): ?array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->withoutVerifying()
                ->get($url);

            if (!$response->successful()) {
                throw new Exception("Failed to fetch game details: " . $response->status());
            }

            $crawler = new Crawler($response->body());

            $name = $crawler->filter('h1.font-headline.text-moon-18')->text('');
            $provider = $crawler->filter('p.text-moon-10-caption')->text('');
            $rtpData = $this->extractRtpData($crawler);

            // Clear crawler to free memory
            $crawler->clear();
            unset($crawler);

            return [
                'name' => trim($name),
                'provider' => trim($provider),
                'category' => $this->extractCategory($url),
                'current_rtp' => $rtpData['current_rtp'] ?? 0,
                'daily_rtp' => $rtpData['daily_rtp'] ?? 0,
                'weekly_rtp' => $rtpData['weekly_rtp'] ?? 0,
                'monthly_rtp' => $rtpData['monthly_rtp'] ?? 0,
                'risk_level' => $rtpData['risk_level'] ?? null,
                'paylines' => $rtpData['paylines'] ?? null,
                'last_updated' => now()
            ];

        } catch (Exception $e) {
            Log::error("Error scraping game details from URL {$url}: " . $e->getMessage());
            return null;
        }
    }

    protected function extractRtpData(Crawler $crawler): array
    {
        try {
            $rtpData = [];

            // Extract RTP values
            $crawler->filter('.grid-cols-3 > div')->each(function (Crawler $node) use (&$rtpData) {
                $label = strtolower(trim($node->filter('span[data-translation]')->text('')));
                $value = trim($node->filter('span.font-medium')->text(''));

                if (strpos($label, '24') !== false) {
                    $rtpData['daily_rtp'] = $this->parseRtpValue($value);
                } elseif (strpos($label, 'hafta') !== false) {
                    $rtpData['weekly_rtp'] = $this->parseRtpValue($value);
                } elseif (strpos($label, 'ay') !== false) {
                    $rtpData['monthly_rtp'] = $this->parseRtpValue($value);
                }
            });

            // Extract general RTP and other data
            $crawler->filter('.grid.grid-flow-col')->each(function (Crawler $node) use (&$rtpData) {
                $label = strtolower(trim($node->filter('p.text-moon-16.text-trunks')->text('')));
                $value = trim($node->filter('p.text-moon-16.text-bulma')->text(''));

                if (strpos($label, 'rtp') !== false) {
                    $rtpData['current_rtp'] = $this->parseRtpValue($value);
                } elseif (strpos($label, 'risk') !== false) {
                    $rtpData['risk_level'] = $value;
                } elseif (strpos($label, 'ödeme çizgileri') !== false) {
                    $rtpData['paylines'] = $value;
                }
            });

            return $rtpData;
        } catch (Exception $e) {
            Log::warning("Could not extract RTP data: " . $e->getMessage());
            return [];
        }
    }

    protected function extractCategory(string $url): string
    {
        $parts = explode('/', parse_url($url, PHP_URL_PATH));
        foreach ($parts as $part) {
            if (strpos($part, 'slots') !== false || strpos($part, 'casino') !== false) {
                return $part;
            }
        }
        return 'other';
    }

    protected function parseRtpValue(string $text): float
    {
        $text = preg_replace('/[^0-9.]/', '', $text);
        return !empty($text) ? (float) $text : 0.0;
    }
    protected function updateGame(array $gameData): void
    {
        try {
            $provider = Provider::firstOrCreate(
                ['name' => $gameData['provider']],
                ['slug' => Str::slug($gameData['provider'])]
            );

            $slug = Str::slug($gameData['name']);
            $baseSlug = $slug;
            $counter = 1;

            // Handle duplicate slugs
            while (Game::where('slug', $slug)
                ->where('provider_id', '!=', $provider->id)
                ->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            Game::updateOrCreate(
                [
                    'name' => $gameData['name'],
                    'provider_id' => $provider->id
                ],
                [
                    'slug' => $slug,
                    'category' => $gameData['category'],
                    'current_rtp' => $gameData['current_rtp'],
                    'daily_rtp' => $gameData['daily_rtp'],
                    'weekly_rtp' => $gameData['weekly_rtp'],
                    'monthly_rtp' => $gameData['monthly_rtp'],
                    'risk_level' => $gameData['risk_level'],
                    'paylines' => $gameData['paylines'],
                    'last_updated' => $gameData['last_updated']
                ]
            );

        } catch (QueryException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry error
                Log::warning("Duplicate entry detected for game: {$gameData['name']}. Attempting to resolve...");
                // You might want to add additional handling here
                throw new Exception("Duplicate game entry: {$gameData['name']}");
            }
            throw $e;
        }
    }

    protected function updateDatabase(array $games): void
    {
        foreach ($games as $gameData) {
            try {
                $provider = Provider::firstOrCreate(
                    ['name' => $gameData['provider']],
                    ['slug' => Str::slug($gameData['provider'])]
                );

                Game::updateOrCreate(
                    [
                        'name' => $gameData['name'],
                        'provider_id' => $provider->id
                    ],
                    [
                        'slug' => Str::slug($gameData['name']),
                        'category' => $gameData['category'],
                        'current_rtp' => $gameData['current_rtp'],
                        'daily_rtp' => $gameData['daily_rtp'],
                        'weekly_rtp' => $gameData['weekly_rtp'],
                        'monthly_rtp' => $gameData['monthly_rtp'],
                        'risk_level' => $gameData['risk_level'],
                        'paylines' => $gameData['paylines'],
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
