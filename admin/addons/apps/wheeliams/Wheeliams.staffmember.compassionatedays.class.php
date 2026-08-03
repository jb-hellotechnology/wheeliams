<?php

class Wheeliams_Staff_Member_Compassionatedays extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_staff_compassionatedays';
	protected $pk        = 'wheeliams_staff_compassionatedayID';
	protected $singular_classname = 'Wheeliams_Staff_Member_Compassionateday';
	
	protected $default_sort_column = 'date';
	
	public $static_fields = array('wheeliams_staff_compassionatedayID,','staffID','date');	
	
	public function getDate($staffID,$date){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_compassionatedays WHERE staffID="'.$staffID.'" AND date="'.$date.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
	public function getForYear($staffID,$start,$end){
		
		$sql = 'SELECT COUNT(*) as count FROM perch3_wheeliams_staff_compassionatedays WHERE staffID="'.$staffID.'" AND (date>="'.$start.'" AND date<="'.$end.'")';
		$data = $this->db->get_rows($sql);
		return $data['count'];
		
	}
	
	public function getDays($staffID){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_compassionatedays WHERE staffID="'.$staffID.'" ORDER BY date DESC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
}