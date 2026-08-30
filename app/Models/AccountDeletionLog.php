<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AccountDeletionLog
 *
 * One row per audit event. NEVER contains password/OTP/token/payment secret.
 *
 * @property int $id
 * @property int $deletion_request_id
 * @property string $action
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $status
 * @property string|null $target_table
 * @property int|null $affected_rows
 * @property string|null $message
 * @property array|null $metadata
 */
class AccountDeletionLog extends Model
{
    use HasFactory;

    protected $table = 'account_deletion_logs';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AccountDeletionRequest::class, 'deletion_request_id');
    }
}
