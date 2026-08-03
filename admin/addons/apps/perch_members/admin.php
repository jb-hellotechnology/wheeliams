<?php
	if ($CurrentUser->logged_in() && $CurrentUser->has_priv('perch_members')) {
	    $this->register_app('perch_members', 'Members', 2, 'Manage site members', '1.6.4');
	    $this->require_version('perch_members', '3.1.1');
	    $this->add_setting('perch_members_login_page', 'Login page path', 'text', '/members/login.php?r={returnURL}');
	}

	spl_autoload_register(function($class_name){
    	if (strpos($class_name, 'PerchMembers')===0) {
    		include(PERCH_PATH.'/addons/apps/perch_members/'.$class_name.'.class.php');
    		return true;
    	}
    	return false;
    });