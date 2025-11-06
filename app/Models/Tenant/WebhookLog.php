<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $webhook_id
 * @property string $event
 * @property string $url
 * @property array $payload
 * @property int|null $http_status
 * @property string|null $response_body
 * @property int $attempt
 * @property bool $success
 * @property string|null $error_message
 * @property int|null $duration_ms
 * @property \Illuminate\Support\Carbon $sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Webhook $webhook
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereAttempt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereDurationMs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereHttpStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereResponseBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereSuccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereWebhookId($value)
 *
 * @mixin \Eloquent
 */
class WebhookLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'webhook_id',
        'event',
        'url',
        'payload',
        'http_status',
        'response_body',
        'attempt',
        'success',
        'error_message',
        'duration_ms',
        'sent_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'http_status' => 'integer',
            'attempt' => 'integer',
            'success' => 'boolean',
            'duration_ms' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Get the webhook that owns this log.
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
