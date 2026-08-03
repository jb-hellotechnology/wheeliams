<?php

    // Try to update
    $Settings = $API->get('Settings');

    if ($Settings->get('perch_members_update')->val()!='1.4') {
        include('update.php');
    }




    $HTML = $API->get('HTML');

    $Members = new PerchMembers_Members($API);

    $Paging = $API->get('Paging');
    $Paging->set_per_page(40);

    $Tags = new PerchMembers_Tags($API);
    $tags = $Tags->all();


	$Lang = $API->get('Lang');

    $members = array();


    $pending_mod_count = $Members->get_count('pending');

    if ($pending_mod_count>0) {
        $filter = 'all';
        $status = 'pending';
    }else{
        $filter = 'status';
        $status = 'all';
    }




    if (isset($_GET['tag']) && $_GET['tag'] != '') {
        $filter = 'tag';
        $tag = $_GET['tag'];
        $status = '';
    }


    if (isset($_GET['status']) && $_GET['status'] != '') {
        $filter = 'status';
        $status = $_GET['status'];
    }

    if (isset($_GET['show-filter']) && $_GET['show-filter'] != '') {
        $status = '';
    }

    

    switch ($filter) {

        case 'tag':
            $members = $Members->get_by_tag_for_admin_listing($tag);
            break;

        case 'status':
            if ($status == 'all') {
                $members = $Members->all($Paging);
            }else{
                $members = $Members->get_by_status($status);
            }

            break;

        default:
            $members = $Members->get_by_status('pending');


            break;
    }

    // Install
    if ($status=='all' && $members == false) {
        $Members->attempt_install();
    }
