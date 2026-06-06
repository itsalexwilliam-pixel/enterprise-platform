<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleKeyword extends Model
{
    protected $fillable = ['keyword', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public $timestamps = false;

    public static function getAllKeywords(): array
    {
        return static::where('is_active', true)->pluck('keyword')->toArray();
    }
}
