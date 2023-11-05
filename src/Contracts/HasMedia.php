<?php

namespace Approval\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HasMedia
{
    public function getMediaModel();
}