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
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Account> $accounts
 * @property-read int|null $accounts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountType whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AccountType extends Model
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
    protected static function newFactory(): \Database\Factories\AccountTypeFactory
    {
        return \Database\Factories\AccountTypeFactory::new();
    }

    /**
     * Get the accounts with this type.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
