<?php
	
	$WheeliamsStaff = new Wheeliams_Staff_Members($API);    
    
    $HTML = $API->get('HTML');
    $Form = $API->get('Form');
    $Template = $API->get('Template');

    $Template->set('natures_laboratory/staff_member.html','nl');
    
    $staffID = (int) $_GET['id'];  
    $StaffMember = $WheeliamsStaff->find($staffID, true);
    $details = $StaffMember->to_array();
    
    $rfid = $WheeliamsStaff->rfid($staffID);

    // HANDLE BLOCKS FROM TEMPLATE
    $Form->handle_empty_block_generation($Template);

    // SET REQUIRED FIELDS
    $Form->set_required_fields_from_template($Template, $details);

    if($Form->submitted()) {
    
        //FOR ITEMS PROGRAMMATICALLY ADDED TO FORM
        $postvars = array('name','email','phone','address','startDate_day','startDate_month','startDate_year', 'memberID', 'holidayAllowance');	   
    	$data = $Form->receive($postvars);      
    	
    	$data['startDate'] = "$data[startDate_year]-$data[startDate_month]-$data[startDate_day]";
    	unset($data['startDate_year']);
    	unset($data['startDate_month']);
    	unset($data['startDate_day']);

        // READ IN DYNAMIC FIELDS FROM TEMPLATE
        $previous_values = false;
        if (isset($details['staffDynamicFields'])) {
            $previous_values = PerchUtil::json_safe_decode($details['wheeliams_staffDynamicFields'], true);
        }

        // GET DYNAMIC FIELDS AND CREATE JSON ARRAY FOR DB
        $dynamic_fields = $Form->receive_from_template_fields($Template, $previous_values, $WheeliamsStaff, $StaffMember);
        $data['wheeliams_staffDynamicFields'] = PerchUtil::json_safe_encode($dynamic_fields);

        $updated_staff = $StaffMember->update($data);

        // SHOW RELEVANT MESSAGE
        $message = $HTML->success_message('Staff member has been successfully updated. Return to %sStaff%s', '<a href="'.$API->app_path().'/staff/">', '</a>'); 
        
    }