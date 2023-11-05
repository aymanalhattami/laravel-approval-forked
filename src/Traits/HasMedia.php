<?php

namespace Approval\Traits;

use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

trait HasMedia
{
    protected string $disk = 'public';
    protected string $directory = '';
    protected string $mediaCollection = 'modification';

    protected string $approvalDisk = 'public';
    protected string $approvalDirectory = '';
    protected string $approvalMediaCollection = 'approval';

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