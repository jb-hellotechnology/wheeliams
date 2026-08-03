<?php

class Wheeliams_Staff_Members extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_staff';
	protected $pk        = 'wheeliams_staffID';
	protected $singular_classname = 'Wheeliams_Staff_Member';
	
	protected $default_sort_column = 'name';
	
	public $static_fields = array('wheeliams_staffID,','name','email','phone','address','startDate','memberID','staffDynamicFields');	
	
	public function staff($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff WHERE wheeliams_staffID='.$id;
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
	public function all_staff(){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff ORDER BY name ASC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function register_with_form($form, $member){
		
		$data = $form->data;
		print_r($member);
		$sql = 'INSERT INTO perch3_wheeliams_staff (name, email, memberID) VALUES ("'.$data['first_name'].' '.$data['last_name'].'", "'.$data['email'].'", "'.$member.'")';
		$this->db->execute($sql);
		
	}
	
	public function byMemberID($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff WHERE memberID="'.$id.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
	public function signedIn(){
		
		$clockedIn = array();
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff ORDER BY name ASC';
		$data = $this->db->get_rows($sql);
		foreach ($data as $staff){
			$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE staffID="'.$staff['memberID'].'" ORDER BY wheeliams_staff_timeID DESC LIMIT 1';
			$data = $this->db->get_row($sql);
			if($data['timeType']=='clock in'){
				array_push($clockedIn, $staff['name']);
			}
		}
		
		return $clockedIn;
		
	}
	
	public function rfid($staffID){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_rfid WHERE wheeliams_staffID="'.$staffID.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
	public function updateRFID($staffID, $name, $rfid){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_rfid WHERE wheeliams_staffID="'.$staffID.'"';
		$data = $this->db->get_row($sql);
		if($data){
			$sql = 'UPDATE perch3_wheeliams_staff_rfid SET rfid="'.$rfid.'", name="'.$name.'" WHERE wheeliams_staffID="'.$staffID.'"';
			$this->db->execute($sql);
		}else{
			$sql = 'INSERT INTO perch3_wheeliams_staff_rfid (wheeliams_staffID, rfid, name) VALUES ("'.$staffID.'", "'.$rfid.'", "'.$name.'")';
			$this->db->execute($sql);
		}
		
	}
}