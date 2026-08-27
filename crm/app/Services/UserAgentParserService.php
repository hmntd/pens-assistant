<?php

namespace App\Services;

class UserAgentParserService
{
    /**
     * Parse raw User-Agent string into Browser, OS, and Device attributes.
     *
     * @param string|null $userAgent
     * @return array{browser: string, os: string, device: string}
     */
    public function parse(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return [
                'browser' => 'Unknown',
                'os' => 'Unknown',
                'device' => 'Desktop',
            ];
        }

        $browser = $this->detectBrowser($userAgent);
        $os = $this->detectOS($userAgent);
        $device = $this->detectDevice($userAgent);

        return [
            'browser' => $browser,
            'os' => $os,
            'device' => $device,
        ];
    }

    protected function detectBrowser(string $ua): string
    {
        if (preg_match('/Edg/i', $ua)) {
            return 'Edge';
        }
        if (preg_match('/OPR|Opera/i', $ua)) {
            return 'Opera';
        }
        if (preg_match('/Firefox|FxiOS/i', $ua)) {
            return 'Firefox';
        }
        if (preg_match('/Chrome|CriOS/i', $ua) && ! preg_match('/Edg/i', $ua)) {
            return 'Chrome';
        }
        if (preg_match('/Safari/i', $ua) && ! preg_match('/Chrome|CriOS|Edg/i', $ua)) {
            return 'Safari';
        }
        if (preg_match('/MSIE|Trident/i', $ua)) {
            return 'Internet Explorer';
        }

        return 'Other';
    }

    protected function detectOS(string $ua): string
    {
        if (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            return 'iOS';
        }
        if (preg_match('/Android/i', $ua)) {
            return 'Android';
        }
        if (preg_match('/Windows|Win64|Win32/i', $ua)) {
            return 'Windows';
        }
        if (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            return 'macOS';
        }
        if (preg_match('/Linux/i', $ua)) {
            return 'Linux';
        }

        return 'Other';
    }

    protected function detectDevice(string $ua): string
    {
        if (preg_match('/iPad|Tablet/i', $ua)) {
            return 'Tablet';
        }
        if (preg_match('/Mobile|iPhone|Android/i', $ua)) {
            return 'Mobile';
        }

        return 'Desktop';
    }
}
