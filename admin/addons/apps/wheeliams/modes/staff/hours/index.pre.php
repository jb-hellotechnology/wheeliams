<?php
	
/*
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
*/

	$WheeliamsStaff = new Wheeliams_Staff_Members($API); 
	$WheeliamsStaffTimes = new Wheeliams_Staff_Member_Times($API); 
	$WheeliamsStaffBreaks = new Wheeliams_Staff_Member_Breaks($API);
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

		$times = array();
		if($_GET['date']){
			$date = $_GET['date'];
		}else{
			$date = date('Y-m');
		}
	    $times = $WheeliamsStaffTimes->forMonth($date,$details['memberID']);
    }else{
	    $staff = array();
		$staff = $WheeliamsStaff->all();
    }