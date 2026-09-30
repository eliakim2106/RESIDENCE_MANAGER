<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Journal des tentatives de connexion.
 */
class LoginLog extends Model
{
    public const UPDATED_AT = null;

    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'successful',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
