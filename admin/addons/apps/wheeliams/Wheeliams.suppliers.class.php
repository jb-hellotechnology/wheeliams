<?php

class Wheeliams_Suppliers extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_suppliers';
	protected $pk        = 'wheeliams_supplierID';
	protected $singular_classname = 'Wheeliams_Supplier';
	
	protected $default_sort_column = 'wheeliams_supplierID';
	
	public $static_fields = array('wheeliams_supplierID','name', 'email', 'dynamicFields');	
	
	public function existing(){
		
		$sql = 'SELECT * FROM perch3_wheeliams_suppliers ORDER BY name ASC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function supplier($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_suppliers WHERE wheeliams_supplierID="'.$id.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
	public function assignContact($id, $data){
		
		$sql = 'INSERT INTO perch3_wheeliams_suppliers_contacts (wheeliams_supplierID, first_name, last_name, phone, email, contact_type) VALUES ("'.$data['wheeliams_supplierID'].'", "'.$data['first_name'].'", "'.$data['last_name'].'", "'.$data['phone'].'", "'.$data['email'].'", "'.$data['contact_type'].'")';
		$data = $this->db->execute($sql);
		
	}
	
	public function updateContact($id, $data){
		
		$sql = 'UPDATE perch3_wheeliams_suppliers_contacts SET wheeliams_supplierID="'.$data['wheeliams_supplierID'].'", first_name="'.$data['first_name'].'", last_name="'.$data['last_name'].'", phone="'.$data['phone'].'", email="'.$data['email'].'", contact_type="'.$data['contact_type'].'" WHERE contactID="'.$data['contactID'].'"';
		$data = $this->db->execute($sql);
		
	}
	
	public function deleteContact($id){
		
		$sql = 'DELETE FROM perch3_wheeliams_suppliers_contacts WHERE contactID="'.$id.'"';
		$data = $this->db->execute($sql);
		
	}
	
	public function contacts($id){

		$sql = 'SELECT * FROM perch3_wheeliams_suppliers_contacts WHERE wheeliams_supplierID="'.$id.'"';
		$data = $this->db->get_rows($sql);
		return $data;

	}

	/* The supplier's ORDERING contact (used for order emails), or null. */
	public function orderingContact($id){
		$sql = 'SELECT * FROM perch3_wheeliams_suppliers_contacts WHERE wheeliams_supplierID='.$this->db->pdb((int)$id).' AND UPPER(contact_type)="ORDERING" ORDER BY contactID ASC LIMIT 1';
		return $this->db->get_row($sql);
	}
	
	public function contact($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_suppliers_contacts WHERE contactID="'.$id.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
}