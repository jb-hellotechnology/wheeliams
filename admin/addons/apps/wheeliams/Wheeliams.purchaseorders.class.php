<?php

/**
 * Purchase Orders (Phase 3).
 *
 * A PO (or enquiry) is created per supplier from a reorder run — its lines are a
 * snapshot of the ordered components. Check-in receives quantities back into
 * stock via the stock ledger, attributed to the receiving member.
 */
class Wheeliams_Purchase_Orders extends PerchAPI_Factory
{
    protected $table = 'wheeliams_purchase_orders';
    protected $pk    = 'perch3_wheeliams_purchase_orderID';
    protected $singular_classname = 'Wheeliams_Purchase_Order';

    protected $po_table   = 'perch3_wheeliams_purchase_orders';
    protected $line_table = 'perch3_wheeliams_purchase_order_lines';

    public function install()
    {
        $this->db->execute("CREATE TABLE IF NOT EXISTS ".$this->po_table." (
            perch3_wheeliams_purchase_orderID INT UNSIGNED NOT NULL AUTO_INCREMENT,
            number VARCHAR(16) DEFAULT NULL,
            is_enquiry TINYINT(1) NOT NULL DEFAULT 0,
            reference VARCHAR(255) DEFAULT NULL,
            supplierID INT UNSIGNED DEFAULT NULL,
            supplierName VARCHAR(191) DEFAULT NULL,
            component_type VARCHAR(32) DEFAULT NULL,
            process_type VARCHAR(191) DEFAULT NULL,
            template_id INT UNSIGNED DEFAULT NULL,
            runID INT UNSIGNED DEFAULT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'open',
            created_at DATETIME DEFAULT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL,
            sent_by INT UNSIGNED DEFAULT NULL,
            sent_to VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (perch3_wheeliams_purchase_orderID),
            KEY supplierID (supplierID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Add the send-tracking columns to any pre-existing table.
        foreach(array(
            'sent_at'     => 'DATETIME DEFAULT NULL',
            'sent_by'     => 'INT UNSIGNED DEFAULT NULL',
            'sent_to'     => 'VARCHAR(255) DEFAULT NULL',
            'template_id' => 'INT UNSIGNED DEFAULT NULL',
        ) as $col => $def){
            $exists = $this->db->get_row("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$this->po_table."' AND COLUMN_NAME='".$col."'");
            if(!$exists){
                $this->db->execute("ALTER TABLE ".$this->po_table." ADD COLUMN ".$col." ".$def);
            }
        }

        $this->db->execute("CREATE TABLE IF NOT EXISTS ".$this->line_table." (
            perch3_wheeliams_purchase_order_lineID INT UNSIGNED NOT NULL AUTO_INCREMENT,
            poID INT UNSIGNED NOT NULL,
            componentID INT UNSIGNED DEFAULT NULL,
            partCode VARCHAR(191) DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            uom VARCHAR(32) DEFAULT NULL,
            type VARCHAR(32) DEFAULT NULL,
            order_qty DECIMAL(14,3) NOT NULL DEFAULT 0,
            qty_received DECIMAL(14,3) NOT NULL DEFAULT 0,
            checked_in TINYINT(1) NOT NULL DEFAULT 0,
            checked_in_at DATETIME DEFAULT NULL,
            checked_in_by INT UNSIGNED DEFAULT NULL,
            supplier_note VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (perch3_wheeliams_purchase_order_lineID),
            KEY poID (poID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /* Create a PO from a set of reorder-line rows. Returns the new PO id. */
    public function createFromLines($number, $isEnquiry, $supplierID, $supplierName, $type, $process, $runID, $lines, $memberID, $templateID = 0)
    {
        $Components = new Wheeliams_Components();

        $po = $this->create(array(
            'number'         => $number,
            'is_enquiry'     => $isEnquiry ? 1 : 0,
            'reference'      => $number.' '.$supplierName.' '.date('Y-m-d'),
            'supplierID'     => (int)$supplierID,
            'supplierName'   => $supplierName,
            'component_type' => $type,
            'process_type'   => $process,
            'template_id'    => $templateID ? (int)$templateID : null,
            'runID'          => (int)$runID,
            'status'         => 'open',
            'created_at'     => date('Y-m-d H:i:s'),
            'created_by'     => (int)$memberID,
        ));
        $poID = (int)$po->perch3_wheeliams_purchase_orderID();

        foreach($lines as $line){
            $c    = $Components->component($line['componentID']);
            $dyn  = $c ? (json_decode($c['dynamicFields'], true) ?: array()) : array();
            $this->db->insert($this->line_table, array(
                'poID'          => $poID,
                'componentID'   => (int)$line['componentID'],
                'partCode'      => $line['partCode'],
                'description'   => $line['description'],
                'uom'           => $line['uom'],
                'type'          => $line['type'],
                'order_qty'     => (float)$line['actual_qty'],
                'qty_received'  => 0,
                'checked_in'    => 0,
                'supplier_note' => $dyn['supplier_notes'] ?? '',
            ));
        }

        return $poID;
    }

    public function orders($supplierID = null)
    {
        $sql = 'SELECT * FROM '.$this->po_table;
        if($supplierID){ $sql .= ' WHERE supplierID='.$this->db->pdb((int)$supplierID); }
        $sql .= ' ORDER BY created_at DESC';
        return $this->db->get_rows($sql);
    }

    public function order($id)
    {
        return $this->db->get_row('SELECT * FROM '.$this->po_table.' WHERE '.$this->pk.'='.$this->db->pdb((int)$id));
    }

    public function lines($poID)
    {
        return $this->db->get_rows('SELECT * FROM '.$this->line_table.' WHERE poID='.$this->db->pdb((int)$poID).' ORDER BY partCode ASC');
    }

    public function suppliersWithOrders()
    {
        return $this->db->get_rows('SELECT DISTINCT supplierID, supplierName FROM '.$this->po_table.' ORDER BY supplierName ASC');
    }

    public function search($q)
    {
        $like = $this->db->pdb('%'.$q.'%');
        return $this->db->get_rows('SELECT * FROM '.$this->po_table.' WHERE number LIKE '.$like.' OR supplierName LIKE '.$like.' ORDER BY created_at DESC');
    }

    /*
     * Check received quantities into stock.
     *   $received = [lineID => qty], $checked = [lineID => any] (ticked lines only)
     * Adds each ticked, not-yet-checked-in line's received qty to stock (ledger
     * reason 'checkin', attributed to $memberID) and marks the line.
     */
    public function checkIn($poID, $received, $checked, $memberID)
    {
        $Stock  = new Wheeliams_Stock();
        $po     = $this->order($poID);
        $number = $po ? $po['number'] : '';

        foreach($this->lines($poID) as $line){
            $lineID = (int)$line['perch3_wheeliams_purchase_order_lineID'];
            if(empty($checked[$lineID]))   continue; // only ticked lines
            if(!empty($line['checked_in'])) continue; // already received

            $qty = isset($received[$lineID]) && $received[$lineID] !== ''
                 ? (float)$received[$lineID]
                 : (float)$line['order_qty'];

            if($line['componentID']){
                $Stock->adjust($line['componentID'], $qty, 'checkin', $memberID, 'PO '.$number);
            }

            $this->db->update($this->line_table, array(
                'qty_received'  => $qty,
                'checked_in'    => 1,
                'checked_in_at' => date('Y-m-d H:i:s'),
                'checked_in_by' => (int)$memberID,
            ), 'perch3_wheeliams_purchase_order_lineID', $lineID);
        }

        $this->refreshStatus($poID);
    }

    public function markSent($poID, $memberID, $toEmail)
    {
        $this->db->update($this->po_table, array(
            'sent_at' => date('Y-m-d H:i:s'),
            'sent_by' => (int)$memberID,
            'sent_to' => $toEmail,
        ), $this->pk, (int)$poID);
    }

    public function refreshStatus($poID)
    {
        $lines = $this->lines($poID);
        $total = count($lines);
        $done  = 0;
        foreach($lines as $l){ if($l['checked_in']) $done++; }
        $status = ($done === 0) ? 'open' : (($done >= $total) ? 'received' : 'part-received');
        $this->db->update($this->po_table, array('status' => $status), $this->pk, (int)$poID);
    }
}
