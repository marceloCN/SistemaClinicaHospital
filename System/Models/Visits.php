<?php

declare(strict_types=1);

namespace System\Models;

use Lib\Core\Model;

class Visits extends Model
{
    protected string $table = 'visits';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;

    protected array $fillable = [
        'ip_address', 'country', 'city', 'browser', 'operating_system',
        'device_type', 'language', 'page_url', 'referer_url', 'user_agent', 'visited_at',
    ];

    public function statistics(): array
    {
        $browser = $this->raw("
            SELECT browser, COUNT(*) as total FROM visits GROUP BY browser ORDER BY total DESC
        ");

        $os = $this->raw("
            SELECT operating_system, COUNT(*) as total FROM visits GROUP BY operating_system ORDER BY total DESC
        ");

        $device = $this->raw("
            SELECT device_type, COUNT(*) as total FROM visits GROUP BY device_type ORDER BY total DESC
        ");

        return [
            'browser' => $browser,
            'os' => $os,
            'device' => $device,
        ];
    }
}
