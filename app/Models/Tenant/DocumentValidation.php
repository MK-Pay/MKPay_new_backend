<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $account_id
 * @property string $document_type
 * @property string $document_number
 * @property string $status
 * @property array|null $validation_data
 * @property string|null $rejection_reason
 * @property int|null $validated_by
 * @property \Illuminate\Support\Carbon|null $validated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Account $account
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereValidatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereValidatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation whereValidationData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentValidation withoutTrashed()
 *
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
