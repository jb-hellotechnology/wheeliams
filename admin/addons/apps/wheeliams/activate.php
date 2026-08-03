<?php

	include('../../../core/inc/api.php');
	
	$API = new PerchAPI(1.0, 'wheeliams');
	
	$UserPrivileges = $API->get('UserPrivileges');
    