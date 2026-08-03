<?php
    echo $HTML->title_panel([
        'heading' => $heading1,
    ], $CurrentUser);
    
    if ($message) echo $message;    

    echo $HTML->heading2('Form details');
    
    
    echo $Form->form_start();
    
        echo $Form->text_field('formTitle', 'Title', isset($details['formTitle'])?$details['formTitle']:false);

        echo $HTML->heading2('Options');

        echo $Form->checkbox_field('moderate', 'New members require approval', '1', isset($settings['moderate'])?$settings['moderate']:'1');
        echo $Form->text_field('moderator_email', 'Email address of moderator', isset($settings['moderator_email'])?$settings['moderator_email']:false);
        echo $Form->text_field('default_tags', 'Default tags', isset($settings['default_tags'])?$settings['default_tags']:false);

        echo $Form->submit_field('btnSubmit', 'Save', $API->app_path());
    
    echo $Form->form_end();
    
