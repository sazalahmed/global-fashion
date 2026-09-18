<?php

namespace Modules\Setting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Branch\Models\Branch;

class Printer extends Model
{
    protected $fillable = [
        'name', 'ip_address', 'port', 'printer_type', 'connection_type',
        'paper_width', 'purpose', 'branch_id', 'is_default', 'is_active',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'port'        => 'integer',
            'paper_width' => 'integer',
            'is_default'  => 'boolean',
            'is_active'   => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByPurpose(Builder $query, string $purpose): Builder
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Human-readable printer type label for list/detail display.
     */
    public function getPrinterTypeLabelAttribute(): string
    {
        return match ($this->printer_type) {
            'thermal'    => 'Thermal (80mm)',
            'thermal_58' => 'Thermal (58mm)',
            'a4'         => 'A4 Printer',
            'label'      => 'Label Printer',
            default      => ucfirst(str_replace('_', ' ', (string) $this->printer_type)),
        };
    }

    /**
     * Test if printer is reachable via TCP socket.
     */
    public function testConnection(int $timeout = 3): array
    {
        $start = microtime(true);
        $socket = @fsockopen($this->ip_address, $this->port, $errno, $errstr, $timeout);

        if ($socket) {
            $latency = round((microtime(true) - $start) * 1000);
            fclose($socket);
            return ['success' => true, 'latency_ms' => $latency, 'error' => null];
        }

        return ['success' => false, 'latency_ms' => null, 'error' => "{$errstr} ({$errno})"];
    }
}
