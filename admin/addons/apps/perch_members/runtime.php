<?php

    if (!defined('PERCH_MEMBERS_SESSION_TIME')) define('PERCH_MEMBERS_SESSION_TIME', '5 DAYS');
    if (!defined('PERCH_MEMBERS_COOKIE'))       define('PERCH_MEMBERS_COOKIE', 'p_m');

    spl_autoload_register(function($class_name){
        if (strpos($class_name, 'PerchMembers')===0) {
            include(__DIR__.'/'.$class_name.'.class.php');
            return true;
        }
        return false;
    });
    
    include('../wheeliams/Wheeliams.staffmember.class.php');
    include('../wheeliams/Wheeliams.staffmembers.class.php');

    PerchSystem::register_template_handler('PerchMembers_Template');

    if (PERCH_RUNWAY_ROUTED) {
        $members_init = function(){
            $API  = new PerchAPI(1.0, 'perch_members');
            $API->on('page.loaded', 'perch_members_init');
        };
        $members_init();
    }else{
        perch_members_init();
    }

    function perch_members_init()
    {
        perch_members_recover_session();
        perch_members_check_page_access();
    }


	function perch_members_form_handler($SubmittedForm)
    {
    	if ($SubmittedForm->validate()) {

    		$API  = new PerchAPI(1.0, 'perch_members');

    		switch($SubmittedForm->formID) {

    			case 'login':
    				$PerchMembers_Auth = new PerchMembers_Auth($API);
    				if (!$PerchMembers_Auth->handle_login($SubmittedForm)) {
                        $SubmittedForm->throw_error('login');
                    }
    				break;

                case 'profile':
                    $Session = PerchMembers_Session::fetch();
                    if ($Session->logged_in && $Session->get('token')==$SubmittedForm->data['token']) {
                        $Members = new PerchMembers_Members($API);
                        if (is_object($Members)) $Member = $Members->find($Session->get('memberID'));
                        if (is_object($Member)) {
                            $Member->update_profile($SubmittedForm);
                            $PerchMembers_Auth = new PerchMembers_Auth($API);
                            $PerchMembers_Auth->refresh_session_data($Member);
                        }
                    }else{
                        $SubmittedForm->throw_error('profile');
                    }
                    break;

                case 'register':
                    $Members = new PerchMembers_Members($API);
                    $Staff = new Wheeliams_Staff_Members($API);
                    $Member = $Members->register_with_form($SubmittedForm);
                    $Staff->register_with_form($SubmittedForm, $Member);
                    
                    break;

                case 'reset':
                    $Members = new PerchMembers_Members($API);
                    $Members->reset_member_password($SubmittedForm);
                    break;

                case 'password':
                    $Session = PerchMembers_Session::fetch();
                    if ($Session->logged_in && $Session->get('token')==$SubmittedForm->data['token']) {
                        $Members = new PerchMembers_Members($API);
                        if (is_object($Members)) $Member = $Members->find($Session->get('memberID'));
                        if (is_object($Member)) $Member->change_password($SubmittedForm);
                    }else{
                        $SubmittedForm->throw_error('password');
                    }
                    break;


    		}

            if (!$SubmittedForm->redispatched) {
                $Tag = $SubmittedForm->get_form_attributes();
                PerchUtil::mark('here mem');
                if (is_object($Tag) && $Tag->next()) {
                    $Perch = Perch::fetch();
                    if (!$Perch->get_form_errors($SubmittedForm->formID)) {
                        PerchUtil::redirect($Tag->next());    
                    }else{
                        PerchUtil::debug($Perch->get_form_errors($SubmittedForm->formID), 'error');
                    }
                }    
            }else{

            }
            

    	}

        $Perch = Perch::fetch();
        PerchUtil::debug($Perch->get_form_errors($SubmittedForm->formID));
    }



	function perch_members_login_form($opts=array(), $return=false)
	{
		$API  = new PerchAPI(1.0, 'perch_members');

        $defaults = array();
        $defaults['template']        = 'login/login_form.html';

        if (is_array($opts)) {
            $opts = array_merge($defaults, $opts);
        }else{
            $opts = $defaults;
        }

        $Template = $API->get('Template');
        $Template->set('members/'.$opts['template'], 'members');
        $html = $Template->render(array());
        $html = $Template->apply_runtime_post_processing($html);

        if ($return) return $html;
        echo $html;

	}


    function perch_members_recover_session()
    {
        $API  = new PerchAPI(1.0, 'perch_members');
        $PerchMembers_Auth = new PerchMembers_Auth($API);
        $PerchMembers_Auth->recover_session();
    }

    function perch_members_check_page_access()
    {
        $Session = PerchMembers_Session::fetch();

        if ($Session->logged_in) {
            $user_tags = $Session->get_tags();
        }else{
            $user_tags = array();
        }

        if (!is_array($user_tags)) $user_tags = array();
        $Page = PerchSystem::get_page_object();


        if (!$Page) {
            $Pages = new PerchContent_Pages;
            $Perch = Perch::fetch();
            $Page = $Pages->find_by_path($Perch->get_page());
            if ($Page instanceof PerchContent_Page) {
                PerchSystem::set_page_object($Page);
            }
        }

        if ($Page) {
            $page_tags = $Page->access_tags();

            if (!is_array($page_tags)) $page_tags = array();

            if (PerchUtil::count($page_tags)) {
                $intersection = array_intersect($user_tags, $page_tags);

                if (PerchUtil::count($intersection)===0) {
                    // no access!
                    $API  = new PerchAPI(1.0, 'perch_members');
                    $Settings = $API->get('Settings');
                    $redirect_url = $Settings->get('perch_members_login_page')->val();
                    if ($redirect_url) {
                        $redirect_url = str_replace('{returnURL}', $Perch->get_page(), $redirect_url);
                        PerchUtil::redirect($redirect_url);
                    }else{
                        die('Access denied.');
                    }
                }
            }
        }
    }

    function perch_member_logged_in()
    {
        $Session = PerchMembers_Session::fetch();
        return $Session->logged_in;
    }

    function perch_member_log_out()
    {
        $API  = new PerchAPI(1.0, 'perch_members');
        $PerchMembers_Auth = new PerchMembers_Auth($API);
        $PerchMembers_Auth->log_out();
    }

    function perch_member_get($property=null)
    {
        if ($property) {
            $Session = PerchMembers_Session::fetch();

            if ($Session->logged_in) {
                return $Session->get($property);
            }
        }

        return false;
    }

    function perch_member_is_passwordless()
    {
        $Session = PerchMembers_Session::fetch();
        if ($Session->logged_in) {
            $API  = new PerchAPI(1.0, 'perch_members');
            $Members = new PerchMembers_Members($API);
            if (is_object($Members)) $Member = $Members->find($Session->get('memberID'));
            if (is_object($Member)) {
                if (is_null($Member->memberPassword())) {
                    return true;
                }
            }
            return false;
        }

        return null;
    }

    function perch_member_has_tag($tag=false)
    {
        if ($tag) {
            $Session = PerchMembers_Session::fetch();

            if ($Session->logged_in) {
                return $Session->has_tag($tag);
            }
        }

        return false;
    }


    function perch_member_add_tag($tag, $expiry_date=false)
    {
        if ($tag) {
            $Session = PerchMembers_Session::fetch();

            if ($Session->logged_in) {
                if (!$Session->has_tag($tag)) {
                    $API  = new PerchAPI(1.0, 'perch_members');
                    $Tags = new PerchMembers_Tags($API);
                    $Tag  = $Tags->find_or_create($tag);
                    if (is_object($Tag)) {
                        $Tag->add_to_member($Session->get('memberID'), $expiry_date);
                        if (!headers_sent()) {
                            $Members = new PerchMembers_Members($API);
                            $Member = $Members->find($Session->get('memberID'));
                            $PerchMembers_Auth = new PerchMembers_Auth($API);
                            $PerchMembers_Auth->refresh_session_data($Member);
                        }
                        return true;
                    }
                }
            }
        }

        return false;
    }

    function perch_member_remove_tag($tag)
    {
        if ($tag) {
            $Session = PerchMembers_Session::fetch();

            if ($Session->logged_in) {
                if ($Session->has_tag($tag)) {
                    $API  = new PerchAPI(1.0, 'perch_members');
                    $Tags = new PerchMembers_Tags($API);
                    $Tag  = $Tags->find_by_tag($tag);
                    if (is_object($Tag)) {
                        $Tag->remove_from_member($Session->get('memberID'));
                        if (!headers_sent()) {
                            $Members = new PerchMembers_Members($API);
                            $Member = $Members->find($Session->get('memberID'));
                            $PerchMembers_Auth = new PerchMembers_Auth($API);
                            $PerchMembers_Auth->refresh_session_data($Member);
                        }
                        return true;
                    }
                }
            }
        }

        return false;
    }



    function perch_member_form($template, $return=false)
    {
        $API  = new PerchAPI(1.0, 'perch_members');
        $Template = $API->get('Template');
        $Template->set(PerchUtil::file_path('members/forms/'.$template), 'forms');

        $Session = PerchMembers_Session::fetch();

        $data = $Session->to_array();

        $html = $Template->render($data);
        $html = $Template->apply_runtime_post_processing($html, $data);

        if ($return) return $html;
        echo $html;
    }

    function perch_members_secure_download($file, $bucket_name='default', $force_download=true)
    {

        $Perch = Perch::fetch();
        $bucket = $Perch->get_resource_bucket($bucket_name);

        if ($bucket) {

            $file_path = realpath(PerchUtil::file_path($bucket['file_path'].'/'.ltrim($file, '/')));

            $file_name = ltrim($file, '/');

            // check we're still within the bucket's folder, to secure against bad file paths
            if (substr($file_path, 0, strlen($bucket['file_path'])) == $bucket['file_path']) {

                if (file_exists($file_path)) {


                    // find file type
                    if (function_exists('finfo_file')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimetype = finfo_file($finfo, $file_path);
                        finfo_close($finfo);
                    }else{
                        $mimetype = mime_content_type($file_path);
                    }

                    if (!$mimetype) $mimetype = 'application/octet-stream';

                    header("Content-Type: $mimetype", true);

                    if ($force_download) {
                        header("Content-Disposition: attachment; filename=\"".$file_name."\"", true);
                        header("Content-Length: ".filesize($file_path), true);
                        header("Content-Transfer-Encoding: binary\n", true);
                    }

                    if ($stream = fopen($file_path, 'rb')){
                        ob_end_flush();
                        while(!feof($stream) && connection_status() == 0){
                            print(fread($stream, 8192));
                            flush();
                        }
                        fclose($stream);
                    }

                    exit;
                }

            }

        }
    }
