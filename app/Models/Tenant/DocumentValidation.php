<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Account|null $account
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation withoutTrashed()
 * @mixin \Eloquent
 */
class DocumentValidation extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'document_type',
        'document_number',
        'status',
        'validation_data',
        'rejection_reason',
        'validated_by',
        'validated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'validation_data' => 'array',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * Get the account that owns the document validation.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
