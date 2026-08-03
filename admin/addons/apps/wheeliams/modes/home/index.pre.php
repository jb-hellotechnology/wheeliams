<?php
    
    $HTML = $API->get('HTML');
    
    $WheeliamsStaff = new Wheeliams_Staff_Members($API); 
    
    $HTML = $API->get('HTML');
    
    $staff = array();
    $staff = $WheeliamsStaff->signedIn();