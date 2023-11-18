<?php

namespace Approval;

use Approval\Enums\RelationActionEnum;
use Approval\Models\Modification;
use Approval\Models\ModificationRelation;
use Closure;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateModificationRelation
{
    private string $modelName;

    private Closure|array $data = [];

    private Modification $modification;

    private ModificationRelation $modificationRelation;

    private string|Closure $foreignIdColumn;

    private RelationActionEnum $action = RelationActionEnum::Create;

    private array $conditionColumns = [];

    private array $modificationMedias = [];

    public static function make(): self
    {
        return new static;
    }

    public function getModificationMedias(): array
    {
        return $this->modificationMedias;
    }

    public function setModificationMedias(array $modificationMedias): static
    {
        $this->modificationMedias = $modificationMedias;

        return $this;
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

    private function getAction(): RelationActionEnum
    {
        return $this->action;
    }

    public function setAction(RelationActionEnum $action): self
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

    public function setForeignIdColumn(string|Closure $foreignIdColumn): static
    {
        $this->foreignIdColumn = $foreignIdColumn;

        return $this;
    }

    private function getForeignIdColumn(): string
    {
        return $this->foreignIdColumn instanceof Closure ? ($this->foreignIdColumn)() : $this->foreignIdColumn;
    }

    public function setData(Closure|array $data = []): self
    {
        $this->data = $data;

        return $this;
    }

    private function getData(): array
    {
        return $this->data instanceof Closure ? ($this->data)() : $this->data;
    }

    private function getModifiedData(): array
    {
        $modifiedData = [];

        foreach ($this->getData() as $key => $value) {
            $modifiedData[$key] = ['modified' => $value, 'original' => null];
        }

        return $modifiedData;
    }

    /**
     * @throws Throwable
     */
    public function save(): self
    {
        DB::transaction(function () {
            $this->modificationRelation = ModificationRelation::create([
                'modification_id' => $this->getModification()->id,
                'model' => $this->getModelName(),
                'foreign_id_column' => $this->getForeignIdColumn(),
                'modifications' => $this->getModifiedData(),
                'action' => $this->getAction()->value,
                'condition_columns' => $this->getConditionColumns(),
            ]);

            if (count($this->getModificationMedias())) {
                CreateMedia::make()
                    ->setModel($this->getModificationRelation())
                    ->saveMany($this->getModificationMedias());
            }
        });

        return $this;
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function saveMany(array $modificationRelations): void
    {
        if (count($modificationRelations)) {
            foreach ($modificationRelations as $modificationRelation) {
                if ($modificationRelation instanceof CreateModificationRelation) {
                    $modificationRelation
                        ->setModification($this->getModification())
                        ->save();
                } else {
                    throw new Exception('modification relations array should be an instance of App\Approvals\ModificationRelation');
                }
            }
        }
    }
}
