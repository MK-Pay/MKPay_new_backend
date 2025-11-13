<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Currency|null $currency
 * @property-read Wallet|null $wallet
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletBalance query()
 * @mixin \Eloquent
 */
class WalletBalance extends Model
{
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wallet_id',
        'currency_id',
        'balance',
        'held_balance',
        'available_balance',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:8',
            'held_balance' => 'decimal:8',
            'available_balance' => 'decimal:8',
        ];
    }

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'wallet_id',
                'currency_id',
                'balance',
                'held_balance',
                'available_balance',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the wallet that owns the balance.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the currency for the balance.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
