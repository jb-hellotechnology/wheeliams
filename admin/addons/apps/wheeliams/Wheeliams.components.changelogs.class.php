<?php

class Wheeliams_Components_Changelogs extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_components_changelog';
	protected $pk        = 'perch3_wheeliams_components_changelogID';
	protected $singular_classname = 'Wheeliams_Components_Changelog';
	
	protected $default_sort_column = 'wheeliams_components_changelogID';
	
	public $static_fields = array('wheeliams_components_changelogID','settingID','type','action','oldValues','newValues','userID','timestamp');	
	
	public function logs($type,$id){
		
		$sql = 'SELECT c.*, m.memberEmail 
		FROM perch3_wheeliams_components_changelog c
		LEFT JOIN perch3_members m ON m.memberID = c.userID
		WHERE c.type="'.$type.'" AND c.componentID="'.$id.'" 
		ORDER BY c.timestamp DESC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
}