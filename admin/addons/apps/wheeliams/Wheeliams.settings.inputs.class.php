<?php

class Wheeliams_Settings_Inputs extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_settings_inputs';
	protected $pk        = 'perch3_wheeliams_settings_inputID';
	protected $singular_classname = 'Wheeliams_Settings_Input';
	
	protected $default_sort_column = 'wheeliams_settings_inputID';
	
	public $static_fields = array('wheeliams_settings_inputID','type','dynamicFields');	
	
	public function existing($type){
		
		$sql = 'SELECT * FROM perch3_wheeliams_settings_inputs WHERE type="'.$type.'" ORDER BY timestamp DESC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function setting($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_settings_inputs WHERE perch3_wheeliams_settings_inputID="'.$id.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
	public function setting_values($id, $value, $help){
		
		$sql = 'SELECT * FROM perch3_wheeliams_settings_inputs WHERE type="'.$id.'"';
		$data = $this->db->get_rows($sql);
		$values = array();
		foreach($data as $row){
			$json = json_decode($row['dynamicFields'], true);
			if($help){
				$item = '';
				$item = $json[$value];
				if($json[$help]){
					$item .= " (".$json[$help].")";
				}
				$item .= "|".$json[$value];
				$values[] = $item;
			}else{
				if($json[$value]){
					$values[] = $json[$value]."|".$json[$value];
				}
			}
		}
		sort($values, SORT_NATURAL);
		array_unshift($values, " | ");
		return implode(",", $values);
		
	}
	
	public function members_list(){
		
		$sql = 'SELECT * FROM perch3_members';
		$data = $this->db->get_rows($sql);
		$values = array();
		foreach($data as $row){
			$json = json_decode($row['memberProperties'], true);
			$values[] = $json['first_name'].' '.$json['last_name'];
		}
		return implode(",", $values);
		
	}
	
}