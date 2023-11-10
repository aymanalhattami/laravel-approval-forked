<?php

namespace Approval;

use Approval\Enums\ActionEnum;
use Approval\Models\Modification;
use Approval\Models\ModificationRelation;

class RegisterModificationRelation
{
    private string $modelName;
    private array $data = [];
    private Modification $modification;
    private ModificationRelation $modificationRelation;
    private string $foreignIdColumn;
    private ActionEnum $action = ActionEnum::Create;
    private array $conditionColumns = [];

    public static function make(): self
    {
        return new static;
    }

    private function getConditionColumns(): array
    {
        return $this->conditionColumns;
    }

    public function setConditionColumns(array $conditionColumns): static
    {
        $this->conditionColumns = $conditionColumns;

        return $this;
    }

    private function getAction(): ActionEnum
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

    private function getModification(): Modification
    {
        return $this->modification;
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

    private function getModelName(): string
    {
        return $this->modelName;
    }

    public function setForeignIdColumn(string $foreignIdColumn): static
    {
        $this->foreignIdColumn = $foreignIdColumn;

        return $this;
    }

    private function getForeignIdColumn(): string
    {
        return $this->foreignIdColumn;
    }

    public function setData(array $data = []): self
    {
        $this->data = $data;

        return $this;
    }

    private function getData(): array
    {
        return $this->data;
    }

    private function getModifiedData(): array
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
            'condition_columns' => $this->getConditionColumns(),
        ]);

        return $this;
    }
}
