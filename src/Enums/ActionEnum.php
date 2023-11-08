<?php

namespace Approval\Enums;

# TODO:: rename to RelationAction
enum ActionEnum: string
{
    case Create = 'create'; # useful for hasMany or belongsToMany relationship
    case UpdateOrCreate = 'update_or_create'; # useful for hasOne relationship
    case DeleteThenCreate = 'delete_then_create'; # useful for hasMany or belongsToMany relationship

//    case MorphCreate = 'morph_create';
    case MorphUpdateOrCreate = 'morph_update_or_create';
    case MorphDeleteThenCreate = 'morph_delete_then_create';

}
