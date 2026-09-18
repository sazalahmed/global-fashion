<?php

namespace Modules\Ecommerce\Support;

use Modules\Ecommerce\Models\SlugHistory;

trait HasSlugHistory
{
    public static function bootHasSlugHistory(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty('slug') && filled($model->getOriginal('slug'))) {
                SlugHistory::updateOrCreate(
                    ['model_type' => $model->getMorphClass(), 'old_slug' => $model->getOriginal('slug')],
                    ['model_id' => $model->getKey()]
                );
            }
        });
    }

    public static function currentSlugFor(string $oldSlug): ?string
    {
        $row = SlugHistory::where('model_type', (new static)->getMorphClass())
            ->where('old_slug', $oldSlug)->first();
        if (! $row) { return null; }
        return optional(static::find($row->model_id))->slug;
    }
}
