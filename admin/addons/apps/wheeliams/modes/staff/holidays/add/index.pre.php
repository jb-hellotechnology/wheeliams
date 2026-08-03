<?php

	$WheeliamsStaff = new Wheeliams_Staff_Members($API); 
	$WheeliamsStaffTime = new Wheeliams_Staff_Member_Times($API);    
	$WheeliamsStaffBankholidays = new Wheeliams_Staff_Member_Bankholidays($API);
	$WheeliamsStaffHolidays = new Wheeliams_Staff_Member_Holidays($API);  
    
    $HTML = $API->get('HTML');
    $Form = $API->get('Form');
    $Template = $API->get('Template');
    
    $StaffMember = $WheeliamsStaff->find($_GET['id'], true);
	$details = $StaffMember->to_array();

    if($Form->submitted()) {
    
        //FOR ITEMS PROGRAMMATICALLY ADDED TO FORM
        $postvars = array('date_day', 'date_month', 'date_year', 'end_date_day', 'end_date_month', 'end_date_year', 'length');	   
    	$data = $Form->receive($postvars);      

		$data['staffID'] = $_GET['id'];
		$data['date'] = "$data[date_year]-$data[date_month]-$data[date_day]";
		$data['end_date'] = "$data[end_date_year]-$data[end_date_month]-$data[end_date_day]";
		
		unset($data['date_day']);
		unset($data['date_month']);
		unset($data['date_year']);
		
		unset($data['end_date_day']);
		unset($data['end_date_month']);
		unset($data['end_date_year']);
		
		$date1 = new DateTime($data['date']);
		$date2 = new DateTime($data['end_date']);
		
		$interval = $date1->diff($date2);
		$days = $interval->days;
		
		unset($data['end_date']);
		$dates = explode("-", $data['date']);
		
		$thisDate = date("Y-m-d", mktime(0, 0, 0, $dates[1], $dates[2], $dates[0]));
		
		$i = 0;
		$y = 0;
		while($i<=$days){

			$dates = explode("-", $thisDate);
			
			$data['staffID'] = $_GET['id'];
			$data['date'] = $thisDate;
			$holiday = $WheeliamsStaffHolidays->create($data);
			
			$thisDate = date("Y-m-d", mktime(0, 0, 0, $dates[1], $dates[2]+1, $dates[0]));
			
			$i++;
			$y++;
		}
		
        

        $message = $HTML->success_message('Holiday has been successfully created. Return to %sHolidays%s', '<a href="'.$API->app_path().'/staff/holidays/?id="'.$_GET['id'].'>', '</a>'); 
        
    }