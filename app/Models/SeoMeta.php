<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    protected $fillable = [
        'meta_title',
        'meta_description',
        'og_image',
    ];

    public function seoable()
    {
        return $this->morphTo();
    }
}
