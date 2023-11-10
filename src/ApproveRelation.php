<?php

namespace Approval;

use Approval\Enums\ActionEnum;
use Approval\Models\Modification;
use Illuminate\Database\Eloquent\Model;

class ApproveRelation
{
    private Modification $modification;
    private Model $model;

    public static function make(): static
    {
        return new static;
    }

    public function getModification(): Modification
    {
        return $this->modification;
    }

    public function setModification(Modification $modification): static
    {
        $this->modification = $modification;

        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function setModel(Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function save(): void
    {
        if ($this->getModification()->modificationRelations()->exists()) {

            $relations = $this->getModification()->modificationRelations->groupBy('action');

            foreach ($relations as $key => $modificationRelations) {
                if ($key == ActionEnum::Create->value) {
                    $this->create($modificationRelations);
                } elseif ($key == ActionEnum::Update->value) {
                    $this->update($modificationRelations);
                } elseif ($key == ActionEnum::Delete->value) {
                    $this->delete($modificationRelations);
                } elseif ($key == ActionEnum::UpdateOrCreate->value) {
                    $this->updateOrCreate($modificationRelations);
                } elseif ($key == ActionEnum::DeleteThenCreate->value) {
                    $this->deleteThenCreate($modificationRelations);
                } else {
                    $this->create($modificationRelations);
                }
            }
        }
    }

    public function create($modificationRelations): void
    {
        foreach ($modificationRelations as $modificationRelation) {
            $modificationRelationModel = new $modificationRelation->model;
            $modificationRelationModel->setForcedApprovalUpdate(true);

            foreach ($modificationRelation->modifications as $key => $value) {
                $modificationRelationModel->{$key} = $value['modified'];
            }

            $modificationRelationModel->{$modificationRelation->foreign_id_column} = $this->getModel()->id;
            $modificationRelationModel->save();

            // save media
//            $this->saveModificationRelationMedia($modificationRelation, $modificationRelationModel);
        }
    }

//    public function saveModificationRelationMedia(ModificationRelation $modificationRelation, $model): void
//    {
//        if ($modificationRelation->media()->exists()) {
//            foreach ($modificationRelation->media as $media) {
//                $disk = null;
//                $directory = null;
//                $collectionName = null;
//
//                if ($media->hasCustomProperty('approval_disk')) {
//                    $disk = $media->getCustomProperty('approval_disk');
//                }
//
//                if ($media->hasCustomProperty('approval_directory')) {
//                    $directory = $media->getCustomProperty('approval_directory');
//                }
//
//                if ($media->hasCustomProperty('approval_collection_name')) {
//                    $collectionName = $media->getCustomProperty('approval_collection_name');
//                }
//
//                $media->copy($model, $collectionName, $disk);
//            }
//        }
//    }

    public function update($modificationRelations): void
    {
        foreach ($modificationRelations as $modificationRelation) {
            $modificationRelationModelQuery = $modificationRelation->model::query()
                ->where($modificationRelation->foreign_id_column, $this->getModel()->id);

            if(count($modificationRelation->condition_columns)){
                foreach ($modificationRelation->condition_columns as $column => $value) {
                    $modificationRelationModelQuery->where($column, $value);
                }
            }

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
//            $this->saveModificationRelationMedia($modificationRelation, $modificationRelationModel);
        }
    }

    public function delete($modificationRelations): void
    {
        foreach ($modificationRelations as $modificationRelation) {
            $modificationRelationModel = $modificationRelation->model::query()
                ->where($modificationRelation->foreign_id_column, $this->getModel()->id)
                ->first();

            if ($modificationRelationModel) {
                $modificationRelationModel->delete();
            }
        }
    }

    public function updateOrCreate($modificationRelations): void
    {
        foreach ($modificationRelations as $modificationRelation) {
            $modificationRelationModelQuery = $modificationRelation->model::query()
                ->where($modificationRelation->foreign_id_column, $this->getModel()->id);

            if(count($modificationRelation->condition_columns)){
                foreach ($modificationRelation->condition_columns as $column => $value) {
                    $modificationRelationModelQuery->where($column, $value);
                }
            }

            $modificationRelationModel = $modificationRelationModelQuery->first();

            if (!$modificationRelationModel) {
                $modificationRelationModel = new $modificationRelation->model;
            }

            $modificationRelationModel->setForcedApprovalUpdate(true);

            foreach ($modificationRelation->modifications as $key => $value) {
                $modificationRelationModel->{$key} = $value['modified'];
            }

            $modificationRelationModel->{$modificationRelation->foreign_id_column} = $this->getModel()->id;
            $modificationRelationModel->save();

            // save media
//            $this->saveModificationRelationMedia($modificationRelation, $modificationRelationModel);
        }
    }

    public function deleteThenCreate($modificationRelations): void
    {
        DB::transaction(function() use($modificationRelations){
            $modificationRelations->each(function ($modificationRelation) {
                $modificationRelationQuery = $modificationRelation->model::query()
                    ->where($modificationRelation->foreign_id_column, $this->getModel()->id);

                if(count($modificationRelation->condition_columns)){
                    foreach ($modificationRelation->condition_columns as $column => $value){
                        $modificationRelationQuery->where($column, $value);
                    }
                }

                $modificationRelationQuery->delete();
            });

            foreach ($modificationRelations as $modificationRelation) {
                if (count($modificationRelation->modifications)) {
                    $modificationRelationModel = new $modificationRelation->model;
                    $modificationRelationModel->setForcedApprovalUpdate(true);

                    foreach ($modificationRelation->modifications as $key => $value) {
                        $modificationRelationModel->{$key} = $value['modified'];
                    }
                    $modificationRelationModel->{$modificationRelation->foreign_id_column} = $this->getModel()->id;
                    $modificationRelationModel->save();

                    // save media
//                    $this->saveModificationRelationMedia($modificationRelation, $modificationRelationModel);
                }
            }
        });
    }
}