<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class SeoPage extends Model
{
    protected $fillable = ['key', 'title', 'description', 'image', 'robots'];

    public static function forKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
