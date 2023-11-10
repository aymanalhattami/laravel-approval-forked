<?php

namespace Approval\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    /**
     * The attributes that can't be filled.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get models that the approval belongs to.
     */
    public function approver(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Return RegisterModification relation via direct relation.
     */
    public function modification(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(config('approval.models.modification', \Approval\Models\Modification::class));
    }
}
