 <?php
    echo $HTML->side_panel_start();
    
    echo $HTML->side_panel_end();
    
    echo $HTML->title_panel([
    'heading' => 'Add Staff Member'
    ], $CurrentUser);

    echo $HTML->main_panel_start(); 
    
    if (isset($message)){ 
	    
	    echo $message;
	    
	}else{
		
		echo $Form->form_start();
		
		echo $Form->text_field("name","Name",'');
		
		echo $Form->text_field("email","Email",'');
		
		echo $Form->text_field("phone","Phone",'');
		
		echo $Form->text_field("address","Address",'');
		
		echo $Form->date_field("startDate","Start Date",'');
		
		echo $Form->text_field("memberID","Member ID",'');
		
		echo $Form->text_field("holidayAllowance","Holiday Allowance",'');
		    
		echo $Form->submit_field('btnSubmit', 'Add Staff Member', $API->app_path());
		
		echo $Form->form_end();
	
	}

    echo $HTML->main_panel_end();