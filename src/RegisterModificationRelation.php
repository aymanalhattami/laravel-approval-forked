<?php

namespace Approval;

use Approval\Models\Modification;
use Approval\Models\ModificationRelation;
use Approval\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;

class RegisterModificationRelation implements \Approval\Contracts\HasMedia
{
    use HasMedia;

    protected string $modelName;
    protected array $data = [];
    protected Modification $modification;
    protected ModificationRelation $modificationRelation;
    protected string $modelForeignId;

    public static function make(): self
    {
        return new static;
    }

    public function setModification(Modification $modification): static
    {
        $this->modification = $modification;

        return $this;
    }

    public function getModification(): Modification
    {
        return $this->modification;
    }

    public function setModificationRelation(ModificationRelation $modificationRelation): self
    {
        $this->modificationRelation = $modificationRelation;

        return $this;
    }

    public function getModificationRelation(): ModificationRelation
    {
        return $this->modificationRelation;
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

    public function setModelForeignId(string $modelForeignId): static
    {
        $this->modelForeignId = $modelForeignId;

        return $this;
    }

    public function getModelForeignId(): string
    {
        return $this->modelForeignId;
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

    public function getMediaModel(): ModificationRelation
    {
        return $this->modificationRelation;
    }

    public function save():self
    {
        $this->modificationRelation = ModificationRelation::create([
            'modification_id' => $this->getModification()->id,
            'model' => $this->getModelName(),
            'model_foreign_id' => $this->getModelForeignId(),
            'modifications' => $this->getModifiedData(),
        ]);

        return $this;
    }
}
