<?php

namespace Approval\Traits;

use Approval\Enums\MediaActionEnum;
use Approval\Models\Modification;
use Approval\Models\ModificationMedia;
use Approval\Models\ModificationRelation;
use Closure;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

trait HasMedia
{
    private array|Closure $files = [];
    private Modification|ModificationRelation $model;

    private string $disk = 'public';
    private string $directory = '';
    private string $mediaCollectionName = 'modification';

    private string $approvalDisk = 'public';
    private string $approvalDirectory = '';
    private string $approvalMediaCollectionName = 'approval';
    private Closure|MediaActionEnum $action = MediaActionEnum::Create;

    public static function make(): static
    {
        return new static;
    }

    private function getAction(): MediaActionEnum
    {
        return $this->action instanceof Closure ? ($this->action)() : $this->action;
    }

    public function setAction(Closure|MediaActionEnum $action): static
    {
        $this->action = $action;

        return $this;
    }

    private function getModel(): Modification|ModificationRelation
    {
        return $this->model;
    }

    public function setModel(Modification|ModificationRelation $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function setFiles(Closure|array $files): static
    {
        $this->files = $files;

        return $this;
    }

    private function getFiles(): array
    {
        return $this->files instanceof Closure ? ($this->files)() : $this->files;
    }

    public function setDisk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    private function getDisk(): string
    {
        return $this->disk;
    }

    public function setDirectory(string $directory): self
    {
        $this->directory = $directory;

        return $this;
    }

    private function getDirectory(): string
    {
        return $this->directory;
    }

    public function setMediaCollectionName(string $mediaCollectionName): static
    {
        $this->mediaCollectionName = $mediaCollectionName;

        return $this;
    }

    private function getMediaCollectionName(): string
    {
        return $this->mediaCollectionName;
    }

    public function setApprovalDisk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    private function getApprovalDisk(): string
    {
        return $this->disk;
    }

    public function setApprovalDirectory(string $approvalDirectory): static
    {
        $this->approvalDirectory = $approvalDirectory;

        return $this;
    }

    private function getApprovalDirectory(): string
    {
        return $this->approvalDirectory;
    }

    public function setApprovalMediaCollectionName(string $approvalMediaCollectionName): static
    {
        $this->approvalMediaCollectionName = $approvalMediaCollectionName;

        return $this;
    }

    private function getApprovalMediaCollectionName(): string
    {
        return $this->approvalMediaCollectionName;
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function save(): static
    {
        DB::transaction(function () {
            if ($this->getAction() == MediaActionEnum::Delete) {
                ModificationMedia::create([
                    'media_id' => null,
                    'model_id' => $this->getModel()->id,
                    'model_type' => $this->getModel()::class,
                    'action' => $this->getAction()->value
                ]);
            } else {
                foreach ($this->getFiles() as $key => $file) {
                    # TODO:: Check that file is an instance of UploadedFile class
                    $media = $this->getModel()
                        ->addMedia($file->getRealPath())
                        ->withCustomProperties([
                            'approval_disk' => $this->getApprovalDisk(),
                            'approval_directory' => $this->getApprovalDirectory(),
                            'approval_collection_name' => $this->getApprovalMediaCollectionName(),
                        ])
                        ->usingName($file->getClientOriginalName())
                        ->toMediaCollection($this->getMediaCollectionName(), $this->getDisk());

                    ModificationMedia::create([
                        'media_id' => $media?->id,
                        'model_id' => $this->getModel()->id,
                        'model_type' => $this->getModel()::class,
                        'action' => $this->getAction()->value
                    ]);
                }
            }
        });

        return $this;
    }
}
