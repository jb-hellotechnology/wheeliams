<?php
	
/*
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
*/
	
    # include the API
    include('../../../../../core/inc/api.php');
    
    $API  = new PerchAPI(1.0, 'Wheeliams');

    # include your class files
    include('../../Wheeliams.class.php');
    include('../../Wheeliamss.class.php');
    include('../../Wheeliams.staffmember.class.php');
    include('../../Wheeliams.staffmembers.class.php');
    include('../../Wheeliams.staffmember.time.class.php');
    include('../../Wheeliams.staffmember.times.class.php');
    include('../../Wheeliams.staffmember.holiday.class.php');
    include('../../Wheeliams.staffmember.holidays.class.php');
    include('../../Wheeliams.staffmember.sickday.class.php');
    include('../../Wheeliams.staffmember.sickdays.class.php');
    
    # Grab an instance of the Lang class for translations
    $Lang = $API->get('Lang');

    # Set the page title
    $Perch->page_title = 'Wheeliams';
    
    # Set Subnav
    include('../../modes/_subnav.php');


    # Do anything you want to do before output is started
    include('../../modes/staff/sick-days/index.pre.php');
    
    
    # Top layout
    include(PERCH_CORE . '/inc/top.php');

    
    # Display your page
    include('../../modes/staff/sick-days/index.post.php');
    
    
    # Bottom layout
    include(PERCH_CORE . '/inc/btm.php');
