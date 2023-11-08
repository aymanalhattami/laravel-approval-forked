<?php

namespace Approval;

use Approval\Enums\ActionEnum;
use Approval\Models\Modification;
use Approval\Models\ModificationRelation;
use Approval\Traits\HasMedia;

class RegisterModificationRelation
{
    use HasMedia;

    protected string $modelName;
    protected array $data = [];
    protected Modification $modification;
    protected ModificationRelation $modificationRelation;
    protected string $foreignIdColumn;
    protected ActionEnum $action = ActionEnum::Create;
    protected string|null $modelTypeColumn = null;
    protected string|null $modelIdColumn = null;

    public static function make(): self
    {
        return new static;
    }

    public function getModelTypeColumn(): ?string
    {
        return $this->modelTypeColumn;
    }

    public function setModelTypeColumn(?string $modelTypeColumn): static
    {
        $this->modelTypeColumn = $modelTypeColumn;

        return $this;
    }

    public function getModelIdColumn(): ?string
    {
        return $this->modelIdColumn;
    }

    public function setModelIdColumn(?string $modelIdColumn): static
    {
        $this->modelIdColumn = $modelIdColumn;

        return $this;
    }

    public function getAction(): ActionEnum
    {
        return $this->action;
    }

    public function setAction(ActionEnum $action): self
    {
        $this->action = $action;

        return $this;
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

    public function setForeignIdColumn(string $foreignIdColumn): static
    {
        $this->foreignIdColumn = $foreignIdColumn;

        return $this;
    }

    public function getForeignIdColumn(): string
    {
        return $this->foreignIdColumn;
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

    public function save():self
    {
        $this->modificationRelation = ModificationRelation::create([
            'modification_id' => $this->getModification()->id,
            'model' => $this->getModelName(),
            'foreign_id_column' => $this->getForeignIdColumn(),
            'modifications' => $this->getModifiedData(),
            'action' => $this->getAction()->value,
            'model_type_column' => $this->getModelTypeColumn(),
        ]);

        return $this;
    }
}
