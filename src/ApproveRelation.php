<?php

namespace Approval;

use Approval\Enums\ActionEnum;
use Approval\Models\Modification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

            ApproveMedia::make()
                ->setModification($modificationRelation)
                ->setModel($this->getModel())
                ->save();
        }
    }

    public function update($modificationRelations): void
    {
        foreach ($modificationRelations as $modificationRelation) {
            $modificationRelationModelQuery = $modificationRelation->model::query()
                ->where($modificationRelation->foreign_id_column, $this->getModel()->id);

            if (count($modificationRelation->condition_columns)) {
                foreach ($modificationRelation->condition_columns as $column => $value) {
                    $modificationRelationModelQuery->where($column, $value);
                }
            }

            $modificationRelationModel = $modificationRelationModelQuery->first();

            if (! $modificationRelationModel) {
                continue;
            }

            $modificationRelationModel->setForcedApprovalUpdate(true);

            foreach ($modificationRelation->modifications as $key => $value) {
                $modificationRelationModel->{$key} = $value['modified'];
            }

            $modificationRelationModel->save();

            ApproveMedia::make()
                ->setModification($modificationRelation)
                ->setModel($this->getModel())
                ->save();
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

            if (count($modificationRelation->condition_columns)) {
                foreach ($modificationRelation->condition_columns as $column => $value) {
                    $modificationRelationModelQuery->where($column, $value);
                }
            }

            $modificationRelationModel = $modificationRelationModelQuery->first();

            if (! $modificationRelationModel) {
                $modificationRelationModel = new $modificationRelation->model;
            }

            $modificationRelationModel->setForcedApprovalUpdate(true);

            foreach ($modificationRelation->modifications as $key => $value) {
                $modificationRelationModel->{$key} = $value['modified'];
            }

            $modificationRelationModel->{$modificationRelation->foreign_id_column} = $this->getModel()->id;
            $modificationRelationModel->save();

            ApproveMedia::make()
                ->setModification($modificationRelation)
                ->setModel($this->getModel())
                ->save();
        }
    }

    public function deleteThenCreate($modificationRelations): void
    {
        DB::transaction(function () use ($modificationRelations) {
            $modificationRelations->each(function ($modificationRelation) {
                $modificationRelationQuery = $modificationRelation->model::query()
                    ->where($modificationRelation->foreign_id_column, $this->getModel()->id);

                if (count($modificationRelation->condition_columns)) {
                    foreach ($modificationRelation->condition_columns as $column => $value) {
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

                    ApproveMedia::make()
                        ->setModification($modificationRelation)
                        ->setModel($this->getModel())
                        ->save();
                }
            }
        });
    }
}
