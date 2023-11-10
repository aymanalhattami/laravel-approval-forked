<?php

namespace Approval\Tests\Models;

use Approval\Traits\RequiresApproval;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use RequiresApproval;

    protected $guarded = [];

    /**
     * Function that defines the rule of when an approval process
     * should be actioned for this model.
     *
     * @param  array  $modifications
     */
    protected function requiresApprovalWhen($modifications): bool
    {
        return $modifications['title'] == 'Trigger Approval';
    }
}
