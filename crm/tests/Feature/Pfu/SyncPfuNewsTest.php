<?php

namespace Tests\Feature\Pfu;

use App\Console\Commands\SyncPfuNewsCommand;
use App\Models\PfuNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncPfuNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_pfu_news_command_scrapes_and_stores_top_3_items()
    {
        $sampleHtml = <<<HTML
        <div class="item-block-news">
            <a href="https://www.pfu.gov.ua/kr/1" class="active-news">
                <div class="title-block-news">Новина 1 про пенсії</div>
                <div class="data-block-news">18 Серпня 2026</div>
            </a>
            <a href="https://www.pfu.gov.ua/kr/2" class="">
                <div class="title-block-news">Новина 2 про субсидії</div>
                <div class="data-block-news">17 Серпня 2026</div>
            </a>
            <a href="https://www.pfu.gov.ua/kr/3" class="">
                <div class="title-block-news">Новина 3 про виплати</div>
                <div class="data-block-news">16 Серпня 2026</div>
            </a>
            <a href="https://www.pfu.gov.ua/kr/4" class="">
                <div class="title-block-news">Новина 4 зайва</div>
                <div class="data-block-news">15 Серпня 2026</div>
            </a>
        </div>
        HTML;

        Http::fake([
            SyncPfuNewsCommand::PFU_NEWS_URL => Http::response($sampleHtml, 200),
        ]);

        $this->artisan('pfu:sync-news')
            ->assertExitCode(0);

        $this->assertDatabaseCount('pfu_news', 3);

        $this->assertDatabaseHas('pfu_news', [
            'url' => 'https://www.pfu.gov.ua/kr/1',
            'title' => 'Новина 1 про пенсії',
            'published_at' => '18 Серпня 2026',
        ]);

        $this->assertDatabaseHas('pfu_news', [
            'url' => 'https://www.pfu.gov.ua/kr/3',
            'title' => 'Новина 3 про виплати',
            'published_at' => '16 Серпня 2026',
        ]);

        $this->assertDatabaseMissing('pfu_news', [
            'url' => 'https://www.pfu.gov.ua/kr/4',
        ]);
    }

    public function test_welcome_page_renders_pfu_news()
    {
        PfuNews::create([
            'title' => 'Тестова новина ПФУ',
            'url' => 'https://www.pfu.gov.ua/kr/test',
            'published_at' => '18 Серпня 2026',
            'preview_text' => 'Тестовий опис',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('pfuNews', 1)
                ->where('pfuNews.0.title', 'Тестова новина ПФУ')
            );
    }
}
