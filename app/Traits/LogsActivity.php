<?php

namespace App\Traits;

use Modules\Activity\Models\ActivityLog;

/**
 * Automatically logs create, update, and delete events on Eloquent models.
 *
 * Usage: Add `use LogsActivity;` to any model you want to track.
 * Optionally define `$activityLogName` to customize the log name.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logModelEvent('created', 'Created ' . $model->getActivityDescription());
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            $original = collect($dirty)->mapWithKeys(fn ($v, $k) => [$k => $model->getOriginal($k)]);

            // Skip if only timestamps changed
            $meaningful = collect($dirty)->except(['updated_at', 'created_at']);
            if ($meaningful->isEmpty()) {
                return;
            }

            $model->logModelEvent('updated', 'Updated ' . $model->getActivityDescription(), [
                'old' => $original->toArray(),
                'new' => $dirty,
            ]);
        });

        static::deleted(function ($model) {
            $model->logModelEvent('deleted', 'Deleted ' . $model->getActivityDescription());
        });
    }

    protected function logModelEvent(string $event, string $description, ?array $properties = null): void
    {
        try {
            ActivityLog::log(
                description: $description,
                subject: $this,
                event: $event,
                properties: $properties,
                logName: $this->activityLogName ?? 'default',
            );
        } catch (\Throwable $e) {
            // Non-critical — never block the operation
        }
    }

    protected function getActivityDescription(): string
    {
        $modelName = class_basename(static::class);

        // Try common identifier fields
        foreach (['invoice_number', 'purchase_number', 'return_number', 'quotation_number', 'challan_number', 'name', 'company_name', 'title', 'reference'] as $field) {
            if ($this->getAttribute($field)) {
                return "{$modelName} {$this->getAttribute($field)}";
            }
        }

        return "{$modelName} #{$this->getKey()}";
    }
}
