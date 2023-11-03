<h6 align="center">
    <img src="https://raw.githubusercontent.com/stephenlake/laravel-approval/master/docs/assets/laravel-approval-banner.png"/>
</h6>

<h6 align="center">
    Attach modification approvals to any model to prevent unauthorised updates.
</h6>

> **Note:**
> This package is forked from [cloudcake/laravel-approval](https://github.com/cloudcake/laravel-approval) in order to support latest laravel version and to add new features

# Getting Started

## Install the package via composer

```bash
composer require aymanalhattami/laravel-approval
```

## Register the service provider

This package makes use of Laravel's auto-discovery. If you are an using earlier version of Laravel (&lt; 5.4) you will need to manually register the service provider.

Add `Approval\ApprovalServiceProvider::class` to the `providers` array in `config/app.php`.

## Publish configuration

`php artisan vendor:publish --provider="Approval\ApprovalServiceProvider" --tag="config"`

## Publish migrations

`php artisan vendor:publish --provider="Approval\ApprovalServiceProvider" --tag="migrations"`

## Run migrations

`php artisan migrate`

# Setting Up

## Setup Approval Model(s)

Any model you wish to attach to an approval process simply requires the `RequiresApproval` trait, for example:

```php
use Approval\Traits\RequiresApproval;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use RequiresApproval;
}
```

### Conditional Approvals

There may be instances where you don't always want your model to go through an approval process, for this reason the the `requiresApprovalWhen` is available for your convenience and defaults to false so that no unintended approvals are created behind the scenes:

```php
/**
* Function that defines the rule of when an approval process
* should be actioned for this model.
*
* @param array $modifications
*
* @return boolean
*/
protected function requiresApprovalWhen(array $modifications) : bool
{
    // Handle some logic that determines if this change requires approval
    //
    // Return true if the model requires approval, return false if it
    // should update immediately without approval.
    return false;
}
```

### Optional Attributes

Approval models come with a few optional attributes to make your approval process as flexible as possible. The following attributes are define by default with the set defaults, you may alter them per model as you please.

```php
/**
* Number of approvers this model requires in order
* to mark the modifications as accepted.
*
* @var integer
*/
protected $approversRequired = 1;

/**
* Number of disapprovers this model requires in order
* to mark the modifications as rejected.
*
* @var integer
*/
protected $disapproversRequired = 1;

/**
* Boolean to mark whether or not this model should be updated
* automatically upon receiving the required number of approvals.
*
* @var boolean
*/
protected $updateWhenApproved = true;

/**
* Boolean to mark whether or not the approval model should be deleted
* automatically when the approval is disapproved wtih the required number
* of disapprovals.
*
* @var boolean
*/
protected $deleteWhenDisapproved = false;

/**
* Boolean to mark whether or not the approval model should be deleted
* automatically when the approval is approved wtih the required number
* of approvals.
*
* @var boolean
*/
protected $deleteWhenApproved = true;
```

## Setup Approver Model(s)

Any other model (not just a user model) can approve models by simply adding the `ApprovesChanges` trait to it, for example:

```php
use Approval\Traits\ApprovesChanges;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use ApprovesChanges;
}
```

Any model with the `ApprovesChanges` trait inherits the approval access function.

### Approver Authorization (Optional)

By default, any model with the `ApprovesChanges` trait will be able to approve and disapprove modifications. You can customize your authorization to approve/disapprove modifications however you please by adding the `authorizedToApprove` method on the specific approver model:

```php
use Approval\Traits\ApprovesChanges;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use ApprovesChanges;

    protected function authorizedToApprove(\Approval\Models\Modification $mod) : bool
    {
        // Return true to authorize approval, false to deny
        return true;
    }
}
```

### Disapprover Authorization (Optional)

Similarly to the approval process, the disapproval authorization method for disapproving modifications follows the same logic:

```php
use Approval\Traits\ApprovesChanges;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use ApprovesChanges;

    protected function authorizedToApprove(\Approval\Models\Modification $mod) : bool
    {
        // Return true to authorize approval, false to deny
        return true;
    }

    protected function authorizedToDisapprove(\Approval\Models\Modification $mod) : bool
    {
        // Return true to authorize disapproval, false to deny
        return true;
    }
}
```

# Usage

## Retrieving Pending Modifications

Any model that contains the `RequiresApproval` trait may have multiple pending modifications, to access these modifications you can call the `modifications()` method on the approval model:

```php
$post = Post::find(1);
$post->modifications()->get();
```

## Retrieving Only Pending Creations

```php
$post = Post::find(1);
$post->modifications()->creations()->get();
```

## Retrieving Only Pending Changes

```php
$post = Post::find(1);
$post->modifications()->changed()->get();
```

## Retrieving Modification Creator

For any pending modifications on a model, you may fetch the model that initiated the modification request:

```php
$post = Post::find(1);
$post->modifications()->first()->modifier();
```

This (modifier) would usually be a user that changed the model and triggered the approval modification, but because Approval caters for more than just users, it's possible that the creator is any other model.

## Retrieving A Model's Modifications

```php
$active = Post::find(1)->modifications()->activeOnly()->get();
$inactive = Post::find(1)->modifications()->inactiveOnly()->get();
$any = Post::find(1)->modifications()->get();
```

## Adding a Modification Approval

```php
$modification = Post::find(1)->modifications()->first();
$reason = "This is optional reason.";

$approver = Admin::first();
$approver->approve($modification, $reason);
```

## Adding a  Modification Disapproval
```php
$modification = Post::find(1)->modifications()->first();
$reason = "This is optional reason.";

$approver = Admin::first();
$approver->disapprove($modification, $reason);
```

## Retrieving Modification Approvals

```php
$post         = Post::find(1);
$modification = $post->modifications()->first();
$approval     = $modification->approvals()->get();
```

## Retrieving Approval Author

```php
$post         = Post::find(1);
$modification = $post->modifications()->first();
$approval     = $modification->approvals()->get();
$author       = $approval->approver();
```

## Retrieving Approval Reason

```php
$post         = Post::find(1);
$modification = $post->modifications()->first();
$approval     = $modification->approvals()->first();
$reason       = $approval->reason;
```

## Retrieving Modification Disapprovals

```php
$post         = Post::find(1);
$modification = $post->modifications()->first();
$approval     = $modification->disapprovals()->get();
```

## Retrieving Disapproval Author

```php
$post         = Post::find(1);
$modification = $post->modifications()->first();
$approval     = $modification->disapprovals()->get();
$author       = $approval->disapprover();
```

## Retrieving disapproval Reason

```php
$post         = Post::find(1);
$modification = $post->modifications()->first();
$approval     = $modification->disapprovals()->first();
$reason       = $approval->reason;
```


## Retrieving Remaining Required Modification Approvals

```php
$post         = Post::find(1);
$modification = $post->modifications()->first();
$remaining    = $modification->approversRemaining;
```

## Retrieving Remaining Required Modification Disapprovals

```php
$post         = Post::find(1);
$modification = $post->modifications()->first();
$remaining    = $modification->disapproversRemaining;
```


## Forcing Modification Updates

```php
$post         = Post::find(1);
$modification = $post->modifications()->first();
$modification->forceApprovalUpdate();
```
