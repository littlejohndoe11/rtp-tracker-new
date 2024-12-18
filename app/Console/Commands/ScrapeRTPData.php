<?php

namespace App\Console\Commands;

use App\Services\RtpScraperService;
use Illuminate\Console\Command;

class ScrapeRTPData extends Command
{
    protected $signature = 'rtp:scrape';
    protected $description = 'Scrape RTP data from casino websites';

    protected RtpScraperService $scraper;

    public function __construct(RtpScraperService $scraper)
    {
        parent::__construct();
        $this->scraper = $scraper;
    }

    public function handle(): void
    {
        try {
            $this->info('Starting RTP data scraping...');

            $result = $this->scraper->scrapeAll();

            if (!empty($result['success']['games'])) {
                $this->info("Successfully scraped " . count($result['success']['games']) . " games");

                foreach ($result['success']['games'] as $game) {
                    $this->line("- {$game['name']} ({$game['provider']})");
                }
            }

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->error("Error: " . ($error['error'] ?? 'Unknown error'));
                }
            }

            $this->info('RTP data scraping completed.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
