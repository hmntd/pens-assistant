<?php

namespace App\Console\Commands;

use App\Models\PfuNews;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncPfuNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pfu:sync-news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape latest 3 PFU news items from the official PFU news page and update database';

    /**
     * Target PFU news archive URL.
     */
    public const PFU_NEWS_URL = 'https://www.pfu.gov.ua/kr/category/prestsentr/novini/';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting PFU news scrape from: '.self::PFU_NEWS_URL);

        try {
            $response = Http::connectTimeout(10)
                ->timeout(30)
                ->retry(3, 2000, throw: false)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7',
                ])
                ->get(self::PFU_NEWS_URL);

            if (! $response->successful()) {
                $this->warn('Failed to fetch PFU news page. HTTP Status: '.$response->status().'. Preserving current database records.');
                Log::warning('SyncPfuNewsCommand: Unreachable external PFU news page', ['status' => $response->status()]);

                return self::SUCCESS;
            }

            $html = $response->body();
            $items = $this->parseNewsItems($html);

            if (empty($items)) {
                $this->warn('No news items parsed from PFU page. Preserving current database records.');
                Log::warning('SyncPfuNewsCommand: 0 news items parsed from PFU page.');

                return self::SUCCESS;
            }

            $top3 = array_slice($items, 0, 3);

            DB::transaction(function () use ($top3) {
                PfuNews::truncate();
                foreach ($top3 as $item) {
                    PfuNews::create([
                        'title' => $item['title'],
                        'url' => $item['url'],
                        'published_at' => $item['published_at'],
                        'preview_text' => $item['preview_text'] ?? null,
                    ]);
                }
            });

            $this->info('Successfully updated PFU news table with '.count($top3).' records.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->warn('Error occurred while syncing PFU news: '.$e->getMessage().'. Preserving current database records.');
            Log::warning('SyncPfuNewsCommand error: '.$e->getMessage(), ['exception' => $e]);

            return self::SUCCESS;
        }
    }

    /**
     * Parse HTML and extract news items using DOMDocument.
     *
     * @return array<int, array{title: string, url: string, published_at: string, preview_text: string|null}>
     */
    public function parseNewsItems(string $html): array
    {
        $items = [];

        if (empty(trim($html))) {
            return $items;
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//div[contains(@class, "item-block-news")]//a');

        if ($nodes && $nodes->length > 0) {
            foreach ($nodes as $node) {
                if (! ($node instanceof \DOMElement)) {
                    continue;
                }

                $url = trim($node->getAttribute('href'));

                if (empty($url) || str_starts_with($url, '#')) {
                    continue;
                }

                $titleNode = $xpath->query('.//div[contains(@class, "title-block-news")]', $node)->item(0);
                $dateNode = $xpath->query('.//div[contains(@class, "data-block-news")]', $node)->item(0);

                $titleRaw = $titleNode ? trim($titleNode->textContent) : '';
                $dateRaw = $dateNode ? trim($dateNode->textContent) : '';

                $titleClean = trim((string) preg_replace('/\s+/u', ' ', $titleRaw));
                $dateClean = trim((string) preg_replace('/\s+/u', ' ', $dateRaw));

                if (preg_match('/(\d{1,2}\s+[А-Яа-яЄєІіЇїҐґ]+\s+\d{4})/', $dateClean, $dateMatches)) {
                    $publishedAt = $dateMatches[1];
                } else {
                    $publishedAt = $dateClean ?: date('d.m.Y');
                }

                if ($titleClean !== '') {
                    $items[] = [
                        'url' => $url,
                        'title' => $titleClean,
                        'published_at' => $publishedAt,
                        'preview_text' => $titleClean,
                    ];
                }
            }
        }

        return $items;
    }
}
