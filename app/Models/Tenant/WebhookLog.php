<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Webhook|null $webhook
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog query()
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
