<?php

namespace App\Models\Tenant;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property-read Account|null $account
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AppSecretToken> $secretTokens
 * @property-read int|null $secret_tokens_count
 * @property string|null $app_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Wallet> $wallets
 * @property-read int|null $wallets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Webhook> $webhooks
 * @property-read int|null $webhooks_count
 * @method static \Database\Factories\AppFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App withoutTrashed()
 * @mixin \Eloquent
 */
class App extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'description',
        'is_active',
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (App $app) {
            if (empty($app->app_id)) {
                $app->app_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\AppFactory
    {
        return \Database\Factories\AppFactory::new();
    }

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'account_id',
                'name',
                'description',
                'is_active',
                'settings',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the account that owns the app.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the secret tokens for the app.
     */
    public function secretTokens(): HasMany
    {
        return $this->hasMany(AppSecretToken::class);
    }

    /**
     * Get the wallets for the app.
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the transactions for the app.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the webhooks for the app.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function getTable()
    {
        return 'apps';
    }
}
