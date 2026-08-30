<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class AccountDeletionRequest
 *
 * One row per deletion request. The lifecycle is:
 *
 *   pending -> verified -> processing -> completed
 *                          \-> partially_retained
 *                          \-> failed (-> retry -> processing ...)
 *                          \-> cancelled
 *
 * SECURITY: this model deliberately does NOT expose any auth secret fields.
 *
 * @property int    $id
 * @property int|null $user_id
 * @property string $request_uuid
 * @property string $verification_method  'email_password'|'phone_otp'
 * @property string $channel              'api'|'web'
 * @property string $status
 * @property \Illuminate\Support\Carbon $requested_at
 */
class AccountDeletionRequest extends Model
{
    use HasFactory;

    /** Status constants — referenced by Service/Job/Controllers. */
    public const STATUS_PENDING             = 'pending';
    public const STATUS_VERIFIED            = 'verified';
    public const STATUS_PROCESSING          = 'processing';
    public const STATUS_COMPLETED           = 'completed';
    public const STATUS_PARTIALLY_RETAINED  = 'partially_retained';
    public const STATUS_FAILED              = 'failed';
    public const STATUS_CANCELLED           = 'cancelled';

    public const METHOD_EMAIL_PASSWORD = 'email_password';
    public const METHOD_PHONE_OTP      = 'phone_otp';

    public const CHANNEL_API = 'api';
    public const CHANNEL_WEB = 'web';

    protected $table = 'account_deletion_requests';

    protected $guarded = ['id'];

    protected $casts = [
        'requested_at'          => 'datetime',
        'verified_at'           => 'datetime',
        'processing_started_at' => 'datetime',
        'completed_at'          => 'datetime',
        'failed_at'             => 'datetime',
        'deletion_summary'      => 'array',
    ];

    /**
     * Audit log rows attached to this request.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AccountDeletionLog::class, 'deletion_request_id');
    }

    /**
     * Returns the user record IF it still exists. May be null after deletion.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Is this request in a terminal state (no further processing expected)?
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_PARTIALLY_RETAINED,
            self::STATUS_CANCELLED,
        ], true);
    }

    /**
     * Is this request currently being processed or queued?
     */
    public function isInFlight(): bool
    {
        return in_array($this->status, [
            self::STATUS_VERIFIED,
            self::STATUS_PROCESSING,
        ], true);
    }

    /**
     * The masked user reference shown to admins (never raw email/phone).
     * e.g. "u****@example.com" or "+1****1234".
     */
    public function getMaskedUserReferenceAttribute(): ?string
    {
        if (!$this->user_id) {
            return null;
        }

        // Only attempt lookup if the user record still exists.
        try {
            $user = User::find($this->user_id);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$user) {
            // User already deleted — return a tombstone label.
            return '[deleted-user #' . $this->user_id . ']';
        }

        if (!empty($user->email)) {
            $email = $user->email;
            [$name, $domain] = explode('@', $email, 2);
            $maskedName = substr($name, 0, 1) . str_repeat('*', max(0, strlen($name) - 1));
            return $maskedName . '@' . $domain;
        }

        if (!empty($user->phone)) {
            $phone = preg_replace('/\D+/', '', (string) $user->phone);
            if (strlen($phone) >= 4) {
                return '+' . substr($phone, 0, 2) . str_repeat('*', max(0, strlen($phone) - 6)) . substr($phone, -4);
            }
            return '+' . str_repeat('*', strlen($phone));
        }

        return '[user #' . $this->user_id . ']';
    }
}
