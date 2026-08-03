<?php
	
	$WheeliamsStaff = new Wheeliams_Staff_Members($API); 
	$WheeliamsStaffTime = new Wheeliams_Staff_Member_Times($API);    
    
    $HTML = $API->get('HTML');
    $Form = $API->get('Form');
    $Template = $API->get('Template');

    $timeID = (int) $_GET['id'];  
    $StaffTime = $WheeliamsStaffTime->find($timeID, true);
    $details = $StaffTime->to_array();

	$StaffMember = array();

    if($Form->submitted()) {
    
        //FOR ITEMS PROGRAMMATICALLY ADDED TO FORM
        $postvars = array('staffID','timeType','timeStamp');	   
    	$data = $Form->receive($postvars);      
    	$data['timemotoData'] = '';

        $new_time = $StaffTime->update($data);

        // SHOW RELEVANT MESSAGE
        if ($new_time) {
            $message = $HTML->success_message('Staff hours have been successfully updated. Return to %sStaff Hours%s', '<a href="'.$API->app_path().'/staff/hours?id='.$_GET['id'].'">', '</a>'); 
        }else{
            $message = $HTML->failure_message('Sorry, staff hours could not be updated.');
        }
        
    }