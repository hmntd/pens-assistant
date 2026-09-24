<?php

namespace App\Console\Commands;

use App\Services\SubsistenceMinimumScraperService;
use Illuminate\Console\Command;

class SyncSubsistenceMinimumsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pfu:sync-subsistence-minimums';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape official statutory annual subsistence minimums and sync to calc_db';

    /**
     * Execute the console command.
     */
    public function handle(SubsistenceMinimumScraperService $service): int
    {
        $this->info('Starting Subsistence Minimums Sync Process...');

        $result = $service->scrapeAndSync();

        $this->info("Successfully synced {$result['synced_count']} annual subsistence minimum records.");
        foreach ($result['records'] as $year => $vals) {
            $this->line(" - {$year}: Disabled Persons = {$vals['for_disabled_persons']} UAH, General = {$vals['general_minimum']} UAH");
        }

        return Command::SUCCESS;
    }
}
