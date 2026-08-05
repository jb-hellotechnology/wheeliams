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

	/* ---------------------------------------------------------------------
	 * BOM explosion engine (Foundation B)
	 *
	 * BOM rows are flat: id = parent componentID, partCode = child componentID,
	 * quantity = qty of child per one parent. "Multilevel" is recursive — a
	 * child that is itself an assembly has its own rows keyed by its id.
	 * ------------------------------------------------------------------- */

	/*
	 * Explode a component into a nested tree.
	 *   $componentID  component to explode
	 *   $qty          extended quantity of this node (top-level demand; default 1)
	 *   $level        recursion depth (0 = root)
	 *   $path         componentIDs already on this branch (cycle guard)
	 * Returns a node array (see buildNode) with a 'children' list, or null.
	 */
	public function explode($componentID, $qty=1, $level=0, $path=array()){
		$componentID = (int)$componentID;

		// Cycle guard: a component must not contain itself (directly or via a loop).
		if(in_array($componentID, $path, true)){
			return null;
		}
		$path[] = $componentID;

		$node = $this->buildNode($componentID, $qty, $level);
		if(!$node) return null;

		$children = array();
		foreach((array)$this->bom($componentID) as $row){
			$childID = (int)$row['partCode'];    // child componentID
			$unitQty = (float)$row['quantity'];  // qty per one parent
			$extQty  = $unitQty * $qty;           // total for this branch
			$child = $this->explode($childID, $extQty, $level + 1, $path);
			if($child){
				$child['unit_qty']  = $unitQty;                          // qty per single parent (for display)
				$child['bom_id']    = (int)$row['perch3_wheeliams_bomID']; // the BOM link row (for edit/delete)
				$child['parent_id'] = (int)$row['id'];                     // parent componentID
				$children[] = $child;
			}
		}
		$node['children'] = $children;
		return $node;
	}

	/* Explode starting from a part-code string rather than an id. */
	public function explodeByPartCode($partCode, $qty=1){
		$Components = new Wheeliams_Components();
		$c = $Components->byPartCode($partCode);
		if(!$c) return null;
		return $this->explode($c['perch3_wheeliams_componentID'], $qty);
	}

	/*
	 * Collate an exploded tree into flat per-component totals, summing a
	 * component wherever it appears. Excludes the root product itself.
	 * Returns an array keyed by componentID, each with a 'total_qty'.
	 */
	public function explodeFlat($componentID, $qty=1){
		$tree = $this->explode($componentID, $qty);
		$flat = array();
		if($tree){
			$this->collate($tree, $flat, true);
		}
		return $flat;
	}

	private function collate($node, &$flat, $isRoot=false){
		if(!$isRoot){
			$id = $node['componentID'];
			if(!isset($flat[$id])){
				$flat[$id] = $node;
				$flat[$id]['total_qty'] = 0;
				unset($flat[$id]['children'], $flat[$id]['unit_qty'], $flat[$id]['level']);
			}
			$flat[$id]['total_qty'] += $node['extended_qty'];
		}
		foreach($node['children'] as $child){
			$this->collate($child, $flat);
		}
	}

	/* Public single-node builder — used to reorder a leaf item (no BOM) directly. */
	public function componentNode($componentID, $qty=1){
		return $this->buildNode($componentID, $qty, 0);
	}

	/* Build a single node with component + current supplier + cost metadata. */
	private function buildNode($componentID, $extQty, $level){
		$Components = new Wheeliams_Components();
		$c = $Components->component($componentID);
		if(!$c) return null;
		$dyn = json_decode($c['dynamicFields'], true) ?: array();

		$supplierID = null; $supplierName = ''; $unitCost = 0.0;
		$sup = $this->db->get_row('SELECT wheeliams_supplierID FROM perch3_wheeliams_components_suppliers WHERE perch3_wheeliams_componentID='.(int)$componentID.' AND current=1 LIMIT 1');
		if($sup){
			$supplierID = $sup['wheeliams_supplierID'];
			$Suppliers = new Wheeliams_Suppliers();
			$s = $Suppliers->supplier($supplierID);
			$supplierName = $s['name'] ?? '';
			$unitCost = (float)$Components->getComponentPrice($componentID, $supplierID);
		}

		return array(
			'componentID'      => (int)$componentID,
			'partCode'         => $c['partCode'],
			'description'      => $dyn['part_description'] ?? '',
			'uom'              => $dyn['unit_of_measure'] ?? '',
			'type'             => $c['type'],
			'process_type'     => $dyn['process_type'] ?? '',
			'generic_material' => $dyn['generic_material'] ?? ($dyn['generic-material'] ?? ''),
			'batch_rounding'   => (float)($dyn['batch_rounding_quantity'] ?? 0),
			'is_service'       => ($c['type'] === 'services'),
			'level'            => $level,
			'bom_id'           => 0,         // set by caller for child nodes
			'parent_id'        => 0,
			'unit_qty'         => $extQty,   // root: equals extended; children overwritten by caller
			'extended_qty'     => $extQty,
			'supplierID'       => $supplierID,
			'supplierName'     => $supplierName,
			'unit_cost'        => $unitCost,
			'line_cost'        => $unitCost * $extQty,
		);
	}

}