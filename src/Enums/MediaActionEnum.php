<?php

namespace Approval\Enums;

enum MediaActionEnum: string
{
    case Create = 'create';
    case Delete = 'delete';
    case DeleteThenCreate = 'delete_then_create';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'Create',
            self::Delete => 'Delete',
            self::DeleteThenCreate => 'Delete all then create',
        };
    }
}
