<?php

namespace Approval\Enums;

// TODO:: rename to RelationAction
enum RelationActionEnum: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case UpdateOrCreate = 'update_or_create';
    case DeleteThenCreate = 'delete_then_create';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'Create',
            self::Update => 'Update',
            self::Delete => 'Delete',
            self::UpdateOrCreate => 'Update if exists or create if not',
            self::DeleteThenCreate => 'Delete all then create',
        };
    }
}
