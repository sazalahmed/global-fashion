<?php

namespace Modules\Security\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Security\Models\ApiKey;

class ApiKeyService
{
    /**
     * List all API keys with pagination.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return ApiKey::with('creator')
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Generate a new API key. Returns [ApiKey model, plaintext key].
     */
    public function create(array $data): array
    {
        $prefix = $data['environment'] === 'test' ? 'bp_test_' : 'bp_live_';
        $randomPart = Str::random(32);
        $plainTextKey = $prefix . $randomPart;

        $expiresAt = null;
        if (isset($data['expiry']) && $data['expiry'] !== 'never') {
            $expiresAt = now()->addDays((int) $data['expiry']);
        }

        $apiKey = ApiKey::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'key_hash'    => Hash::make($plainTextKey),
            'key_prefix'  => $prefix . '****************************' . substr($randomPart, -4),
            'environment' => $data['environment'] ?? 'live',
            'expires_at'  => $expiresAt,
            'rate_limit'  => $data['rate_limit'] ?? 60,
            'permissions' => $data['api_permissions'] ?? [],
            'status'      => 'active',
            'created_by'  => auth()->id(),
        ]);

        return [$apiKey, $plainTextKey];
    }

    /**
     * Delete (soft) an API key.
     */
    public function delete(int $id): void
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->delete();
    }

    /**
     * Get stats for the API keys dashboard.
     */
    public function getStats(): array
    {
        return [
            'total'   => ApiKey::count(),
            'active'  => ApiKey::where('status', 'active')->count(),
            'revoked' => ApiKey::where('status', 'revoked')->count(),
            'expired' => ApiKey::where('status', 'expired')->count(),
        ];
    }
}
