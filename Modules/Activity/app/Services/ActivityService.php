<?php

namespace Modules\Activity\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Activity\Models\ActivityLog;

class ActivityService
{
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return ActivityLog::with('causer')
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where('description', 'like', "%{$s}%"))
            ->when($filters['log_name'] ?? null, fn($q, $n) => $q->byLogName($n))
            ->when($filters['event'] ?? null, fn($q, $e) => $q->where('event', $e))
            ->when($filters['causer_id'] ?? null, function ($q, $id) {
                $q->where('causer_type', \App\Models\User::class)->where('causer_id', $id);
            })
            ->when($filters['subject_type'] ?? null, fn($q, $t) => $q->bySubjectType($t))
            ->when($filters['from_date'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to_date'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ActivityLog
    {
        return ActivityLog::with('causer', 'subject')->findOrFail($id);
    }

    public function clear(?string $logName = null, ?int $olderThanDays = null): int
    {
        $query = ActivityLog::query();

        if ($logName) {
            $query->where('log_name', $logName);
        }

        if ($olderThanDays) {
            $query->where('created_at', '<', now()->subDays($olderThanDays));
        }

        return $query->delete();
    }

    public function getStats(): array
    {
        return [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'this_week' => ActivityLog::where('created_at', '>=', now()->startOfWeek())->count(),
            'log_names' => ActivityLog::distinct()->pluck('log_name')->toArray(),
        ];
    }

    public function getSubjectTypes(): array
    {
        return ActivityLog::whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->map(fn($type) => class_basename($type))
            ->toArray();
    }
}
