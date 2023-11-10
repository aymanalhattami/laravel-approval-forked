<?php

namespace Approval\Traits;

use Approval\Models\Approval;
use Illuminate\Support\Facades\DB;

trait ApprovesChanges
{
    /**
     * Defines if this model is allowed to cast their approval
     * should be actioned for this model.
     */
    protected function authorizedToApprove(/* @scrutinizer ignore-unused */ \Approval\Models\Modification $modification): bool
    {
        return true;
    }

    /**
     * Defines if this model is allowed to cast their disapproval
     * should be actioned for this model.
     */
    protected function authorizedToDisapprove(/* @scrutinizer ignore-unused */ \Approval\Models\Modification $modification): bool
    {
        return true;
    }

    /**
     * Approve a modification.
     */
    public function approve(\Approval\Models\Modification $modification, string $reason = null): bool
    {
        if ($this->authorizedToApprove($modification)) {
            DB::transaction(function () use ($modification, $reason) {
                // Prevent disapproving and approving
                if ($disapproval = $this->disapprovals()->where([
                    'disapprover_id' => $this->{$this->primaryKey},
                    'disapprover_type' => get_class(),
                    'modification_id' => $modification->id,
                ])->first()) {
                    $disapproval->delete();
                }

                // Prevent duplicates
                $approvalModel = config('approval.models.approval', Approval::class);
                $approvalModel::firstOrCreate([
                    'approver_id' => $this->{$this->primaryKey},
                    'approver_type' => get_class(),
                    'modification_id' => $modification->id,
                    'reason' => $reason,
                ]);

                $modification->fresh();

                if ($modification->approversRemaining == 0) {
                    if ($modification->modifiable_id === null) {
                        $polymorphicModel = new $modification->modifiable_type();
                        $polymorphicModel->applyModificationChanges($modification, true);
                    } else {
                        $modification->modifiable->applyModificationChanges($modification, true);
                    }
                }
            });

            return true;
        }

        return false;
    }

    /**
     * Disapprove a modification.
     */
    public function disapprove(\Approval\Models\Modification $modification, string $reason = null): bool
    {
        if ($this->authorizedToDisapprove($modification)) {

            // Prevent approving and disapproving
            if ($approval = $this->approvals()->where([
                'approver_id' => $this->{$this->primaryKey},
                'approver_type' => get_class(),
                'modification_id' => $modification->id,
            ])->first()) {
                $approval->delete();
            }

            // Prevent duplicates
            $disapprovalModel = config('approval.models.disapproval', \Approval\Models\Disapproval::class);
            $disapprovalModel::firstOrCreate([
                'disapprover_id' => $this->{$this->primaryKey},
                'disapprover_type' => get_class(),
                'modification_id' => $modification->id,
                'reason' => $reason,
            ]);

            $modification->fresh();

            if ($modification->disapproversRemaining == 0) {
                if ($modification->modifiable_id === null) {
                    $polymorphicModel = new $modification->modifiable_type();
                    $polymorphicModel->applyModificationChanges($modification, false);
                } else {
                    $modification->modifiable->applyModificationChanges($modification, false);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Return Approval relations via moprhMany.
     */
    public function approvals(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->/* @scrutinizer ignore-call */ morphMany(Approval::class, 'approver');
    }

    /**
     * Return Disapproval relations via moprhMany.
     */
    public function disapprovals(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->/* @scrutinizer ignore-call */ morphMany(\Approval\Models\Disapproval::class, 'disapprover');
    }
}
