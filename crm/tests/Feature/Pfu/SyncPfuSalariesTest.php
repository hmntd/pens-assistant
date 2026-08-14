<?php

namespace Tests\Feature\Pfu;

use App\Events\PfuSalariesSynced;
use App\Models\AuditLog;
use App\Services\PfuSalaryScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SyncPfuSalariesTest extends TestCase
{
    use RefreshDatabase;

    public function test_extract_year_urls_finds_target_year_links(): void
    {
        $service = new PfuSalaryScraperService();

        $sampleHtml = <<<HTML
        <div>
            <a href="https://www.pfu.gov.ua/2179262-pokaznyk-serednoyi-zarobitnoyi-platy-za-2026-rik/" title=""><span>2026 </span></a>
            <a href="https://www.pfu.gov.ua/1987654-pokaznyk-serednoyi-zarobitnoyi-platy-za-2025-rik/" title=""><span>2025 </span></a>
            <a href="https://www.pfu.gov.ua/1800000-pokaznyk-serednoyi-zarobitnoyi-platy-za-2024-rik/" title=""><span>2024 </span></a>
        </div>
        HTML;

        $urls = $service->extractYearUrls($sampleHtml, [2026, 2025]);

        $this->assertArrayHasKey(2026, $urls);
        $this->assertArrayHasKey(2025, $urls);
        $this->assertEquals('https://www.pfu.gov.ua/2179262-pokaznyk-serednoyi-zarobitnoyi-platy-za-2026-rik/', $urls[2026][0]);
        $this->assertEquals('https://www.pfu.gov.ua/1987654-pokaznyk-serednoyi-zarobitnoyi-platy-za-2025-rik/', $urls[2025][0]);
    }

    public function test_salary_text_parser_converts_ukrainian_formats(): void
    {
        $service = new PfuSalaryScraperService();

        $this->assertEquals(21876.06, $service->parseSalaryText('21 876 грн 06 коп.'));
        $this->assertEquals(16849.15, $service->parseSalaryText('16 849 грн 15 коп.'));
        $this->assertEquals(18200.50, $service->parseSalaryText('18 200,50 грн'));
    }

    public function test_month_name_parser(): void
    {
        $service = new PfuSalaryScraperService();

        $this->assertEquals(1, $service->parseMonthNameToNumber('Січень'));
        $this->assertEquals(5, $service->parseMonthNameToNumber('Травень'));
        $this->assertEquals(12, $service->parseMonthNameToNumber('Грудень'));
        $this->assertEquals(8, $service->parseMonthNameToNumber('August'));
    }

    public function test_artisan_sync_pfu_salaries_command_dispatches_event_and_logs_audit(): void
    {
        $this->artisan('pfu:sync-salaries')
            ->expectsOutputToContain('Starting PFU Average Salaries Scraper...')
            ->assertExitCode(0);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pfu_average_salaries_synced',
            'entity_type' => 'PfuAverageSalary',
        ]);
    }

    public function test_pfu_salaries_synced_event_dispatched(): void
    {
        Event::fake([PfuSalariesSynced::class]);

        $service = new PfuSalaryScraperService();
        $service->scrapeAndSync();

        Event::assertDispatched(PfuSalariesSynced::class);
    }
}
