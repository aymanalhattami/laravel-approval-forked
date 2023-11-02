<?php

namespace Approval\Models;

use Illuminate\Database\Eloquent\Model;

class ModificationRelation extends Model
{
    /**
     * The attributes that can't be filled.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'modifications' => 'json',
    ];

    public function modification()
    {
        return $this->belongsTo(config('approval.models.modification', Modification::class));
    }
}
