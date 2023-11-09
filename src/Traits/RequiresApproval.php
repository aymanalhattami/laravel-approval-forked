<?php

namespace Approval\Traits;

use Approval\Enums\ActionEnum;
use Approval\Models\Modification;
use Approval\Models\ModificationRelation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

trait RequiresApproval
{
    /**
     * Number of approvers this model requires in order
     * to mark the modifications as accepted.
     *
     * @var int
     */
    protected $approversRequired = 1;

    /**
     * Number of disapprovers this model requires in order
     * to mark the modifications as rejected.
     *
     * @var int
     */
    protected $disapproversRequired = 1;

    /**
     * Boolean to mark whether or not this model should be updated
     * automatically upon receiving the required number of approvals.
     *
     * @var bool
     */
    protected $updateWhenApproved = true;

    /**
     * Boolean to mark whether or not the approval model should be deleted
     * automatically when the approval is disapproved wtih the required number
     * of disapprovals.
     *
     * @var bool
     */
    protected $deleteWhenDisapproved = false;

    /**
     * Boolean to mark whether or not the approval model should be deleted
     * automatically when the approval is approved wtih the required number
     * of approvals.
     *
     * @var bool
     */
    protected $deleteWhenApproved = false;

    /**
     * Boolean to mark whether or not the approval model should be saved
     * forcefully.
     *
     * @var bool
     */
    private $forcedApprovalUpdate = false;

    /**
     * Boot the RequiresApproval trait. Listen for events and perform logic.
     */
    public static function bootRequiresApproval()
    {
        static::saving(function ($item) {
            if (!$item->isForcedApprovalUpdate() && $item->requiresApprovalWhen($item->getDirty()) === true) {
                return self::captureSave($item);
            }

            $item->setForcedApprovalUpdate(false);

            return true;
        });
    }

    /**
     * Returns true if the model is being force updated.
     *
     * @return bool
     */
    public function isForcedApprovalUpdate()
    {
        return $this->forcedApprovalUpdate;
    }

    /**
     * Setter for forcedApprovalUpdate.
     *
     * @return bool
     */
    public function setForcedApprovalUpdate($forced = true)
    {
        return $this->forcedApprovalUpdate = $forced;
    }

    /**
     * Function that defines the rule of when an approval process
     * should be actioned for this model.
     *
     * @param array $modifications
     *
     * @return bool
     */
    protected function requiresApprovalWhen($modifications): bool
    {
        return true;
    }

    public static function captureSave($item)
    {
        $diff = collect($item->getDirty())
            ->transform(function ($change, $key) use ($item) {
                return [
                    'original' => $item->getOriginal($key),
                    'modified' => $item->$key,
                ];
            })->all();

        $hasModificationPending = $item->modifications()
            ->activeOnly()
            ->where('md5', md5(json_encode($diff)))
            ->first();

        $modifier = $item->modifier();

        $modificationModel = config('approval.models.modification', Modification::class);

        $modification = $hasModificationPending ?? new $modificationModel();
        $modification->active = true;
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
            $modification->is_update = false;
        }

        $modification->save();

        if (!$hasModificationPending) {
            $item->modifications()->save($modification);
        }

        return false;
    }

    /**
     * Return RegisterModification relations via moprhMany.
     *
     * @return MorphMany
     */
    public function modifications()
    {
        return $this->morphMany(config('approval.models.modification', Modification::class), 'modifiable');
    }

    /**
     * Returns the model that should be used as the modifier of the modified model.
     *
     * @return mixed
     */
    protected function modifier()
    {
        return auth()->user();
    }

    /**
     * Return collection of creations for the current model
     *
     * @return Collection
     */
    public static function creations()
    {
        $modificationClass = config('approval.models.modification', Modification::class);
        return $modificationClass::whereModifiableType(static::class)->whereIsUpdate(false)->get();
    }

    /**
     * Apply modification to model.
     *
     * @return void
     */
    public function applyModificationChanges(Modification $modification, bool $approved)
    {
        if ($approved && $this->updateWhenApproved) {
            DB::transaction(function () use ($modification, $approved) {
                $this->setForcedApprovalUpdate(true);

                foreach ($modification->modifications as $key => $mod) {
                    $this->{$key} = $mod['modified'];
                }

                $this->save();

                if ($this->deleteWhenApproved) {
                    $modification->delete();
                } else {
                    $modification->active = false;
                    $modification->save();
                }

                $this->saveModificationRelations($modification);
                $this->saveModificationMedia($modification);
            });
        } elseif ($approved === false) {
            if ($this->deleteWhenDisapproved) {
                $modification->delete();
            } else {
                $modification->active = false;
                $modification->save();
            }
        }
    }

    public function saveModificationRelations($modification): void
    {
        if ($modification->modificationRelations()->exists()) {

            $relations = $modification->modificationRelations->groupBy('action');

            foreach ($relations as $key => $modificationRelations) {
                if ($key == ActionEnum::Create->value) {
                    $this->createModificationRelation($modification, $modificationRelations);
                } elseif ($key == ActionEnum::Update->value) {
                    $this->updateModificationRelation($modification, $modificationRelations);
                } elseif ($key == ActionEnum::Delete->value) {
                    $this->deleteModificationRelation($modification, $modificationRelations);
                } elseif ($key == ActionEnum::UpdateOrCreate->value) {
                    $this->updateOrCreateModificationRelation($modification, $modificationRelations);
                } elseif ($key == ActionEnum::DeleteThenCreate->value) {
                    $this->deleteThenCreateModificationRelation($modification, $modificationRelations);
                } elseif ($key == ActionEnum::MorphUpdateOrCreate->value) {
                    $this->morphUpdateOrCreateModificationRelation($modification, $modificationRelations);
                } elseif ($key == ActionEnum::MorphDeleteThenCreate->value) {
                    $this->morphDeleteThenCreateModificationRelation($modification, $modificationRelations);
                } else {
                    $this->createModificationRelation($modification, $modificationRelations);
                }
            }
        }
    }

    public function createModificationRelation($modification, $modificationRelations): void
    {
        foreach ($modificationRelations as $modificationRelation) {
            $modificationRelationModel = new $modificationRelation->model;
            $modificationRelationModel->setForcedApprovalUpdate(true);

            foreach ($modificationRelation->modifications as $key => $value) {
                $modificationRelationModel->{$key} = $value['modified'];
            }

            $modificationRelationModel->{$modificationRelation->foreign_id_column} = $this->id;
            $modificationRelationModel->save();

            // save media
            $this->saveModificationRelationMedia($modificationRelation, $modificationRelationModel);
        }
    }

    public function updateModificationRelation($modification, $modificationRelations): void
    {
        foreach ($modificationRelations as $modificationRelation) {
            $modificationRelationModelQuery = $modificationRelation->model::query()
                ->where($modificationRelation->foreign_id_column, $this->id);

//            if(count($modificationRelation->condition_columns)){
//                foreach ($modificationRelation->condition_columns as $column){
//                    $modificationRelationModelQuery->where($column, $modificationRelation->modifications[$column]['modified']);
//                }
//            }

            $modificationRelationModel = $modificationRelationModelQuery->first();

            if (!$modificationRelationModel) {
                continue;
            }

            $modificationRelationModel->setForcedApprovalUpdate(true);

            foreach ($modificationRelation->modifications as $key => $value) {
                $modificationRelationModel->{$key} = $value['modified'];
            }

            $modificationRelationModel->save();

            // save media
            $this->saveModificationRelationMedia($modificationRelation, $modificationRelationModel);
        }
    }

    public function deleteModificationRelation($modification, $modificationRelations): void
    {
        foreach ($modificationRelations as $modificationRelation) {
            $modificationRelationModel = $modificationRelation->model::query()
                ->where($modificationRelation->foreign_id_column, $this->id)
                ->first();

            if ($modificationRelationModel) {
                $modificationRelationModel->delete();
            }
        }
    }

    public function updateOrCreateModificationRelation($modification, $modificationRelations): void
    {
        foreach ($modificationRelations as $modificationRelation) {
            $modificationRelationModelQuery = $modificationRelation->model::query()
                ->where($modificationRelation->foreign_id_column, $this->id);

//            if(count($modificationRelation->condition_columns)){
//                foreach ($modificationRelation->condition_columns as $column){
//                    $modificationRelationModelQuery->where($column, $modificationRelation->modifications[$column]['modified']);
//                }
//            }

            $modificationRelationModel = $modificationRelationModelQuery->first();

            if (!$modificationRelationModel) {
                $modificationRelationModel = new $modificationRelation->model;
            }

            $modificationRelationModel->setForcedApprovalUpdate(true);

            foreach ($modificationRelation->modifications as $key => $value) {
                $modificationRelationModel->{$key} = $value['modified'];
            }

            $modificationRelationModel->{$modificationRelation->foreign_id_column} = $this->id;
            $modificationRelationModel->save();

            // save media
            $this->saveModificationRelationMedia($modificationRelation, $modificationRelationModel);
        }
    }

    public function deleteThenCreateModificationRelation($modification, $modificationRelations): void
    {
        $modificationRelations->each(function ($modificationRelation) {
            $modificationRelationModel = $modificationRelation->model::query()
                ->where($modificationRelation->foreign_id_column, $this->id)
                ->first();

            if ($modificationRelationModel) {
                $modificationRelationModel->delete();
            }
        });

        foreach ($modificationRelations as $modificationRelation) {
            if (count($modificationRelation->modifications)) {
                $modificationRelationModel = new $modificationRelation->model;
                $modificationRelationModel->setForcedApprovalUpdate(true);

                foreach ($modificationRelation->modifications as $key => $value) {
                    $modificationRelationModel->{$key} = $value['modified'];
                }

                $modificationRelationModel->{$modificationRelation->foreign_id_column} = $this->id;
                $modificationRelationModel->save();

                // save media
                $this->saveModificationRelationMedia($modificationRelation, $modificationRelationModel);
            }

        }
    }

    public function morphUpdateOrCreateModificationRelation($modification, $modificationRelations): void
    {
        foreach ($modificationRelations as $modificationRelation) {
            $modificationRelationModel = $modificationRelation->model::query()
                ->where($modificationRelation->foreign_id_column, $this->id)
                ->where($modificationRelation->model_type_column, static::class)
//                ->where('role_id', $modificationRelation->modifications['role_id']['modified'])
                ->first();

            if (!$modificationRelationModel) {
                $modificationRelationModel = new $modificationRelation->model;
            }

            $modificationRelationModel->setForcedApprovalUpdate(true);

            foreach ($modificationRelation->modifications as $key => $value) {
                $modificationRelationModel->{$key} = $value['modified'];
            }

            $modificationRelationModel->{$modificationRelation->foreign_id_column} = $this->id;
            $modificationRelationModel->save();

            // save media
            $this->saveModificationRelationMedia($modificationRelation, $modificationRelationModel);
        }
    }

    public function morphDeleteThenCreateModificationRelation($modification, $modificationRelations): void
    {
        $modificationRelations->each(function ($modificationRelation) {
            $modificationRelation->model::where([
                $modificationRelation->model_type_column => static::class,
                $modificationRelation->foreign_id_column => $this->id
            ])->delete();
        });

        foreach ($modificationRelations as $modificationRelation) {
            if (count($modificationRelation->modifications)) {
                $modificationRelationModel = new $modificationRelation->model;
                $modificationRelationModel->setForcedApprovalUpdate(true);

                foreach ($modificationRelation->modifications as $key => $value) {
                    $modificationRelationModel->{$key} = $value['modified'];
                }
                $modificationRelationModel->{$modificationRelation->foreign_id_column} = $this->id;
                $modificationRelationModel->save();

                // save media
                $this->saveModificationRelationMedia($modificationRelation, $modificationRelationModel);
            }

        }
    }

    public function saveModificationMedia(Modification $modification): void
    {
        if ($modification->media()->exists()) {
            foreach ($modification->media as $media) {
                $disk = null;
                $directory = null;
                $collectionName = null;

                if ($media->hasCustomProperty('approval_disk')) {
                    $disk = $media->getCustomProperty('approval_disk');
                }

                if ($media->hasCustomProperty('approval_directory')) {
                    $directory = $media->getCustomProperty('approval_directory');
                }

                if ($media->hasCustomProperty('approval_collection_name')) {
                    $collectionName = $media->getCustomProperty('approval_collection_name');
                }

                $media->copy($this, $collectionName, $disk);
            }
        }
    }

    public function saveModificationRelationMedia(ModificationRelation $modificationRelation, $model): void
    {
        if ($modificationRelation->media()->exists()) {
            foreach ($modificationRelation->media as $media) {
                $disk = null;
                $directory = null;
                $collectionName = null;

                if ($media->hasCustomProperty('approval_disk')) {
                    $disk = $media->getCustomProperty('approval_disk');
                }

                if ($media->hasCustomProperty('approval_directory')) {
                    $directory = $media->getCustomProperty('approval_directory');
                }

                if ($media->hasCustomProperty('approval_collection_name')) {
                    $collectionName = $media->getCustomProperty('approval_collection_name');
                }

                $media->copy($model, $collectionName, $disk);
            }
        }
    }
}
