 <?php
    echo $HTML->side_panel_start();
    
    echo $HTML->side_panel_end();
    
    echo $HTML->title_panel([
    'heading' => $details['name'].' - Staff Profile'
    ], $CurrentUser);
    
    $Smartbar = new PerchSmartbar($CurrentUser, $HTML, $Lang);
		
	$Smartbar->add_item([
	    'active' => true,
	    'title' => 'Profile',
	    'link'  => $API->app_nav().'/staff/?id='.$staffID,
	]);

	$Smartbar->add_item([
	    'active' => false,
	    'title' => 'Hours',
	    'link'  => $API->app_nav().'/staff/hours/?id='.$staffID,
	]);
	
	$Smartbar->add_item([
    'active' => false,
    'title' => 'Holidays',
    'link'  => $API->app_nav().'/staff/holidays/?id='.$staffID,
	]);
	
	$Smartbar->add_item([
	    'active' => false,
	    'title' => 'Sick Days',
	    'link'  => $API->app_nav().'/staff/sick-days/?id='.$staffID,
	]);
	
	echo $Smartbar->render();

    echo $HTML->main_panel_start(); 
    
    if (isset($message)){ 
	    
	    echo $message;
	    
	}else{
		
		echo $Form->form_start();
		
		echo $Form->text_field("name","Name",$details['name']);
		
		echo $Form->text_field("email","Email",$details['email']);
		
		echo $Form->text_field("phone","Phone",$details['phone']);
		
		echo $Form->text_field("address","Address",$details['address']);
		
		echo $Form->date_field("startDate","Start Date",$details['startDate']);
		
		echo $Form->text_field("memberID","Member ID",$details['memberID']);
		
		echo $Form->text_field("holidayAllowance","Holiday Allowance",$details['holidayAllowance']);
		
		echo $Form->fields_from_template($Template, $details, $WheeliamsStaff->static_fields);
		    
		echo $Form->submit_field('btnSubmit', 'Update Staff Member', $API->app_path());
		
		echo $Form->form_end();
	
	}

    echo $HTML->main_panel_end();