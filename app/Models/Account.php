<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property-read AccountCategory|null $accountCategory
 * @property-read AccountStatus|null $accountStatus
 * @property-read AccountType|null $accountType
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tenant\App> $apps
 * @property-read int|null $apps_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tenant\DocumentValidation> $documentValidations
 * @property-read int|null $document_validations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tenant\Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tenant\Wallet> $wallets
 * @property-read int|null $wallets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tenant\Webhook> $webhooks
 * @property-read int|null $webhooks_count
 * @method static \Database\Factories\AccountFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account withoutTrashed()
 * @mixin \Eloquent
 */
class Account extends Model
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
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\AccountFactory
    {
        return \Database\Factories\AccountFactory::new();
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
        return $this->hasMany(Tenant\App::class);
    }

    /**
     * Get the wallets for the account.
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Tenant\Wallet::class);
    }

    /**
     * Get the transactions for the account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Tenant\Transaction::class);
    }

    /**
     * Get the webhooks for the account.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Tenant\Webhook::class);
    }

    /**
     * Get the document validations for the account.
     */
    public function documentValidations(): HasMany
    {
        return $this->hasMany(Tenant\DocumentValidation::class);
    }
}
