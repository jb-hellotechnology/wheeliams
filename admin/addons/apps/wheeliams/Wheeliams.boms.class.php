<?php

class Wheeliams_Boms extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_boms';
	protected $pk        = 'perch3_wheeliams_bomID';
	protected $singular_classname = 'Wheeliams_Bom';
	
	protected $default_sort_column = 'wheeliams_bomID';
	
	public $static_fields = array('wheeliams_bomID','type','dynamicFields');	
	
	public function existing($type){
		
		if($type=='manufactured'){
			$prefix = 'A02';
			$sql = 'SELECT * FROM perch3_wheeliams_components WHERE type="'.$type.'" AND LEFT (partCode, 3)="'.$prefix.'" ORDER BY partCode ASC';
		}else{
			$prefix = 'A06';
			$sql = 'SELECT * FROM perch3_wheeliams_components WHERE LEFT (partCode, 3)="'.$prefix.'" ORDER BY partCode ASC';
		}
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function bom($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_boms WHERE id="'.$id.'"';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function bomComponent($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_boms WHERE perch3_wheeliams_bomID="'.$id.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
	public function addToBom($type,$id,$component,$quantity,$userID){
		
		$sql = 'INSERT INTO perch3_wheeliams_boms (type, id, partCode, quantity, userID) VALUES ("'.$type.'", "'.$id.'", "'.$component.'", "'.$quantity.'", "'.$userID.'")';
		$data = $this->db->execute($sql);
		
	}
	
	public function updateBom($id,$quantity,$userID){
		
		$sql = 'UPDATE perch3_wheeliams_boms SET quantity="'.$quantity.'", userID="'.$userID.'" WHERE perch3_wheeliams_bomID='.$id;
		$data = $this->db->execute($sql);
		
	}
	
	public function byPartCode($partCode){
		
		$sql = 'SELECT * FROM perch3_wheeliams_boms WHERE partCode="'.$partCode.'" ORDER BY partCode ASC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function saveNotes($data){
		
		$bomID = $data['bomID'];
		$type = $data['type'];
		$notes = $data['notes'];
		
		$sql = 'SELECT * FROM perch3_wheeliams_boms_notes WHERE bomID="'.$bomID.'" AND bomType="'.$type.'"';
		$row = $this->db->get_row($sql);
		if($row){
			$sql = 'UPDATE perch3_wheeliams_boms_notes SET notes="'.$notes.'" WHERE bomType="'.$type.'" AND bomID="'.$bomID.'"';
			$data = $this->db->execute($sql);
		}else{
			$sql = 'INSERT INTO perch3_wheeliams_boms_notes (bomType, bomID, notes) VALUES ("'.$type.'", "'.$bomID.'", "'.$notes.'")';
			$data = $this->db->execute($sql);
		}
		
	}
	
	public function notes($type, $id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_boms_notes WHERE bomID="'.$id.'" AND bomType="'.$type.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
}