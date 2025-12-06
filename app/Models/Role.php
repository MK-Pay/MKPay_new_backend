<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role select(array $columns)
 * @extends \Spatie\Permission\Models\Role<\App\Models\Role>
 * @mixin \Eloquent
 */
class Role extends \Spatie\Permission\Models\Role
{
    protected $appends = [
        'label',
    ];

    public static function cachedList(?bool $cacheClear = null): Collection
    {
        $cacheKey = __METHOD__;

        if (config('app.env') === 'testing') {
            $cacheClear = true;
        }

        if ($cacheClear) {
            cache()->forget($cacheKey);
        }

        return cache()->remember(
            $cacheKey,
            600,
            fn (): Collection => static::select(['name', 'guard_name'])->get()
        );
    }

    public function getLabel(): string
    {
        $name = $this->name;

        return __("acl.role.{$name}") ?? str($name)->headline()->value();
    }

    public function getLabelAttribute(): string
    {
        return $this->getLabel();
    }
}
