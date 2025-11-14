<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Account> $accounts
 * @property-read int|null $accounts_count
 * @method static \Database\Factories\AccountCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory query()
 * @mixin \Eloquent
 */
class AccountCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\AccountCategoryFactory
    {
        return \Database\Factories\AccountCategoryFactory::new();
    }

    /**
     * Get the accounts in this category.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function getTable()
    {
        return 'public.account_categories';
    }
}
