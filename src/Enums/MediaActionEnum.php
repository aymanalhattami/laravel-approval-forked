<?php

namespace Approval\Enums;

enum MediaActionEnum: string
{
    case Create = 'create';
    case Delete = 'delete';
    case DeleteThenCreate = 'delete_then_create';
}
