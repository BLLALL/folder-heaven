<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class File extends Model
{
    /** @use HasFactory<\Database\Factories\FileFactory> */
    use HasFactory;

    protected $table = 'files';

    protected $fillable = [
        'name',
        'path',
        'size',
        'mime_type',
        'is_folder',
        'owner_id',
        'parent_folder_id',
        'file_hash',
    ];

    protected $casts = [
        'is_folder' => 'boolean',
        'size' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(File::class, 'parent_folder_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(File::class, 'parent_folder_id');
    }
}
