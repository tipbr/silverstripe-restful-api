<?php

namespace TipBr\Extensions;

use SilverStripe\Security\Member;
use TipBr\DataObjects\PasswordResetRequest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

class MemberExtension extends Member
{
    private static $has_many = [
        'PasswordResetRequests' => PasswordResetRequest::class
    ];

    // public function getCMSFields()
    // {
    //     $fields = parent::getCMSFields();
    //     $fields->addFieldToTab('Root.PasswordResetRequests', GridField::create(
    //         'PasswordResetRequests',
    //         'Password Reset Requests',
    //         $this->PasswordResetRequests(),
    //         GridFieldConfig_RecordEditor::create()
    //     ));

    //     return $fields;
    // }
}
