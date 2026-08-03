<?php

class PerchMembers_Session
{
	static protected $instance;

	public $logged_in = false;
	public $session_id = null;
	public $dirty = false;


	private $details = array();

	public static function fetch()
	{	    
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }

        return self::$instance;
	}

	/*
	public function __destruct() 
	{
		if ($this->logged_in && $this->dirty) {
			$data = array(
				'sessionData' => serialize($this->details)
				);

			$DB = PerchDB::fetch();
			$DB->update(PERCH_DB_PREFIX.'members_sessions', $data, 'sessionID', $DB->pdb($this->session_id));
		}
	}
	*/

	public function load($row)
	{
		$this->details = $row;
	}

	private function alias_property($property)
	{
		switch($property) 
		{
			case 'email': 	$property = 'memberEmail'; 		break;
			case 'status': 	$property = 'memberStatus'; 	break;
			case 'expires': $property = 'memberExpires'; 	break;
			case 'auth_id': $property = 'memberAuthID'; 	break;
			case 'id': 		$property = 'memberID'; 		break;
		}

		return $property;
	}

	public function get($property)
	{
		if ($this->logged_in) {

			$property = $this->alias_property($property);

			if (isset($this->details[$property])) {
				return $this->details[$property];
			}
		}

		return false;
	}

	public function is_set($property)
	{
		if ($this->logged_in) {

			$property = $this->alias_property($property);

			if (isset($this->details[$property])) {
				return true;
			}
		}
		return false;
	}

	public function set($property, $value)
	{
		if ($this->logged_in) {
			$property = $this->alias_property($property);

			$this->details[$property] = $value;

			$this->dirty = true;
		}
	}

	public function has_tag($tag)
	{
		if ($this->logged_in) {

			if (isset($this->details['tags']) && is_array($this->details['tags'])) {
				return in_array($tag, $this->details['tags']);
			}

		}

		return false;
	}

	public function to_array()
	{
		$out = $this->details;

		$out['email']   = (isset($out['memberEmail']) 	? $out['memberEmail'] 	: '');
		$out['status']  = (isset($out['memberStatus']) 	? $out['memberStatus'] 	: '');
		$out['expires'] = (isset($out['memberExpires']) ? $out['memberExpires'] : '');
		$out['auth_id'] = (isset($out['memberAuthID']) 	? $out['memberAuthID'] 	: '');
		$out['id']      = (isset($out['memberID']) 		? $out['memberID'] 		: '');

		return $out;
	}

	public function get_tags()
	{
		if ($this->logged_in) {

			if (isset($this->details['tags'])) {
				return $this->details['tags'];
			}

		}

		return false;

	}


}
