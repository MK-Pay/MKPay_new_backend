<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Account> $accounts
 * @property-read int|null $accounts_count
 * @method static \Database\Factories\AccountStatusFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountStatus query()
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
