<?php

namespace Approval;

use Approval\Models\Modification;
use Illuminate\Support\Facades\Auth;

class RegisterModification
{
    protected string $model;
    protected array $data = [];
    protected Modification $modification;

    public static function make(): self
    {
        return new static;
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

    public function setData(array $data): self
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
}