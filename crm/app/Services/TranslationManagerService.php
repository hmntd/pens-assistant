<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class TranslationManagerService
{
    private string $ukJsonPath;

    private string $enJsonPath;

    private string $ukTsPath;

    private string $enTsPath;

    public function __construct()
    {
        $this->ukJsonPath = base_path('lang/uk.json');
        $this->enJsonPath = base_path('lang/en.json');
        $this->ukTsPath = resource_path('js/i18n/uk.ts');
        $this->enTsPath = resource_path('js/i18n/en.ts');
    }

    /**
     * Retrieve all flattened translation key-value pairs.
     */
    public function getAllTranslations(): array
    {
        $ukMap = $this->loadTsFile($this->ukTsPath);
        $enMap = $this->loadTsFile($this->enTsPath);

        $ukJson = $this->loadJsonFile($this->ukJsonPath);
        $enJson = $this->loadJsonFile($this->enJsonPath);

        $flatUk = array_merge($this->flattenArray($ukMap), $ukJson);
        $flatEn = array_merge($this->flattenArray($enMap), $enJson);

        $allKeys = array_unique(array_merge(array_keys($flatUk), array_keys($flatEn)));
        sort($allKeys);

        $items = [];
        foreach ($allKeys as $key) {
            $items[] = [
                'key' => $key,
                'uk' => $flatUk[$key] ?? '',
                'en' => $flatEn[$key] ?? '',
            ];
        }

        return $items;
    }

    /**
     * Save translation key to JSON and TS locale files.
     */
    public function saveTranslationKey(string $key, string $ukVal, string $enVal): void
    {
        // 1. Update JSON files
        $ukJson = $this->loadJsonFile($this->ukJsonPath);
        $enJson = $this->loadJsonFile($this->enJsonPath);

        $ukJson[$key] = $ukVal;
        $enJson[$key] = $enVal;

        File::put($this->ukJsonPath, json_encode($ukJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put($this->enJsonPath, json_encode($enJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 2. Update TS locale files
        $ukTsData = $this->loadTsFile($this->ukTsPath);
        $enTsData = $this->loadTsFile($this->enTsPath);

        $this->setArrayValueByDotKey($ukTsData, $key, $ukVal);
        $this->setArrayValueByDotKey($enTsData, $key, $enVal);

        $this->saveTsFile($this->ukTsPath, $ukTsData);
        $this->saveTsFile($this->enTsPath, $enTsData);
    }

    private function loadJsonFile(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }
        $content = File::get($path);
        $json = json_decode($content, true);

        return is_array($json) ? $json : [];
    }

    private function loadTsFile(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }
        $content = File::get($path);
        if (preg_match('/export\s+default\s+(\{[\s\S]*\});?$/', trim($content), $matches)) {
            $jsonLike = $matches[1];
            return $this->parseJsObjectString($jsonLike);
        }

        return [];
    }

    private function saveTsFile(string $path, array $data): void
    {
        $exportContent = "export default ".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).";\n";
        File::put($path, $exportContent);
    }

    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = (string) $value;
            }
        }

        return $result;
    }

    private function setArrayValueByDotKey(array &$array, string $dotKey, string $value): void
    {
        $keys = explode('.', $dotKey);
        $current = &$array;
        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (! isset($current[$k]) || ! is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }
    }

    private function parseJsObjectString(string $jsStr): array
    {
        $converted = preg_replace('/([{,]\s*)([a-zA-Z0-9_]+)\s*:/', '$1"$2":', $jsStr);
        $decoded = json_decode($converted, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return [];
    }
}
