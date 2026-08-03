<?php

	// Define subnav links and titles
	
	PerchUI::set_subnav([
		[
            'page' => [
            	'wheeliams'
            ],
            'label'=> 'Dashboard'
        ],
        [
            'page' => [
            	'wheeliams/staff',
            	'wheeliams/staff/add',
            	'wheeliams/staff/edit',
            	'wheeliams/staff/delete',
            	'wheeliams/staff/hours',
            	'wheeliams/staff/holidays',
            	'wheeliams/staff/holidays/add',
            	'wheeliams/staff/holidays/delete',
            	'wheeliams/staff/bank-holidays',
            	'wheeliams/staff/early-finishes',
            	'wheeliams/staff/sick-days',
            	'wheeliams/staff/sick-days/delete',
            	'wheeliams/staff/compassionate-leave',
            	'wheeliams/staff/compassionate-leave/delete',
            ],
            'label'=> 'Staff'
        ]
    ], $CurrentUser);
