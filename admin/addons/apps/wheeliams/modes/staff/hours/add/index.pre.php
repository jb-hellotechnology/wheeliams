<?php
	
	$WheeliamsStaff = new Wheeliams_Staff_Members($API); 
	$WheeliamsStaffTime = new Wheeliams_Staff_Member_Times($API);    
    
    $HTML = $API->get('HTML');
    $Form = $API->get('Form');
    $Template = $API->get('Template');

    $staffID = (int) $_GET['id'];  
    $StaffMember = $WheeliamsStaff->find($staffID, true);
   
    $details = $StaffMember->to_array();

	$StaffMember = array();
    
    $Template = $API->get ('Template');

    $Template->set('natures_laboratory/staff_member_time.html','nl');

    // HANDLE BLOCKS FROM TEMPLATE
    $Form->handle_empty_block_generation($Template);

    // SET REQUIRED FIELDS
    $Form->set_required_fields_from_template($Template, $details);

    if($Form->submitted()) {
    
        //FOR ITEMS PROGRAMMATICALLY ADDED TO FORM
        $postvars = array('timeType','timeStamp');	   
    	$data = $Form->receive($postvars);      
    	$data['staffID'] = $details['memberID'];
    	$data['timemotoData'] = '';

        // READ IN DYNAMIC FIELDS FROM TEMPLATE
        $previous_values = false;
        if (isset($details['wheeliams_staff_timeDynamicFields'])) {
            $previous_values = PerchUtil::json_safe_decode($details['wheeliams_staff_timeDynamicFields'], true);
        }

        // GET DYNAMIC FIELDS AND CREATE JSON ARRAY FOR DB
        $dynamic_fields = $Form->receive_from_template_fields($Template, $previous_values, $WheeliamsStaff, $StaffMember);
        $data['wheeliams_staff_timeDynamicFields'] = PerchUtil::json_safe_encode($dynamic_fields);

        $new_time = $WheeliamsStaffTime->create($data);

        // SHOW RELEVANT MESSAGE
        if ($new_time) {
            $message = $HTML->success_message('Staff hours have been successfully created. Return to %sStaff Hours%s', '<a href="'.$API->app_path().'/staff/hours?id='.$_GET['id'].'">', '</a>'); 
        }else{
            $message = $HTML->failure_message('Sorry, staff hours could not be created.');
        }
        
    }