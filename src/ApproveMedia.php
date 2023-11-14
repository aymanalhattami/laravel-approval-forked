<?php

namespace Approval;

use Approval\Enums\MediaActionEnum;
use Approval\Models\Modification;
use Approval\Models\ModificationMedia;
use Approval\Models\ModificationRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ApproveMedia
{
    private Modification|ModificationRelation $modification;

    private Model $model;

    public static function make(): static
    {
        return new static;
    }

    public function getModification(): Modification|ModificationRelation
    {
        return $this->modification;
    }

    public function setModification(Modification|ModificationRelation $modification): static
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

    public function save(): static
    {
        $modificationMedias = ModificationMedia::query()
            ->where('model_id', $this->getModification()->id)
            ->where('model_type', $this->getModification()::class)
            ->get();

        if ($modificationMedias) {
            $groupedModificationMedia = $modificationMedias->groupBy('action');

            if ($groupedModificationMedia) {
                foreach ($groupedModificationMedia as $key => $modificationMedia) {
                    if ($key == MediaActionEnum::Create->value) {
                        $this->create($modificationMedia);
                    } elseif ($key == MediaActionEnum::Delete->value) {
                        $this->delete($modificationMedia);
                    } elseif ($key == MediaActionEnum::DeleteThenCreate->value) {
                        $this->deleteThenCreate($modificationMedia);
                    } else {
                        $this->create($modificationMedia);
                    }
                }
            }
        }

        return $this;
    }

    private function create(array|Collection $modificationMedia): void
    {
        if (count($modificationMedia)) {
            foreach ($modificationMedia as $modificationMediaModel) {
                $media = $modificationMediaModel->media;
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

                $media->copy($this->getModel(), $collectionName, $disk);
            }
        }
    }

    private function delete(array|Collection $modificationMedia): void
    {
        foreach ($modificationMedia as $modificationMediaModel) {
            $mediaQuery = Media::query()
                ->where('model_type', $modificationMediaModel->model->modifiable_type)
                ->where('model_id', $modificationMediaModel->model->modifiable_id);

            if (count($modificationMediaModel->condition_columns)) {
                foreach ($modificationMediaModel->condition_columns as $column => $value) {
                    $mediaQuery->where($column, $value);
                }
            }

            $mediaQuery->delete();
        }
    }

    private function deleteThenCreate(array|Collection $modificationMedia): void
    {
        DB::transaction(function () use ($modificationMedia) {
            $this->delete($modificationMedia);
            $this->create($modificationMedia);
        });
    }
}
