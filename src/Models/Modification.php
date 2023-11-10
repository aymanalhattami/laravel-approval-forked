<?php

namespace Approval\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Modification extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * The attributes that can't be filled.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'modifications' => 'json',
    ];

    /**
     * Get models that the modification belongs to.
     */
    public function modifiable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get models that the ignited this modification.
     */
    public function modifier(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Return Approval relations via direct relation.
     */
    public function approvals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('approval.models.approval', \Approval\Models\Approval::class));
    }

    /**
     * Return Disapproval relations via direct relation.
     */
    public function disapprovals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('approval.models.disapproval', \Approval\Models\Disapproval::class));
    }

    public function modificationRelations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('approval.models.modificationRelation', \Approval\Models\ModificationRelation::class));
    }

    /**
     * Get the number of approvals reamaining for the changes
     * to be approved and approval will close.
     */
    public function getApproversRemainingAttribute(): int
    {
        return $this->approvers_required - $this->approvals()->count();
    }

    /**
     * Get the number of disapprovals reamaining for the changes
     * to be disapproved and approval will close.
     */
    public function getDisapproversRemainingAttribute(): int
    {
        return $this->disapprovers_required - $this->disapprovals()->count();
    }

    /**
     * Convenience alias of ApproversRemaining attribute.
     */
    public function getApprovalsRemainingAttribute(): int
    {
        return $this->approversRemaining;
    }

    /**
     * Convenience alias of DisapproversRemaining attribute.
     */
    public function getDisapprovalsRemainingAttribute(): int
    {
        return $this->disapproversRemaining;
    }

    /**
     * Force apply changes to modifiable.
     */
    public function forceApprovalUpdate(): void
    {
        $this->modifiable->applyModificationChanges($this, true);
    }

    /**
     * Scope to only include active modifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeActiveOnly($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope to only include inactive modifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeInactiveOnly($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('active', false);
    }

    /**
     * Scope to only retrieve changed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeChanges($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_update', true);
    }

    /**
     * Scope to only retrieve created models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeCreations($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_update', false);
    }
}
