<?php

namespace Approval;

use Approval\Models\Modification;
use Approval\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class RegisterModification implements \Approval\Contracts\HasMedia
{
    use HasMedia;

    protected string $modelName;
    protected string|null $modelId = null;
    protected array $data = [];
    protected Modification $modification;
    protected bool $isUpdate = false;

    public static function make(): self
    {
        return new static;
    }

    public function isUpdate(bool $value = true): self
    {
        $this->isUpdate = $value;

        return $this;
    }

    public function getModelId(): string|null
    {
        return $this->modelId;
    }

    public function setModelId(string $modelId): self
    {
        $this->modelId = $modelId;

        return $this;
    }

    public function getModification(): Modification
    {
        return $this->modification;
    }

    public function setModelName(string $modelName): self
    {
        $this->modelName = $modelName;

        return $this;
    }

    public function getModelName(): string
    {
        return $this->modelName;
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

        if(count($this->getData())){
            foreach ($this->getData() as $key => $value){
                $modifiedData[$key] = ['modified' => $value, 'original' => null];
            }
        }

        return $modifiedData;
    }

    public function getMediaModel(): Modification
    {
        return $this->modification;
    }

    public function save():self
    {
        $this->modification = Modification::create([
            'modifiable_type' => $this->getModelName(),
            'modifiable_id' => $this->getModelId(),
            'modifier_id' => Auth::id(),
            'modifier_type' => Auth::user()::class,
            'is_update' => $this->isUpdate,
            'md5' => md5(Carbon::now()->format('Y-m-d-H-i-s')),
            'modifications' => $this->getModifiedData()
        ]);

        return $this;
    }
}
