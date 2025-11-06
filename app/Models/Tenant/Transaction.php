<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int|null $app_id
 * @property int $wallet_id
 * @property int $currency_id
 * @property int $transaction_status_id
 * @property string $type
 * @property string $amount
 * @property string $fee
 * @property string $net_amount
 * @property string|null $payment_method
 * @property string|null $external_id
 * @property string|null $description
 * @property array|null $metadata
 * @property int|null $related_transaction_id
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Account $account
 * @property-read App|null $app
 * @property-read Wallet $wallet
 * @property-read Currency $currency
 * @property-read TransactionStatus $transactionStatus
 * @property-read Transaction|null $relatedTransaction
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Transaction> $relatedTransactions
 * @property-read int|null $related_transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereNetAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereRelatedTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereTransactionStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereWalletId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'app_id',
        'wallet_id',
        'currency_id',
        'transaction_status_id',
        'type',
        'amount',
        'fee',
        'net_amount',
        'payment_method',
        'external_id',
        'description',
        'metadata',
        'related_transaction_id',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'fee' => 'decimal:8',
            'net_amount' => 'decimal:8',
            'metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\TransactionFactory
    {
        return \Database\Factories\TransactionFactory::new();
    }

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'account_id',
                'app_id',
                'wallet_id',
                'currency_id',
                'transaction_status_id',
                'type',
                'amount',
                'fee',
                'net_amount',
                'payment_method',
                'external_id',
                'description',
                'metadata',
                'related_transaction_id',
                'completed_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the account that owns the transaction.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the app that owns the transaction.
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * Get the wallet for the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the currency for the transaction.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the transaction status.
     */
    public function transactionStatus(): BelongsTo
    {
        return $this->belongsTo(TransactionStatus::class);
    }

    /**
     * Get the related transaction (for refunds, transfers, etc.).
     */
    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'related_transaction_id');
    }

    /**
     * Get the transactions related to this transaction.
     */
    public function relatedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'related_transaction_id');
    }
}
