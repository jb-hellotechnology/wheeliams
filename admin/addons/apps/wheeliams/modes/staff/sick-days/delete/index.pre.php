<?php
	
	$WheeliamsStaff = new Wheeliams_Staff_Members($API); 
	$WheeliamsStaffTimes = new Wheeliams_Staff_Member_Times($API);  
	$WheeliamsStaffSickdays = new Wheeliams_Staff_Member_Sickdays($API);  
    
    $HTML = $API->get('HTML');
    $Form = $API->get('Form');
    $Template = $API->get('Template');
    
    $staffID = $_GET['id'];
    $StaffMember = $WheeliamsStaff->find($staffID, true);
	$details = $StaffMember->to_array(); 

    if($Form->submitted()) {
    
        $SickdayID = (int) $_GET['sickdayID'];  
		$Sickday = $WheeliamsStaffSickdays->find($SickdayID, true);
		
		$Sickday->delete();
		$deleted = true;
		$message = $HTML->success_message('Sick Day has been successfully deleted. Return to %sSick Days%s', '<a href="'.$API->app_path().'/staff/sick-days/?id='.$_GET['id'].'">', '</a>'); 
        
    }else{
	    $deleted = false;
	    $message = $HTML->warning_message('Are you sure you want to delete this Sick Day?', '', ''); 
    }