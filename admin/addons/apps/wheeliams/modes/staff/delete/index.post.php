 <?php
    echo $HTML->side_panel_start();
    
    echo $HTML->side_panel_end();
    
    echo $HTML->title_panel([
    'heading' => 'Add Staff Member'
    ], $CurrentUser);

    echo $HTML->main_panel_start(); 
    
    if (isset($message)){ 
	    
	    echo $message;
	    
	}
	
	if(!$deleted){
		
		echo $Form->form_start();
		    
		echo $Form->submit_field('btnSubmit', 'Delete Staff Member', $API->app_path());
		
		echo $Form->form_end();
	
	}

    echo $HTML->main_panel_end();
    
	PerchUtil::output_debug();