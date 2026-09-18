<?php

namespace Modules\Activity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'log_name', 'description', 'subject_type', 'subject_id',
        'causer_type', 'causer_id', 'properties', 'event',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByLogName($query, string $logName)
    {
        return $query->where('log_name', $logName);
    }

    public function scopeByCauser($query, $causer)
    {
        return $query->where('causer_type', get_class($causer))
            ->where('causer_id', $causer->id);
    }

    public function scopeBySubjectType($query, string $type)
    {
        return $query->where('subject_type', $type);
    }

    public function getChangesAttribute(): array
    {
        $props = $this->properties ?? [];
        return [
            'old' => $props['old'] ?? [],
            'new' => $props['attributes'] ?? $props['new'] ?? [],
        ];
    }

    /**
     * Static helper to log an activity.
     */
    public static function log(
        string $description,
        ?Model $subject = null,
        ?Model $causer = null,
        ?array $properties = null,
        string $event = 'created',
        string $logName = 'default',
    ): self {
        return static::create([
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'causer_type' => $causer ? get_class($causer) : (\Illuminate\Support\Facades\Auth::check() ? \App\Models\User::class : null),
            'causer_id' => $causer?->id ?? \Illuminate\Support\Facades\Auth::id(),
            'properties' => $properties,
            'event' => $event,
        ]);
    }
}
