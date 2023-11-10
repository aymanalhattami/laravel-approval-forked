<?php

namespace Approval\Enums;

# TODO:: rename to RelationAction
enum ActionEnum: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case UpdateOrCreate = 'update_or_create';
    case DeleteThenCreate = 'delete_then_create';

}
