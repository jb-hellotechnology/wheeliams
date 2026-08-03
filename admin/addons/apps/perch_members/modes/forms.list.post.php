<?php

    echo $HTML->title_panel([
        'heading' => $Lang->get('Listing member forms'),
    ], $CurrentUser);

    if (isset($message)) echo $message;



    $Listing = new PerchAdminListing($CurrentUser, $HTML, $Lang, $Paging);
    $Listing->add_col([
            'title'     => 'Form',
            'value'     => 'formTitle',
            'sort'      => 'formTitle',
            'edit_link' => 'edit',
            'priv'      => 'perch_members.forms.edit',
        ]);

    $Listing->add_col([
            'title'     => 'Key',
            'value'     => 'formKey',
            'sort'      => 'formKey',
        ]);

    
    $Listing->add_delete_action([
            'priv'   => 'perch_members.forms.delete',
            'inline' => true,
            'path'   => 'delete',
        ]);

    echo $Listing->render($forms);

