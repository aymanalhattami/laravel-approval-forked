<?php

namespace Approval;

use Approval\Models\Modification;
use Approval\Models\ModificationRelation;
use Approval\Traits\HasMedia;

class RegisterModificationRelation
{
    use HasMedia;

    protected string $model;
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

    public function getModificationRelation(): ModificationRelation
    {
        return $this->modificationRelation;
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

    public function create():self
    {
        $this->modificationRelation = ModificationRelation::create([
            'modification_id' => $this->getModification()->id,
            'model' => $this->getModel(),
            'model_foreign_id' => $this->getModelForeignId(),
            'modifications' => $this->getModifiedData(),
        ]);

        return $this;
    }
}
