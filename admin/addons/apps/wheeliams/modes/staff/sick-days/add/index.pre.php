<?php

	$WheeliamsStaff = new Wheeliams_Staff_Members($API); 
	$WheeliamsStaffTime = new Wheeliams_Staff_Member_Times($API);    
	$WheeliamsStaffSickdays = new Wheeliams_Staff_Member_Sickdays($API);  
    
    $HTML = $API->get('HTML');
    $Form = $API->get('Form');
    $Template = $API->get('Template');
    
    $staffID = $_GET['id'];
    $StaffMember = $WheeliamsStaff->find($staffID, true);
	$details = $StaffMember->to_array();

    if($Form->submitted()) {
    
        //FOR ITEMS PROGRAMMATICALLY ADDED TO FORM
        $postvars = array('date_day', 'date_month', 'date_year');	   
        
    	$data = $Form->receive($postvars);      
		$data['staffID'] = $_GET['id'];
		
		$data['date'] = "$data[date_year]-$data[date_month]-$data[date_day]";
		
		unset($data['date_day']);
		unset($data['date_month']);
		unset($data['date_year']);
		
        $new_time = $WheeliamsStaffSickdays->create($data);

        // SHOW RELEVANT MESSAGE
        if ($new_time) {
            $message = $HTML->success_message('Sick Day has been successfully created. Return to %sSick Days%s', '<a href="'.$API->app_path().'/staff/sick-days/?id='.$_GET['id'].'">', '</a>'); 
        }else{
            $message = $HTML->failure_message('Sorry, Sick Day could not be created.');
        }
        
    }