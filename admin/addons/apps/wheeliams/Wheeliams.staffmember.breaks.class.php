<?php

class Wheeliams_Staff_Member_Breaks extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_staff_breaks';
	protected $pk        = 'wheeliams_staff_breakID';
	protected $singular_classname = 'Wheeliams_Staff_Member_Break';
	
	protected $default_sort_column = 'timeStamp';
	
	public $static_fields = array('wheeliams_staff_breakID,','staffID','date','breakLength');	
	
	public function staff_break($date, $breakLength){
		
		$Session = PerchMembers_Session::fetch();
		$memberID = $Session->get('memberID');
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_breaks WHERE staffID="'.$memberID.'" AND date="'.$date.'"';
		$data = $this->db->get_row($sql);
		if($data){
			$sql = 'UPDATE perch3_wheeliams_staff_breaks SET breakLength="'.$breakLength.'" WHERE staffID="'.$memberID.'" AND date="'.$date.'"';
			$data = $this->db->execute($sql);
		}else{
			$sql = 'INSERT INTO perch3_wheeliams_staff_breaks (staffID, date, breakLength) VALUES ("'.$memberID.'", "'.$date.'", "'.$breakLength.'")';
			$data = $this->db->execute($sql);
		}
		
	}
	
	public function add_staff_break($memberID, $date, $breakLength){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_breaks WHERE staffID="'.$memberID.'" AND date="'.$date.'"';
		$data = $this->db->get_row($sql);
		if($data){
			$sql = 'UPDATE perch3_wheeliams_staff_breaks SET breakLength="'.$breakLength.'" WHERE staffID="'.$memberID.'" AND date="'.$date.'"';
			$data = $this->db->execute($sql);
		}else{
			$sql = 'INSERT INTO perch3_wheeliams_staff_breaks (staffID, date, breakLength) VALUES ("'.$memberID.'", "'.$date.'", "'.$breakLength.'")';
			$data = $this->db->execute($sql);
		}
		
	}
	
	public function get_break_length($date){
		
		$Session = PerchMembers_Session::fetch();
		$memberID = $Session->get('memberID');
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_breaks WHERE staffID="'.$memberID.'" AND date="'.$date.'"';
		$data = $this->db->get_row($sql);
		if($data){
			return $data['breakLength'];
		}else{
			return 0;
		}
		
	}
	
	public function breaks($memberID,$date){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_breaks WHERE staffID="'.$memberID.'" AND date="'.$date.'"';
		$data = $this->db->get_row($sql);
		
		if($data){
			return $data['breakLength'];
		}else{
			return 0;
		}
		
	}
	
}