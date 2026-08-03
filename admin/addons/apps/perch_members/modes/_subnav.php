<?php

	$Members = new PerchMembers_Members($API);
	$pending_mod_count = $Members->get_count('pending');

	PerchUI::set_subnav([
		['page'=>[
					'perch_members',
					'perch_members/delete',
					'perch_members/edit',
			], 'label'=>'Members', 'badge'=>$pending_mod_count, 'priv'=>'perch_members.moderate'],
		/*['page'=>[
					'perch_members/events',
					'perch_members/events/edit',
					'perch_members/events/delete',
			], 'label'=>'Events', 'priv'=>'perch_members.events.manage'], */
		['page'=>[
					'perch_members/forms',
					'perch_members/forms/edit',
			], 'label'=>'Forms',  'priv'=>'perch_members.forms.manage'],
	], $CurrentUser);
