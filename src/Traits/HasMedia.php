<?php

namespace Approval\Traits;

use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

trait HasMedia
{
    protected array $files = [];

    protected string $disk = 'public';
    protected string $directory = '';
    protected string $mediaCollection = 'modification';

    protected string $approvalDisk = 'public';
    protected string $approvalDirectory = '';
    protected string $approvalMediaCollection = 'approval';

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
    public function saveFiles(): self
    {
        foreach ($this->getFiles() as $key => $file){
            $this->getMediaModel()
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