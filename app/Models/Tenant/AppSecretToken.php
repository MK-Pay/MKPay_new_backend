<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read App|null $app
 * @method static \Database\Factories\AppSecretTokenFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSecretToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSecretToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSecretToken onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSecretToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSecretToken withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSecretToken withoutTrashed()
 * @mixin \Eloquent
 */
class AppSecretToken extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'app_id',
        'name',
        'permissions',
        'is_active',
        'last_used_at',
        'expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'token_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'app_id',
                'name',
                'permissions',
                'is_active',
                'last_used_at',
                'expires_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\AppSecretTokenFactory
    {
        return \Database\Factories\AppSecretTokenFactory::new();
    }

    /**
     * Get the app that owns the secret token.
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
