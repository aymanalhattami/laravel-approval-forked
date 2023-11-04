<?php

namespace Approval\Models;

use Illuminate\Database\Eloquent\Model;

class Modification extends Model
{
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
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function modifiable()
    {
        return $this->morphTo();
    }

    /**
     * Get models that the ignited this modification.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function modifier()
    {
        return $this->morphTo();
    }

    /**
     * Return Approval relations via direct relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function approvals()
    {
        return $this->hasMany(config('approval.models.approval', \Approval\Models\Approval::class));
    }

    /**
     * Return Disapproval relations via direct relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function disapprovals()
    {
        return $this->hasMany(config('approval.models.disapproval', \Approval\Models\Disapproval::class));
    }

    public function modificationRelations()
    {
        return $this->hasMany(config('approval.models.modificationRelation', \Approval\Models\ModificationRelation::class));
    }

    /**
     * Get the number of approvals reamaining for the changes
     * to be approved and approval will close.
     *
     * @return int
     */
    public function getApproversRemainingAttribute()
    {
        return $this->approvers_required - $this->approvals()->count();
    }

    /**
     * Get the number of disapprovals reamaining for the changes
     * to be disapproved and approval will close.
     *
     * @return int
     */
    public function getDisapproversRemainingAttribute()
    {
        return $this->disapprovers_required - $this->disapprovals()->count();
    }

    /**
     * Convenience alias of ApproversRemaining attribute.
     *
     * @return int
     */
    public function getApprovalsRemainingAttribute()
    {
        return $this->approversRemaining;
    }

    /**
     * Convenience alias of DisapproversRemaining attribute.
     *
     * @return int
     */
    public function getDisapprovalsRemainingAttribute()
    {
        return $this->disapproversRemaining;
    }

    /**
     * Force apply changes to modifiable.
     *
     * @return void
     */
    public function forceApprovalUpdate()
    {
        $this->modifiable->applyModificationChanges($this, true);
    }

    /**
     * Scope to only include active modifications.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveOnly($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to only include inactive modifications.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactiveOnly($query)
    {
        return $query->where('active', false);
    }

    /**
     * Scope to only retrieve changed models.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChanges($query)
    {
        return $query->where('is_update', true);
    }

    /**
     * Scope to only retrieve created models.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreations($query)
    {
        return $query->where('is_update', false);
    }

    public function createModificationRelation(array $data): ModificationRelation
    {
        $modifiedData = [];

        foreach ($data['modifications'] as $key => $value){
            $modifiedData[$key] = ['modified' => $value, 'original' => null];
        }

        return ModificationRelation::create([
            'modification_id' => $this->id,
            'model' => $data['model'],
            'model_relation_column' => $data['model_relation_column'],
            'modifications' => $modifiedData,

        ]);
    }
}
