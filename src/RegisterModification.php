<?php

namespace Approval;

use Approval\Models\Modification;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class RegisterModification
{
    protected string $model;
    protected array $data = [];
    protected array $media = [];
    protected Modification $modification;
    protected string $disk = 'local';
    protected string $directory = '';
    protected string $mediaCollection = 'modification';

    protected string $approvalDisk = 'local';
    protected string $approvalDirectory = '';
    protected string $approvalMediaCollection = 'approval';

    public static function make(): self
    {
        return new static;
    }

    public function setModification(Modification $modification): self
    {
        $this->modification = $modification;

        return $this;
    }

    public function getModification(): Modification
    {
        return $this->modification;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setData(array $data = []): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getModifiedData(): array
    {
        $modifiedData = [];

        foreach ($this->getData() as $key => $value){
            $modifiedData[$key] = ['modified' => $value, 'original' => null];
        }

        return $modifiedData;
    }

    public function create():self
    {
        $this->modification = Modification::create([
            'modifiable_type' => $this->getModel(),
            'modifier_id' => Auth::id(),
            'modifier_type' => Auth::user()::class,
            'is_update' => false,
            'md5' => md5($this->getModel()),
            'modifications' => $this->getModifiedData()
        ]);

        return $this;
    }

    public function setMedia(array $media): self
    {
        $this->media = $media;

        return $this;
    }

    public function getMedia(): array
    {
        return $this->media;
    }

    public function setDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    public function setDirectory(string $directory): self
    {
        $this->directory = $directory;

        return $this;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function setMediaCollection(string $mediaCollection): self
    {
        $this->mediaCollection = $mediaCollection;

        return $this;
    }

    public function getMediaCollection(): string
    {
        return $this->mediaCollection;
    }

    public function setApprovalDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function getApprovalDisk(): string
    {
        return $this->disk;
    }

    public function setApprovalDirectory(string $directory): self
    {
        $this->directory = $directory;

        return $this;
    }

    public function getApprovalDirectory(): string
    {
        return $this->directory;
    }

    public function setApprovalMediaCollection(string $mediaCollection): self
    {
        $this->mediaCollection = $mediaCollection;

        return $this;
    }

    public function getApprovalMediaCollection(): string
    {
        return $this->mediaCollection;
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function createMedia(): self
    {
        foreach ($this->getMedia() as $key => $file){
            $this->getModification()
                ->addMedia($file->getRealPath())
                ->withCustomProperties([
                    'approval_disk' => $this->getApprovalDisk(),
                    'approval_directory' => $this->getApprovalDirectory(),
                    'approval_collection_name' => $this->getApprovalMediaCollection()
                ])
                ->usingName($file->getClientOriginalName())
                ->toMediaCollection($this->getMediaCollection());
        }

        return $this;
    }
}
