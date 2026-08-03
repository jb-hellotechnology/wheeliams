<?php

class Wheeliams_Staff_Member_Bankholidays extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_staff_bankholidays';
	protected $pk        = 'wheeliams_staff_bankholidayID';
	protected $singular_classname = 'Wheeliams_Staff_Member_Bankholiday';
	
	protected $default_sort_column = 'date';
	
	public $static_fields = array('wheeliams_staff_bankholidayID,','date');

	public function getDate($date){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_bankholidays WHERE date="'.$date.'"';
		$data = $this->db->get_count($sql);

		if($data>0){
			return true;
		}else{
			return false;
		}
		
	}
	
	public function getForYear($start,$end){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_bankholidays WHERE date>="'.$start.'" AND date<="'.$end.'"';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}	
}