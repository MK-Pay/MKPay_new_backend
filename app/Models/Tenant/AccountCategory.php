<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Account> $accounts
 * @property-read int|null $accounts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCategory whereUpdatedAt($value)
 *
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
}
