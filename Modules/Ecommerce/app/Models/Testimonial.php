<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Ecommerce\Database\Factories\TestimonialFactory;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'designation',
        'message',
        'rating',
        'image',
        'is_active',
    ];

    // protected static function newFactory(): TestimonialFactory
    // {
    //     // return TestimonialFactory::new();
    // }
}
