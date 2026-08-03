<?php

	$WheeliamsStaff = new Wheeliams_Staff_Members($API); 
	$WheeliamsStaffTimes = new Wheeliams_Staff_Member_Times($API); 
	$WheeliamsStaffBankholiday = new Wheeliams_Staff_Member_Bankholidays($API);    
	$WheeliamsStaffCompassionate = new Wheeliams_Staff_Member_Compassionatedays($API);  
	$WheeliamsStaffSickdays = new Wheeliams_Staff_Member_Sickdays($API);    
	$WheeliamsStaffHolidays = new Wheeliams_Staff_Member_Holidays($API); 
    
    $HTML = $API->get('HTML');
    $Form = $API->get('Form');
    $Template = $API->get('Template');

    $staffID = (int) $_GET['id'];  
    if($staffID){
	    $StaffMember = $WheeliamsStaff->find($staffID, true);
	    $details = $StaffMember->to_array();
		$holidays = array();
		$holidays = $WheeliamsStaffHolidays->byStaffID($_GET['id']);
	
		if($Form->submitted()) {
			//MAKE LABELS
			$postvars = array();
			foreach($holidays as $Holiday){
				array_push($postvars, 'date_'.$Holiday['wheeliams_staff_holidayID']);
			}   
	    	$data = $Form->receive($postvars); 
	    	$list = '';
	    	foreach($data as $date){
		    	$h = $WheeliamsStaffHolidays->find($date, true);
				$h->delete();
	    	}
	    	$holidays = $WheeliamsStaffHolidays->byStaffID($_GET['id']);
    	}	
    }else{
	    $staff = array();
		$staff = $WheeliamsStaff->all();
    }