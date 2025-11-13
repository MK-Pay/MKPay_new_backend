<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Transaction> $transactions
 * @property-read int|null $transactions_count
 * @method static \Database\Factories\TransactionStatusFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionStatus query()
 * @mixin \Eloquent
 */
class TransactionStatus extends Model
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
        'is_final',
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
            'is_final' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\TransactionStatusFactory
    {
        return \Database\Factories\TransactionStatusFactory::new();
    }

    /**
     * Get the transactions with this status.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getTable()
    {
        return 'transaction_status';
    }
}
