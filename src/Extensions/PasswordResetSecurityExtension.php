<?php

namespace TipBr\Extensions;

use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\Tab;
use TipBr\DataObjects\PasswordResetRequest;

class PasswordResetSecurityExtension extends Extension
{
    /**
     * Add a tab to the SecurityAdmin
     */
    public function updateEditForm($form)
    {
        $fields = $form->Fields();

        // Create gridfield for password reset requests
        $gridFieldConfig = GridFieldConfig_RecordEditor::create();
        $gridField = GridField::create(
            'PasswordResetRequests',
            'Password Reset Requests',
            PasswordResetRequest::get(),
            $gridFieldConfig
        );

        // Find the main TabSet
        $mainTabSet = $fields->fieldByName('Root');

        if ($mainTabSet && $mainTabSet instanceof TabSet) {
            // Create a new tab and add it directly to the main TabSet
            $tab = Tab::create(
                'PasswordResetRequests',
                'Password Reset Requests',
                $gridField
            );

            $mainTabSet->push($tab);
        }
    }
}

// class PasswordResetSecurityExtension extends Extension
// {
//     /**
//      * Update CMS Fields to add PasswordResetRequest GridField
//      *
//      * @param FieldList $fields
//      */
//     public function updateEditForm($form)
//     {
//         // Get the form fields
//         $fields = $form->Fields();

//         // Create a new tab for the password reset requests
//         $resetRequestsTab = Tab::create(
//             'PasswordResetRequests',
//             'Password Reset Requests'
//         );

//         // Create the gridfield for the requests
//         $gridFieldConfig = GridFieldConfig_RecordEditor::create();
//         $gridField = GridField::create(
//             'PasswordResetRequests',
//             'Password Reset Requests',
//             PasswordResetRequest::get(),
//             $gridFieldConfig
//         );

//         // Associate the gridfield with the form
//         $gridField->setForm($form);

//         // Add the gridfield to the tab
//         $resetRequestsTab->push($gridField);

//         // Add the tab to the form
//         $fields->insertAfter('Users', $resetRequestsTab);
//     }
// }
