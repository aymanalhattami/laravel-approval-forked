<?php

namespace Approval\Traits;

use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

trait HasMedia
{
    protected array $files = [];

    protected string $disk = 'public';
    protected string $directory = '';
    protected string $mediaCollectionName = 'modification';

    protected string $approvalDisk = 'public';
    protected string $approvalDirectory = '';
    protected string $approvalMediaCollectionName = 'approval';

    public function setFiles(array $media): self
    {
        $this->files = $media;

        return $this;
    }

    public function getFiles(): array
    {
        return $this->files;
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

    public function setMediaCollectionName(string $mediaCollectionName): self
    {
        $this->mediaCollectionName = $mediaCollectionName;

        return $this;
    }

    public function getMediaCollectionName(): string
    {
        return $this->mediaCollectionName;
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

    public function setApprovalDirectory(string $approvalDirectory): self
    {
        $this->approvalDirectory = $approvalDirectory;

        return $this;
    }

    public function getApprovalDirectory(): string
    {
        return $this->approvalDirectory;
    }

    public function setApprovalMediaCollectionName(string $approvalMediaCollectionName): self
    {
        $this->approvalMediaCollectionName = $approvalMediaCollectionName;

        return $this;
    }

    public function getApprovalMediaCollectionName(): string
    {
        return $this->approvalMediaCollectionName;
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function saveFiles(): self
    {
        foreach ($this->getFiles() as $key => $file){
            $this->getMediaModel()
                ->addMedia($file->getRealPath())
                ->withCustomProperties([
                    'approval_disk' => $this->getApprovalDisk(),
                    'approval_directory' => $this->getApprovalDirectory(),
                    'approval_collection_name' => $this->getApprovalMediaCollectionName()
                ])
                ->usingName($file->getClientOriginalName())
                ->toMediaCollection($this->getMediaCollectionName(), $this->getDisk());
        }

        return $this;
    }
}
