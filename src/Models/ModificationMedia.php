<?php

namespace Approval\Models;

use Approval\Enums\MediaActionEnum;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ModificationMedia extends Model
{

    /**
     * The attributes that can't be filled.
     *
     * @var array
     */
    protected $guarded = ['id'];
    protected $casts = [
        'action' => MediaActionEnum::class,
    ];


    public function media(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function model(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('model');
    }
}
