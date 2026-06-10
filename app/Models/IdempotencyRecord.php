<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyRecord extends Model
{
    protected $fillable = [
        'idempotency_key',
        'request_body_hash',
        'response_body',
        'response_status',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
        ];
    }
}
