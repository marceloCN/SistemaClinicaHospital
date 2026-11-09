<?php

declare(strict_types=1);

namespace Lib\Support;

use Lib\Core\Request;
use System\Models\Visits;

class Analytics
{
    public static function track(Request $request): void
    {
        try {
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $language = $request->header('Accept-Language', 'UNKNOWN');
            $page = $request->path();
            $referer = $request->referer() ?: 'DIRECT';

            $browser = self::getBrowser($userAgent);
            $os = self::getOS($userAgent);
            $device = self::getDevice($userAgent);
            $location = self::getLocation($ip);

            Visits::query()->insert([
                'ip_address' => $ip,
                'country' => $location['country'],
                'city' => $location['city'],
                'browser' => $browser,
                'operating_system' => $os,
                'device_type' => $device,
                'language' => $language,
                'page_url' => $page,
                'referer_url' => $referer,
                'user_agent' => $userAgent,
                'visited_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('Analytics error: ' . $e->getMessage());
        }
    }

    private static function getBrowser(string $agent): string
    {
        // Orden importante: los UA de Edge/Opera/Chrome tambien contienen
        // "Chrome" y "Safari"; hay que comprobar los mas especificos primero.
        return match (true) {
            str_contains($agent, 'Edg/'), str_contains($agent, 'Edge/') => 'Edge',
            str_contains($agent, 'OPR/'), str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Firefox'), str_contains($agent, 'FxiOS') => 'Firefox',
            str_contains($agent, 'Chrome'), str_contains($agent, 'CriOS') => 'Chrome',
            str_contains($agent, 'Safari') => 'Safari',
            default => 'Unknown',
        };
    }

    private static function getOS(string $agent): string
    {
        // Android contiene "Linux" y los iPhone/iPad contienen "Mac OS X".
        return match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad'), str_contains($agent, 'iPod') => 'iOS',
            str_contains($agent, 'Mac') => 'MacOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Unknown',
        };
    }

    private static function getDevice(string $agent): string
    {
        return preg_match('/mobile/i', $agent) ? 'Mobile' : 'Desktop';
    }

    private static function getLocation(string $ip): array
    {
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return ['country' => 'Local', 'city' => 'Localhost'];
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return ['country' => 'Unknown', 'city' => 'Unknown'];
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['country' => 'Private', 'city' => 'Private'];
        }

        $cached = self::cachedLocation($ip);

        if ($cached !== null) {
            return $cached;
        }

        $context = stream_context_create(['http' => ['timeout' => 1]]);
        $json = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,city", false, $context);

        if (!$json) {
            return ['country' => 'Unknown', 'city' => 'Unknown'];
        }

        $data = json_decode($json, true);

        if (($data['status'] ?? '') !== 'success') {
            return ['country' => 'Unknown', 'city' => 'Unknown'];
        }

        $location = [
            'country' => $data['country'] ?? 'Unknown',
            'city' => $data['city'] ?? 'Unknown',
        ];

        self::cacheLocation($ip, $location);

        return $location;
    }

    private static function cachedLocation(string $ip): ?array
    {
        $file = self::cacheFile($ip);

        if (!is_file($file) || filemtime($file) < time() - 86400) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : null;
    }

    private static function cacheLocation(string $ip, array $location): void
    {
        $file = self::cacheFile($ip);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        @file_put_contents($file, json_encode($location), LOCK_EX);
    }

    private static function cacheFile(string $ip): string
    {
        return BASE_PATH . '/storage/cache/analytics/' . md5($ip) . '.json';
    }
}
