<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $account_id
 * @property int|null $app_id
 * @property string $url
 * @property string|null $secret
 * @property array $events
 * @property bool $is_active
 * @property int $retry_count
 * @property int $timeout
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Account $account
 * @property-read App|null $app
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WebhookLog> $logs
 * @property-read int|null $logs_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereEvents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereRetryCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereTimeout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Webhook extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'app_id',
        'url',
        'secret',
        'events',
        'is_active',
        'retry_count',
        'timeout',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'retry_count' => 'integer',
            'timeout' => 'integer',
        ];
    }

    /**
     * Get the account that owns the webhook.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the app that owns the webhook.
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * Get the webhook logs for this webhook.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }
}
