<?php

class Wheeliams_Suppliers_Changelogs extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_suppliers_changelog';
	protected $pk        = 'wheeliams_suppliers_changelogID';
	protected $singular_classname = 'Wheeliams_Suppliers_Changelog';
	
	protected $default_sort_column = 'wheeliams_suppliers_changelogID';
	
	public $static_fields = array('wheeliams_suppliers_changelogID','supplierID','action','oldValues','newValues','userID','timestamp');	
	
	public function logs($id){
		
		$sql = 'SELECT c.*, m.memberEmail 
		FROM perch3_wheeliams_suppliers_changelog c
		LEFT JOIN perch3_members m ON m.memberID = c.userID
		WHERE c.supplierID="'.$id.'" 
		ORDER BY c.timestamp DESC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
}