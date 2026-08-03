<?php

class Wheeliams_Staff_Member_Sickdays extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_staff_sickdays';
	protected $pk        = 'wheeliams_staff_sickdayID';
	protected $singular_classname = 'Wheeliams_Staff_Member_Sickday';
	
	protected $default_sort_column = 'date';
	
	public $static_fields = array('wheeliams_staff_sickdayID,','staffID','date');	
	
	public function getDate($staffID,$date){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_sickdays WHERE staffID="'.$staffID.'" AND date="'.$date.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
	public function getForYear($staffID,$start,$end){
		
		$sql = 'SELECT COUNT(*) AS count FROM perch3_wheeliams_staff_sickdays WHERE staffID="'.$staffID.'" AND (date>="'.$start.'" AND date<="'.$end.'")';
		$data = $this->db->get_rows($sql);
		return $data['count'];
		
	}
	
	public function getDays($staffID){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_sickdays WHERE staffID="'.$staffID.'" ORDER BY date DESC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
}