<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Currency|null $fromCurrency
 * @property-read Currency|null $toCurrency
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyExchangeRate query()
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
