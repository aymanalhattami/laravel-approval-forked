<?php

namespace Approval\Traits;

use Approval\ApproveMedia;
use Approval\ApproveModificationRelation;
use Approval\Enums\ActionEnum;
use Approval\Enums\ModificationStatusEnum;
use Approval\Models\Modification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

trait RequiresApproval
{
    /**
     * Number of approvers this model requires in order
     * to mark the modifications as accepted.
     */
    protected int $approversRequired = 1;

    /**
     * Number of disapprovers this model requires in order
     * to mark the modifications as rejected.
     */
    protected int $disapproversRequired = 1;

    /**
     * Boolean to mark whether or not this model should be updated
     * automatically upon receiving the required number of approvals.
     */
    protected bool $updateWhenApproved = true;

    /**
     * Boolean to mark whether or not the approval model should be deleted
     * automatically when the approval is disapproved wtih the required number
     * of disapprovals.
     */
    protected bool $deleteWhenDisapproved = false;

    /**
     * Boolean to mark whether or not the approval model should be deleted
     * automatically when the approval is approved wtih the required number
     * of approvals.
     */
    protected bool $deleteWhenApproved = false;

    /**
     * Boolean to mark whether or not the approval model should be saved
     * forcefully.
     */
    private bool $forcedApprovalUpdate = false;

    /**
     * Boot the RequiresApproval trait. Listen for events and perform logic.
     */
    public static function bootRequiresApproval(): void
    {
        static::saving(function ($item) {
            if (! $item->isForcedApprovalUpdate() && $item->requiresApprovalWhen($item->getDirty()) === true) {
                return self::captureSave($item);
            }

            $item->setForcedApprovalUpdate(false);

            return true;
        });
    }

    /**
     * Returns true if the model is being force updated.
     */
    public function isForcedApprovalUpdate(): bool
    {
        return $this->forcedApprovalUpdate;
    }

    /**
     * Setter for forcedApprovalUpdate.
     */
    public function setForcedApprovalUpdate($forced = true): bool
    {
        return $this->forcedApprovalUpdate = $forced;
    }

    /**
     * Function that defines the rule of when an approval process
     * should be actioned for this model.
     *
     * @param  array  $modifications
     */
    protected function requiresApprovalWhen($modifications): bool
    {
        return true;
    }

    public static function captureSave($item): false
    {
        $diff = collect($item->getDirty())
            ->transform(function ($change, $key) use ($item) {
                return [
                    'original' => $item->getOriginal($key),
                    'modified' => $item->$key,
                ];
            })->all();

        $hasModificationPending = $item->modifications()
            ->pending()
            ->where('md5', md5(json_encode($diff)))
            ->first();

        $modifier = $item->modifier();

        $modificationModel = config('approval.models.modification', Modification::class);

        $modification = $hasModificationPending ?? new $modificationModel();
        $modification->action = ModificationStatusEnum::Pending->value;
        $modification->modifications = $diff;
        $modification->approvers_required = $item->approversRequired;
        $modification->disapprovers_required = $item->disapproversRequired;
        $modification->md5 = md5(json_encode($diff));

        if ($modifier && ($modifierClass = get_class($modifier))) {
            $modifierInstance = new $modifierClass();

            $modification->modifier_id = $modifier->{$modifierInstance->getKeyName()};
            $modification->modifier_type = $modifierClass;
        }

        if (is_null($item->{$item->getKeyName()})) {
            $modification->action = ActionEnum::Create;
        }

        $modification->save();

        if (! $hasModificationPending) {
            $item->modifications()->save($modification);
        }

        return false;
    }

    /**
     * Return RegisterModification relations via moprhMany.
     */
    public function modifications(): MorphMany
    {
        return $this->morphMany(config('approval.models.modification', Modification::class), 'modifiable');
    }

    /**
     * Returns the model that should be used as the modifier of the modified model.
     */
    protected function modifier(): mixed
    {
        return auth()->user();
    }

    /**
     * Return collection of creations for the current model
     */
    public static function creations(): Collection
    {
        $modificationClass = config('approval.models.modification', Modification::class);

        return $modificationClass::whereModifiableType(static::class)->whereAction(ActionEnum::Create->value)->get();
    }

    /**
     * Apply modification to model.
     */
    public function applyModificationChanges(Modification $modification, bool $approved): void
    {
        if ($approved && $this->updateWhenApproved) {
            DB::transaction(function () use ($modification) {
                $this->setForcedApprovalUpdate(true);

                foreach ($modification->modifications as $key => $mod) {
                    $this->{$key} = $mod['modified'];
                }

                $this->save();

                if ($this->deleteWhenApproved) {
                    $modification->delete();
                } else {
                    $modification->status = ModificationStatusEnum::Approved->value;
                    $modification->save();
                }

                ApproveModificationRelation::make()
                    ->setModel($this)
                    ->setModification($modification)
                    ->save();
                ApproveMedia::make()
                    ->setModification($modification)
                    ->setModel($this)
                    ->save();
            });
        } elseif ($approved === false) {
            if ($this->deleteWhenDisapproved) {
                $modification->delete();
            } else {
                $modification->status = ModificationStatusEnum::Disapproved->value;
                $modification->save();
            }
        }
    }
}
