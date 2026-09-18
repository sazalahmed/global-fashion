<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class SlugHistory extends Model
{
    protected $fillable = ['model_type', 'model_id', 'old_slug'];
}
