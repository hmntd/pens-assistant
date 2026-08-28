<?php

namespace App\Services;

use App\Events\PfuSalariesSynced;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PfuSalaryScraperService
{
    /**
     * Parse Ukrainian text salary string to float decimal.
     */
    public function parseSalaryText(string $text): ?float
    {
        $clean = str_replace(["\xC2\xA0", "&nbsp;"], ' ', trim($text));

        if (empty($clean)) {
            return null;
        }

        if (preg_match('/(\d{1,3}(?:[\s\xA0]?\d{3})*)\s*(?:грн|гривень|гривні|гривня)\s*(\d{1,2})\s*(?:коп|копійок|копійки)/iu', $clean, $matches)) {
            $hryvniasStr = preg_replace('/[^\d]/', '', $matches[1]);
            $kopecksStr = str_pad($matches[2], 2, '0', STR_PAD_RIGHT);
            return (float) ($hryvniasStr . '.' . $kopecksStr);
        }

        if (preg_match('/(\d{1,3}(?:[\s\xA0]?\d{3})*)[,.](\d{1,2})(?:\s*(?:грн|гривень|гривні|гривня))?/iu', $clean, $matches)) {
            $hryvniasStr = preg_replace('/[^\d]/', '', $matches[1]);
            $kopecksStr = str_pad($matches[2], 2, '0', STR_PAD_RIGHT);
            return (float) ($hryvniasStr . '.' . $kopecksStr);
        }

        if (preg_match('/(\d{1,3}(?:[\s\xA0]?\d{3})*)\s*(?:грн|гривень|гривні|гривня)/iu', $clean, $matches)) {
            $hryvniasStr = preg_replace('/[^\d]/', '', $matches[1]);
            return (float) $hryvniasStr;
        }

        return null;
    }

    /**
     * Convert Ukrainian or English month name to integer (1-12).
     */
    public function parseMonthNameToNumber(string $text): ?int
    {
        $normalized = mb_strtolower(trim($text));

        $months = [
            1 => ['січень', 'січня', 'january', 'jan'],
            2 => ['лютий', 'лютого', 'february', 'feb'],
            3 => ['березень', 'березня', 'march', 'mar'],
            4 => ['квітень', 'квітня', 'april', 'apr'],
            5 => ['травень', 'травня', 'may'],
            6 => ['червень', 'червня', 'june', 'jun'],
            7 => ['липень', 'липня', 'july', 'jul'],
            8 => ['серпень', 'серпня', 'august', 'aug'],
            9 => ['вересень', 'вересня', 'september', 'sep'],
            10 => ['жовтень', 'жовтня', 'october', 'oct'],
            11 => ['листопад', 'листопада', 'november', 'nov'],
            12 => ['грудень', 'грудня', 'december', 'dec'],
        ];

        foreach ($months as $num => $names) {
            foreach ($names as $name) {
                if (str_contains($normalized, $name)) {
                    return $num;
                }
            }
        }

        return null;
    }

    /**
     * Extract an array of POTENTIAL target year page URLs.
     */
    public function extractYearUrls(string $html, array $targetYears): array
    {
        $yearUrls = [];
        foreach ($targetYears as $year) {
            $yearUrls[$year] = [];
        }

        if (empty($html)) {
            return $yearUrls;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new \DOMXPath($dom);

        $links = $xpath->query('//a[@href]');
        if ($links) {
            foreach ($links as $link) {
                /** @var \DOMElement $link */
                $href = $link->getAttribute('href');
                $text = mb_strtolower(trim($link->textContent));

                foreach ($targetYears as $year) {
                    $yearStr = (string) $year;

                    if (str_contains($text, $yearStr) || str_contains($href, "-za-{$yearStr}-rik") || str_contains($href, "{$yearStr}-rik")) {
                        $fullUrl = str_starts_with($href, 'http') ? $href : 'https://www.pfu.gov.ua' . ltrim($href, '/');

                        if (! in_array($fullUrl, $yearUrls[$year])) {
                            if (str_contains($href, 'zarobitnoy') || str_contains($text, 'заробітн')) {
                                array_unshift($yearUrls[$year], $fullUrl);
                            } else {
                                $yearUrls[$year][] = $fullUrl;
                            }
                        }
                    }
                }
            }
        }

        return $yearUrls;
    }

    /**
     * Scrape a single year detail page URL and extract monthly average salary figures.
     */
    public function scrapeYearDetailPage(string $url, int $year): array
    {
        $records = [];
        try {
            $response = Http::timeout(10)->get($url);
            if (! $response->successful()) {
                return $records;
            }

            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $response->body());
            $xpath = new \DOMXPath($dom);

            $nodes = $xpath->query('//tr | //p | //li');
            if ($nodes) {
                foreach ($nodes as $node) {
                    $text = '';
                    foreach ($node->childNodes as $child) {
                        $text .= $child->textContent . ' ';
                    }
                    $text = preg_replace('/\s+/u', ' ', trim($text));

                    $month = $this->parseMonthNameToNumber($text);

                    if ($month !== null) {
                        $amount = $this->parseSalaryText($text);

                        if ($amount !== null && $amount > 1000.0) {
                            $records[] = [
                                'year' => $year,
                                'month' => $month,
                                'amount' => $amount,
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning("Failed to scrape detail page for year {$year} at {$url}: " . $e->getMessage());
        }

        return $records;
    }

    /**
     * Fetch main PFU page, retrieve current and previous year detail URLs, parse monthly amounts, and sync over gRPC.
     */
    public function scrapeAndSync(): array
    {
        $currentYear = (int) date('Y');
        $prevYear = $currentYear - 1;
        $targetYears = [$currentYear, $prevYear];

        $mainUrl = 'https://www.pfu.gov.ua/statystyka/pokazniki-serednoyi-zarobitnoyi-plat/';
        $archiveUrl = 'https://www.pfu.gov.ua/statystyka/pokazniki-serednoyi-zarobitnoyi-plat/arhiv-zapitannya-vidpovidi-peremishhenim-pokazniki-serednoyi-zarobitnoyi-plat/';

        $records = [];

        try {
            $mainResponse = Http::timeout(10)->get($mainUrl);
            $archiveResponse = Http::timeout(10)->get($archiveUrl);

            $combinedHtml = ($mainResponse->successful() ? $mainResponse->body() : '') .
                ($archiveResponse->successful() ? $archiveResponse->body() : '');

            $yearUrls = $this->extractYearUrls($combinedHtml, $targetYears);

            foreach ($yearUrls as $year => $urls) {
                foreach ($urls as $url) {
                    $yearRecords = $this->scrapeYearDetailPage($url, $year);

                    if (! empty($yearRecords)) {
                        foreach ($yearRecords as $rec) {
                            $records[] = $rec;
                        }
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning('PFU main page fetch failed: ' . $e->getMessage());
        }



        $calcClient = new \Calc\CalcServiceClient('calc:50051', [
            'credentials' => \Grpc\ChannelCredentials::createInsecure(),
        ]);

        $grpcRequest = new \Calc\SyncAverageSalariesRequest();
        $salaryProtos = [];

        foreach ($records as $item) {
            $protoRec = new \Calc\AverageSalaryRecord();
            $protoRec->setYear($item['year']);
            $protoRec->setMonth($item['month']);
            $protoRec->setAmount($item['amount']);
            $salaryProtos[] = $protoRec;
        }

        $grpcRequest->setSalaries($salaryProtos);

        /** @var \Calc\SyncAverageSalariesResponse|null $grpcResponse */
        list($grpcResponse, $status) = $calcClient->SyncAverageSalaries($grpcRequest)->wait();

        Log::info("gRPC SyncAverageSalaries status code: " . $status->code . ", details: " . ($status->details ?? 'none'));

        $processedCount = ($status->code === \Grpc\STATUS_OK && $grpcResponse && $grpcResponse->getSuccess())
            ? $grpcResponse->getProcessedCount()
            : count($records);

        PfuSalariesSynced::dispatch(
            $records,
            $processedCount,
            auth()->check() ? auth()->id() : null
        );

        return [
            'status' => 'success',
            'processed' => $processedCount,
            'records' => $records,
        ];
    }
}
