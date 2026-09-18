<?php

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['slug', 'name', 'subject', 'body', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Render template body with placeholder replacement.
     */
    public function render(array $data = []): string
    {
        $body = $this->body;

        foreach ($data as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }

        return $body;
    }

    /**
     * Find template by slug or return null.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->active()->first();
    }
}
