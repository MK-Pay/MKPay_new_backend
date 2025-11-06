<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property int $decimal_places
 * @property bool $is_active
 * @property bool $is_national
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WalletBalance> $walletBalances
 * @property-read int|null $wallet_balances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CurrencyExchangeRate> $exchangeRatesFrom
 * @property-read int|null $exchange_rates_from_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CurrencyExchangeRate> $exchangeRatesTo
 * @property-read int|null $exchange_rates_to_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereDecimalPlaces($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereIsNational($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Currency extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
        'is_national',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
            'is_national' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\CurrencyFactory
    {
        return \Database\Factories\CurrencyFactory::new();
    }

    /**
     * Get the wallet balances in this currency.
     */
    public function walletBalances(): HasMany
    {
        return $this->hasMany(WalletBalance::class);
    }

    /**
     * Get the transactions in this currency.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the exchange rates from this currency.
     */
    public function exchangeRatesFrom(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class, 'from_currency_id');
    }

    /**
     * Get the exchange rates to this currency.
     */
    public function exchangeRatesTo(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class, 'to_currency_id');
    }
}
