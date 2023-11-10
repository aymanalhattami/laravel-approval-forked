<?php

namespace Approval\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ModificationRelation extends Model implements HasMedia
{
    use InteractsWithMedia;

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
        'condition_columns' => 'json',
    ];

    public function modification()
    {
        return $this->belongsTo(config('approval.models.modification', Modification::class));
    }
}
