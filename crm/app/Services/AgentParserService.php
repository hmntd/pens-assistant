<?php

namespace App\Services;

class AgentParserService
{
    /**
     * Parse User-Agent string to extract device type, platform, and browser details.
     *
     * @param string|null $userAgent
     * @return array{device_type: string, platform: string, browser: string, browser_version: string|null}
     */
    public function parse(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return [
                'device_type' => 'Desktop',
                'platform' => 'Unknown',
                'browser' => 'Unknown',
                'browser_version' => null,
            ];
        }

        return [
            'device_type' => $this->detectDeviceType($userAgent),
            'platform' => $this->detectPlatform($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'browser_version' => $this->detectBrowserVersion($userAgent),
        ];
    }

    private function detectDeviceType(string $userAgent): string
    {
        return match (true) {
            (bool) preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $userAgent) => 'Tablet',
            (bool) preg_match('/(mobile|iphone|ipod|blackberry|phone|iemobile|opera mini)/i', $userAgent) => 'Mobile',
            default => 'Desktop',
        };
    }

    private function detectPlatform(string $userAgent): string
    {
        return match (true) {
            (bool) preg_match('/Windows NT/i', $userAgent) => 'Windows',
            (bool) preg_match('/iPhone|iPad|iPod/i', $userAgent) => 'iOS',
            (bool) preg_match('/Macintosh|Mac OS X/i', $userAgent) => 'macOS',
            (bool) preg_match('/Android/i', $userAgent) => 'Android',
            (bool) preg_match('/CrOS/i', $userAgent) => 'ChromeOS',
            (bool) preg_match('/Linux/i', $userAgent) => 'Linux',
            default => 'Unknown',
        };
    }

    private function detectBrowser(string $userAgent): string
    {
        return match (true) {
            (bool) preg_match('/Edg([ea])?\//i', $userAgent) => 'Edge',
            (bool) preg_match('/OPR\/|Opera\//i', $userAgent) => 'Opera',
            (bool) preg_match('/Firefox\//i', $userAgent) => 'Firefox',
            (bool) preg_match('/Chrome\//i', $userAgent) => 'Chrome',
            (bool) preg_match('/Safari\//i', $userAgent) => 'Safari',
            default => 'Unknown',
        };
    }

    private function detectBrowserVersion(string $userAgent): ?string
    {
        $browser = $this->detectBrowser($userAgent);
        $pattern = match ($browser) {
            'Edge' => '/Edg([ea])?\/([0-9.]+)/i',
            'Opera' => '/(?:OPR|Opera)\/([0-9.]+)/i',
            'Firefox' => '/Firefox\/([0-9.]+)/i',
            'Chrome' => '/Chrome\/([0-9.]+)/i',
            'Safari' => '/Version\/([0-9.]+)/i',
            default => null,
        };

        if ($pattern && preg_match($pattern, $userAgent, $matches)) {
            $versionStr = end($matches);
            $parts = explode('.', $versionStr);

            return $parts[0] ?? $versionStr;
        }

        return null;
    }
}
