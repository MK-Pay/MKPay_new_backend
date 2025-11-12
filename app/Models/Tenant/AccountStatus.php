<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $allows_transactions
 * @property bool $allows_withdrawals
 * @property bool $allows_transfers
 * @property bool $holds_funds
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Account> $accounts
 * @property-read int|null $accounts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereAllowsTransactions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereAllowsTransfers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereAllowsWithdrawals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereHoldsFunds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AccountStatus extends Model
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
        'description',
        'allows_transactions',
        'allows_withdrawals',
        'allows_transfers',
        'holds_funds',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allows_transactions' => 'boolean',
            'allows_withdrawals' => 'boolean',
            'allows_transfers' => 'boolean',
            'holds_funds' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\AccountStatusFactory
    {
        return \Database\Factories\AccountStatusFactory::new();
    }

    /**
     * Get the accounts with this status.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function getTable()
    {
        return 'account_status';
    }
}
