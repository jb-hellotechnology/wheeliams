<?php
	
	$WheeliamsStaff = new Wheeliams_Staff_Members($API); 
	$WheeliamsStaffTimes = new Wheeliams_Staff_Member_Times($API); 
	$WheeliamsStaffBankholidays = new Wheeliams_Staff_Member_Bankholidays($API);
	$WheeliamsStaffHolidays = new Wheeliams_Staff_Member_Holidays($API);    
    
    $HTML = $API->get('HTML');
    $Form = $API->get('Form');
    
    $StaffMember = $WheeliamsStaff->find($_GET['staffID'], true);
	$details = $StaffMember->to_array();

    if($Form->submitted()) {
    
        $HolidayID = (int) $_GET['id'];  
		$Holiday = $WheeliamsStaffHolidays->find($HolidayID, true);
		
		$Holiday->delete();
		$deleted = true;
		$message = $HTML->success_message('Holiday has been successfully deleted. Return to %sHolidays%s', '<a href="'.$API->app_path().'/staff/holidays/?id='.$_GET['staffID'].'">', '</a>'); 
        
    }else{
	    $deleted = false;
	    $message = $HTML->warning_message('Are you sure you want to delete this holiday?', '', ''); 
    }