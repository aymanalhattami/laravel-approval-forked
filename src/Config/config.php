<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | If you wish to extend the approval models with your own custom model,
    | you'll need to specify your models here. This is particularly useful
    | if you need to configure your models with UUID's as primary keys for
    | example.
    |
    */

    'models' => [
      'modification' => \Approval\Models\Modification::class,
      'approval'     => \Approval\Models\Approval::class,
      'disapproval'  => \Approval\Models\Disapproval::class,
      'modificationRelation'  => \Approval\Models\ModificationRelation::class,
    ],

    'disk_name' => env('MODIFICATION_DISK_NAME', 'public'),
    'collection_name' => env('MODIFICATION_COLLECTION_NAME', 'modification')
];
