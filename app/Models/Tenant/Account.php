<?php

namespace App\Models\Tenant;

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
 * @property int $account_type_id
 * @property int|null $account_category_id
 * @property int $account_status_id
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $name
 * @property string|null $cpf
 * @property string|null $cnpj
 * @property string|null $phone
 * @property array|null $usage_types
 * @property string|null $hourly_transaction_limit
 * @property string|null $daily_transaction_limit
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read AccountType $accountType
 * @property-read AccountCategory|null $accountCategory
 * @property-read AccountStatus $accountStatus
 * @property-read \Illuminate\Database\Eloquent\Collection<int, App> $apps
 * @property-read int|null $apps_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Wallet> $wallets
 * @property-read int|null $wallets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Webhook> $webhooks
 * @property-read int|null $webhooks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DocumentValidation> $documentValidations
 * @property-read int|null $document_validations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereAccountCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereAccountStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereAccountTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCnpj($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCpf($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereDailyTransactionLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereHourlyTransactionLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereUsageTypes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Account extends Model
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_type_id',
        'account_category_id',
        'account_status_id',
        'email',
        'email_verified_at',
        'name',
        'cpf',
        'cnpj',
        'phone',
        'usage_types',
        'hourly_transaction_limit',
        'daily_transaction_limit',
        'verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'usage_types' => 'array',
            'hourly_transaction_limit' => 'decimal:2',
            'daily_transaction_limit' => 'decimal:2',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Account $account) {
            if (empty($account->uuid)) {
                $account->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'account_type_id',
                'account_category_id',
                'account_status_id',
                'email',
                'email_verified_at',
                'name',
                'cpf',
                'cnpj',
                'phone',
                'usage_types',
                'hourly_transaction_limit',
                'daily_transaction_limit',
                'verified_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the account type.
     */
    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    /**
     * Get the account category.
     */
    public function accountCategory(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class);
    }

    /**
     * Get the account status.
     */
    public function accountStatus(): BelongsTo
    {
        return $this->belongsTo(AccountStatus::class);
    }

    /**
     * Get the apps for the account.
     */
    public function apps(): HasMany
    {
        return $this->hasMany(App::class);
    }

    /**
     * Get the wallets for the account.
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the transactions for the account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the webhooks for the account.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    /**
     * Get the document validations for the account.
     */
    public function documentValidations(): HasMany
    {
        return $this->hasMany(DocumentValidation::class);
    }
}
