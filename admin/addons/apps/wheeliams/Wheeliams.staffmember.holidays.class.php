<?php

class Wheeliams_Staff_Member_Holidays extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_staff_holidays';
	protected $pk        = 'wheeliams_staff_holidayID';
	protected $singular_classname = 'Wheeliams_Staff_Member_Holiday';
	
	protected $default_sort_column = 'date';
	
	public $static_fields = array('wheeliams_staff_holidayID,','date','staffID','length');

	public function getDate($staffID, $date){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_holidays WHERE staffID="'.$staffID.'" AND date="'.$date.'"';
		$data = $this->db->get_rows($sql);

		return($data);
		
	}
	
	public function getForYear($staffID,$start,$end){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_holidays WHERE staffID="'.$staffID.'" AND (date>="'.$start.'" AND date<="'.$end.'")';
		$data = $this->db->get_rows($sql);
		$holidayLength = 0;
		foreach($data as $holiday){
			$holidayLength = $holidayLength + $holiday['length'];
		}
		return $holidayLength;
		
	}
	
	public function getListForYear($staffID,$start,$end){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_holidays WHERE staffID="'.$staffID.'" AND (date>="'.$start.'" AND date<="'.$end.'") ORDER BY date ASC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function all_holidays($staffID){
		
		if($staffID){
			$sql = 'SELECT * FROM perch3_wheeliams_staff_holidays WHERE staffID="'.$staffID.'" ORDER BY date ASC';
		}else{
			$sql = 'SELECT * FROM perch3_wheeliams_staff_holidays ORDER BY date ASC';
		}
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function byStaffID($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_holidays WHERE staffID="'.$id.'" ORDER BY date DESC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function request_holiday($form, $staffID){
		
		$start = $form->data['start'];
		$end = $form->data['end'];
		
		$sql = 'INSERT INTO perch3_wheeliams_staff_holidays_requests (staffID, start, end) VALUES ("'.$staffID.'", "'.$start.'", "'.$end.'")';
		$data = $this->db->execute($sql);
		
	}	
	
	public function staff_holiday_requests($staffID){
		
		if($staffID){
			$sql = 'SELECT * FROM perch3_wheeliams_staff_holidays_requests WHERE staffID="'.$staffID.'"';
		}else{
			$sql = 'SELECT * FROM perch3_wheeliams_staff_holidays_requests';
		}
		$data = $this->db->get_rows($sql);
		
		return $data;
		
	}
	
	public function delete_request($id){
		
		$sql = 'DELETE FROM perch3_wheeliams_staff_holidays_requests WHERE wheeliams_staff_holidays_requestID="'.$id.'"';
		$data = $this->db->execute($sql);
		
	}
	
	public function approve_request($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_holidays_requests WHERE wheeliams_staff_holidays_requestID="'.$id.'"';
		$data = $this->db->get_row($sql);
		
		$group = rand();
		$startDate = new DateTime($data['start']);
		$endDate = new DateTime($data['end']);
		$endDate->modify('+1 day'); // include the end date
		
		$interval = new DateInterval('P1D');
		$period = new DatePeriod($startDate, $interval, $endDate);
		
		foreach ($period as $date) {
			$thisDate = $date->format('Y-m-d');
			$sql = 'INSERT INTO perch3_wheeliams_staff_holidays (date, staffID, length, groupID) VALUES ("'.$thisDate.'", "'.$data['staffID'].'", "1", "'.$group.'")';
			$this->db->execute($sql);
		}
		
		$sql = 'DELETE FROM perch3_wheeliams_staff_holidays_requests WHERE wheeliams_staff_holidays_requestID="'.$id.'"';
		$this->db->execute($sql);
		
	}
	
	public function get_holiday($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_holidays WHERE groupID = (SELECT groupID FROM perch3_wheeliams_staff_holidays WHERE wheeliams_staff_holidayID="'.$id.'")';
		$group = $this->db->get_rows($sql);
		
		return $group;
		
	}
	
	public function delete_holiday($id, $type){
		
		if($type=='day'){
			$sql = 'DELETE FROM perch3_wheeliams_staff_holidays WHERE wheeliams_staff_holidayID="'.$id.'"';
			$this->db->execute($sql);
		}else{
			$sql = 'DELETE FROM perch3_wheeliams_staff_holidays WHERE groupID = (SELECT groupID FROM (SELECT groupID FROM perch3_wheeliams_staff_holidays WHERE wheeliams_staff_holidayID="'.$id.'") AS tmp) AND groupID IS NOT NULL AND groupID != ""';
			$this->db->execute($sql);
		}
		
	}
}
