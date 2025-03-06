<?php

namespace TipBr\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use TipBr\DataObjects\PasswordResetRequest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

class MemberExtension extends Extension
{
    private static $has_many = [
        'PasswordResetRequests' => PasswordResetRequest::class
    ];

    public function updateCMSFields(FieldList $fields)
    {

        $fields->addFieldToTab('Root.PasswordResetRequests', GridField::create(
            'PasswordResetRequests',
            'Password Reset Codes',
            $this->owner->PasswordResetRequests(),
            GridFieldConfig_RecordEditor::create()
        ));

        return $fields;
    }
}
