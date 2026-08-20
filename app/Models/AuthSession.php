<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthSession extends Model
{
    use HasFactory;

    protected $table = 'auth_sessions';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'token',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->where('is_active', true)->latest()->first();
    }
}
