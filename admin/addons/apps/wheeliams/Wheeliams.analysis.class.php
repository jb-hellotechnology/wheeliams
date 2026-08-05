<?php

/**
 * Order Analysis engine (Phase 3).
 *
 * On demand: read saleable products on order (Shopify), flag those needing
 * reorder, compute a target order quantity, explode each flagged product's BOM,
 * collate component quantities across products, batch-round to an actual order
 * quantity (AQ), and save the result as a timestamped reorder list.
 *
 * NOTE: the exact reorder trigger/target maths is implemented per the client's
 * written spec and is deliberately isolated in run() — the client may refine it.
 */
class Wheeliams_Analysis extends PerchAPI_Factory
{
    protected $table = 'wheeliams_analysis_runs';
    protected $pk    = 'perch3_wheeliams_analysis_runID';
    protected $singular_classname = 'Wheeliams_Analysis_Run';

    protected $runs_table  = 'perch3_wheeliams_analysis_runs';
    protected $lines_table = 'perch3_wheeliams_reorder_lines';

    public function install()
    {
        $this->db->execute("CREATE TABLE IF NOT EXISTS ".$this->runs_table." (
            perch3_wheeliams_analysis_runID INT UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at DATETIME DEFAULT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            note VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (perch3_wheeliams_analysis_runID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->execute("CREATE TABLE IF NOT EXISTS ".$this->lines_table." (
            perch3_wheeliams_reorder_lineID INT UNSIGNED NOT NULL AUTO_INCREMENT,
            runID INT UNSIGNED NOT NULL,
            componentID INT UNSIGNED NOT NULL,
            partCode VARCHAR(191) DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            uom VARCHAR(32) DEFAULT NULL,
            type VARCHAR(32) DEFAULT NULL,
            process_type VARCHAR(191) DEFAULT NULL,
            supplierID INT UNSIGNED DEFAULT NULL,
            supplierName VARCHAR(191) DEFAULT NULL,
            used_on TEXT,
            total_qty DECIMAL(14,3) NOT NULL DEFAULT 0,
            actual_qty DECIMAL(14,3) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT NULL,
            PRIMARY KEY (perch3_wheeliams_reorder_lineID),
            KEY runID (runID),
            KEY componentID (componentID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /* Run an analysis and save it. Returns the new run id.
       Pass $demand to inject demand (testing); null reads live Shopify orders. */
    public function run($memberID, $note='', $demand=null)
    {
        $Stock = new Wheeliams_Stock();
        $Boms  = new Wheeliams_Boms();

        if($demand === null){
            $demand = wheeliams_demand_on_order(); // [componentID => ['component'=>row, 'qty'=>float]]
        }

        // Collate required component quantities across all flagged products.
        $collated = array(); // componentID => ['node'=>..., 'qt'=>float, 'used_on'=>[partCode=>true]]

        foreach($demand as $productID => $d){
            $dyn     = json_decode($d['component']['dynamicFields'], true) ?: array();
            $stock   = $Stock->level($productID);
            $planned = $Stock->planned($productID);
            $reorder = (float)($dyn['reorder_quantity'] ?? 0);
            $maxlvl  = (float)($dyn['maximum_stock_level'] ?? 0);
            $onOrder = (float)$d['qty'];

            // Flag when (stock - quantity on order) falls to or below the reorder level.
            if(($stock - $onOrder) > $reorder) continue;

            // Target order quantity to refill toward the maximum stock level.
            $target = $maxlvl - $stock - $planned;
            if($target <= 0) continue;

            $productCode = $d['component']['partCode'];

            foreach($Boms->explodeFlat($productID, $target) as $cid => $node){
                if(!isset($collated[$cid])){
                    $collated[$cid] = array('node' => $node, 'qt' => 0, 'used_on' => array());
                }
                $collated[$cid]['qt'] += $node['total_qty'];
                $collated[$cid]['used_on'][$productCode] = true;
            }
        }

        // Save the run header.
        $run = $this->create(array(
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => (int)$memberID,
            'note'       => $note,
        ));
        $runID = (int)$run->perch3_wheeliams_analysis_runID();

        // Save one batch-rounded reorder line per collated component.
        foreach($collated as $cid => $c){
            $node = $c['node'];
            $qt   = $c['qt'];
            $aq   = wheeliams_batch_round($qt, (float)($node['batch_rounding'] ?? 0));

            $this->db->insert($this->lines_table, array(
                'runID'        => $runID,
                'componentID'  => (int)$cid,
                'partCode'     => $node['partCode'],
                'description'  => $node['description'],
                'uom'          => $node['uom'],
                'type'         => $node['type'],
                'process_type' => $node['process_type'],
                'supplierID'   => (int)$node['supplierID'],
                'supplierName' => $node['supplierName'],
                'used_on'      => implode(', ', array_keys($c['used_on'])),
                'total_qty'    => $qt,
                'actual_qty'   => $aq,
                'created_at'   => date('Y-m-d H:i:s'),
            ));
        }

        return $runID;
    }

    public function latestRunID()
    {
        $row = $this->db->get_row('SELECT perch3_wheeliams_analysis_runID FROM '.$this->runs_table.' ORDER BY perch3_wheeliams_analysis_runID DESC LIMIT 1');
        return $row ? (int)$row['perch3_wheeliams_analysis_runID'] : 0;
    }

    public function runInfo($runID)
    {
        return $this->db->get_row('SELECT * FROM '.$this->runs_table.' WHERE perch3_wheeliams_analysis_runID='.$this->db->pdb((int)$runID));
    }

    public function allRuns()
    {
        return $this->db->get_rows('SELECT * FROM '.$this->runs_table.' ORDER BY perch3_wheeliams_analysis_runID DESC');
    }

    /* Reorder lines for a run, sorted by supplier, then used-on product, then part code. */
    public function lines($runID)
    {
        $sql = 'SELECT * FROM '.$this->lines_table.' WHERE runID='.$this->db->pdb((int)$runID).
               ' ORDER BY supplierName ASC, used_on ASC, partCode ASC';
        return $this->db->get_rows($sql);
    }

    public function updateAQ($lineID, $aq)
    {
        $this->db->update($this->lines_table, array('actual_qty' => (float)$aq), 'perch3_wheeliams_reorder_lineID', (int)$lineID);
    }
}
