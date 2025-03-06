<?php

namespace TipBr\Extensions;

use SilverStripe\Core\Extension;
use TipBr\DataObjects\PasswordResetRequest;

class SecurityAdminExtension extends Extension
{
    public function updateManagedModels(&$models)
    {
        $models['resetrequests'] = [
            'title' => 'Reset Requests',
            'dataClass' => PasswordResetRequest::class,
        ];
    }
}
