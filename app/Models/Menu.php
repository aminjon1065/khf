<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use ClearsResponseCache;

    protected $guarded = ['id'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }
}
