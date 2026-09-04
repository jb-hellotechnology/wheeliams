<?php

class Wheeliams_Components extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_components';
	protected $pk        = 'perch3_wheeliams_componentID';
	protected $singular_classname = 'Wheeliams_Component';
	
	protected $default_sort_column = 'wheeliams_componentID';
	
	public $static_fields = array('wheeliams_componentID','type','dynamicFields');	
	
	public function existing($type){
		
		$sql = 'SELECT * FROM perch3_wheeliams_components WHERE type="'.$type.'" ORDER BY partCode ASC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function component($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_components WHERE perch3_wheeliams_componentID="'.$id.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
	public function byPartCode($partCode){

		$sql = 'SELECT * FROM perch3_wheeliams_components WHERE partCode="'.$partCode.'"';
		$data = $this->db->get_row($sql);
		return $data;

	}

	public function bySku($sku){

		// Shopify SKUs omit the part-code version suffix (SKU "A02-0019" vs part
		// code "A02-0019-A"), so match on the first 8 characters (the base code).
		$sku = trim((string)$sku);
		if($sku === '') return null;
		$base = substr($sku, 0, 8);
		$sql = 'SELECT * FROM perch3_wheeliams_components WHERE LEFT(partCode,8)='.$this->db->pdb($base).' ORDER BY partCode DESC LIMIT 1';
		return $this->db->get_row($sql);

	}
	
	public function assignSupplier($componentID, $supplierID){
		
		$sql = 'INSERT INTO perch3_wheeliams_components_suppliers (perch3_wheeliams_componentID, wheeliams_supplierID) VALUES ('.$componentID.', '.$supplierID.')';
		$data = $this->db->execute($sql);
		
	}
	
	public function removeSupplier($componentID, $supplierID){
		
		$sql = 'DELETE FROM perch3_wheeliams_components_suppliers WHERE perch3_wheeliams_componentID='.$componentID.' AND wheeliams_supplierID='.$supplierID;
		$data = $this->db->execute($sql);
		
		$sql = 'DELETE FROM perch3_wheeliams_components_suppliers_price WHERE componentID='.$componentID.' AND supplierID='.$supplierID;
		$data = $this->db->execute($sql);
		
	}
	
	public function componentSuppliers($componentID){
		
		$sql = 'SELECT * FROM perch3_wheeliams_components_suppliers WHERE perch3_wheeliams_componentID='.$componentID.' ORDER BY current DESC';
		$data = $this->db->get_rows($sql);
		return $data;
		
	}
	
	public function componentPrice($componentID,$supplierID,$price,$costDate=null){

		$this->ensurePriceColumns();
		// Cost date defaults to today but can be back-dated by the user.
		$costDate = ($costDate && strtotime($costDate)) ? date('Y-m-d', strtotime($costDate)) : date('Y-m-d');

		$sql = 'INSERT INTO perch3_wheeliams_components_suppliers_price (supplierID, componentID, price, cost_date) VALUES ('
			.$this->db->pdb((int)$supplierID).', '.$this->db->pdb((int)$componentID).', '.$this->db->pdb($price).', '.$this->db->pdb($costDate).')';
		return $this->db->execute($sql);

	}

	/* Add columns introduced after the price table first shipped. */
	protected function ensurePriceColumns(){
		$exists = $this->db->get_row("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perch3_wheeliams_components_suppliers_price' AND COLUMN_NAME='cost_date'");
		if(!$exists){
			$this->db->execute("ALTER TABLE perch3_wheeliams_components_suppliers_price ADD COLUMN cost_date DATE DEFAULT NULL");
		}
	}
	
	public function getComponentPrice($componentID,$supplierID){
		
		$sql = 'SELECT * FROM perch3_wheeliams_components_suppliers_price WHERE supplierID='.$supplierID.' AND componentID='.$componentID.' ORDER BY timestamp DESC LIMIT 1';
		$data = $this->db->get_row($sql);
		if($data){
			return $data['price'];
		}else{
			return '0.00';
		}		
		
	}
	
	public function latestComponentPrice($componentID){
		
		$sql = 'SELECT * FROM perch3_wheeliams_components_suppliers_price WHERE componentID='.$componentID.' ORDER BY timestamp DESC LIMIT 1';
		$data = $this->db->get_row($sql);
		return $data;		
		
	}
	
	public function componentPriceGraph($componentID, $supplierID){
		
		$sql = 'SELECT * FROM perch3_wheeliams_components_suppliers_price WHERE componentID='.$componentID.' AND supplierID='.$supplierID.' ORDER BY COALESCE(cost_date, DATE(timestamp)) DESC LIMIT 10';
		$data = $this->db->get_rows($sql);
		$dataset = '';
		foreach($data as $row){
			// Plot against the cost date where set, else fall back to the entry timestamp.
			$date = !empty($row['cost_date']) ? $row['cost_date'] : explode(" ", $row['timestamp'])[0];
			$dataset .= '{ timestamp: \''.$date.'\', price: '.number_format($row['price'], 2, '.', '').' },';
		}
		return $dataset;
		
	}
	
	public function setCurrentSupplier($componentID, $supplierID){
		
		$sql = 'UPDATE perch3_wheeliams_components_suppliers SET current=0 WHERE perch3_wheeliams_componentID='.$componentID;
		$data = $this->db->execute($sql);
		
		$sql = 'UPDATE perch3_wheeliams_components_suppliers SET current=1 WHERE perch3_wheeliams_componentID='.$componentID.' AND wheeliams_supplierID='.$supplierID;
		$data = $this->db->execute($sql);
		
	}
	
}