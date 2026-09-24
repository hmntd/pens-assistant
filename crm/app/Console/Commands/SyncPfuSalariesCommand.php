<?php

namespace App\Console\Commands;

use App\Services\PfuSalaryScraperService;
use Illuminate\Console\Command;

class SyncPfuSalariesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pfu:sync-salaries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape official PFU average salaries for current and previous years and sync to Calc DB via gRPC';

    /**
     * Execute the console command.
     */
    public function handle(PfuSalaryScraperService $scraperService): int
    {
        $this->info('Starting PFU Average Salaries Scraper...');

        $result = $scraperService->scrapeAndSync();

        $this->info("Successfully processed {$result['processed']} average salary records.");
        foreach ($result['records'] as $rec) {
            $this->line(" - {$rec['year']}-{$rec['month']}: {$rec['amount']} UAH");
        }

        return Command::SUCCESS;
    }
}
