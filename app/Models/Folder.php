<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Folder extends File
{
    protected $attributes = [
        'is_folder' => true,
    ];

    public static function booted()
    {
        static::addGlobalScope('folder', function (Builder $query) {
            $query->where('is_folder', true);
        });
    }
}
