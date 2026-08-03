<?php
	
/*
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
*/
	
    # include the API
    include('../../../../core/inc/api.php');
    
    $API  = new PerchAPI(1.0, 'Wheeliams');

    # include your class files
    include('../Wheeliams.class.php');
    include('../Wheeliamss.class.php');
    include('../Wheeliams.staffmember.class.php');
    include('../Wheeliams.staffmembers.class.php');
    
    # Grab an instance of the Lang class for translations
    $Lang = $API->get('Lang');

    # Set the page title
    $Perch->page_title = 'Wheeliams';
    
    
    # Set Subnav
    include('../modes/_subnav.php');


    # Do anything you want to do before output is started
    include('../modes/staff/index.pre.php');
    
    
    # Top layout
    include(PERCH_CORE . '/inc/top.php');

    
    # Display your page
    include('../modes/staff/index.post.php');
    
    
    # Bottom layout
    include(PERCH_CORE . '/inc/btm.php');
