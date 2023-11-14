<?php

namespace Approval\Enums;

enum ActionEnum: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'Create',
            self::Update => 'Update',
            self::Delete => 'Delete',
        };
    }

    public static function toArray(): array
    {
        $enums = [];

        foreach (static::cases() as $status) {
            $enums[$status->value] = $status->label();
        }

        return $enums;
    }
}
