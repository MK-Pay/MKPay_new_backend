<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $from_currency_id
 * @property int $to_currency_id
 * @property string $rate
 * @property string $fee_percentage
 * @property string $fee_fixed
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Currency $fromCurrency
 * @property-read Currency $toCurrency
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereFeeFixed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereFeePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereFromCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereToCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate whereValidUntil($value)
 *
 * @mixin \Eloquent
 */
class CurrencyExchangeRate extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate',
        'fee_percentage',
        'fee_fixed',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:10',
            'fee_percentage' => 'decimal:2',
            'fee_fixed' => 'decimal:8',
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    /**
     * Get the source currency for the exchange rate.
     */
    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    /**
     * Get the target currency for the exchange rate.
     */
    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }
}
