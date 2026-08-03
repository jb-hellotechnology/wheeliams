<?php

class Wheeliams_Settings_Changelogs extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_settings_changelog';
	protected $pk        = 'perch3_wheeliams_settings_changelogID';
	protected $singular_classname = 'Wheeliams_Settings_Changelog';
	
	protected $default_sort_column = 'wheeliams_settings_changelogID';
	
	public $static_fields = array('wheeliams_settings_changelogID','settingID','type','action','oldValues','newValues','userID','timestamp');	
	
	public function logs($type,$id){
		
		$sql = 'SELECT c.*, m.memberEmail 
		FROM perch3_wheeliams_settings_changelog c
		LEFT JOIN perch3_members m ON m.memberID = c.userID
		WHERE c.type="'.$type.'" AND c.settingID="'.$id.'" 
		ORDER BY c.timestamp DESC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
}