<?php
	
	$WheeliamsStaff = new Wheeliams_Staff_Members($API); 
	$WheeliamsStaffTimes = new Wheeliams_Staff_Member_Times($API); 
	$WheeliamsStaffSickday = new Wheeliams_Staff_Member_Sickdays($API);    
    
    $HTML = $API->get('HTML');
    $Form = $API->get('Form');
    $Template = $API->get('Template');

    $staffID = (int) $_GET['id'];  
    $StaffMember = $WheeliamsStaff->find($staffID, true);
    $details = $StaffMember->to_array();
    
    $sickdays = $WheeliamsStaffSickday->getDays($staffID);