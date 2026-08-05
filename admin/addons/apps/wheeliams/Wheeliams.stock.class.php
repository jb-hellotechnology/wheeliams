<?php

/**
 * Stock control model (Foundation A).
 *
 * Current level lives in perch3_wheeliams_stock (one row per component); every
 * change is appended to perch3_wheeliams_stock_movements as an audit trail.
 * Kept separate from the component's dynamicFields on purpose — the component
 * add/edit forms rebuild dynamicFields wholesale, which would otherwise wipe the
 * live stock level.
 */
class Wheeliams_Stock extends PerchAPI_Factory
{
    protected $table = 'wheeliams_stock';
    protected $pk    = 'perch3_wheeliams_stockID';
    protected $singular_classname = 'Wheeliams_Stock_Item';

    protected $stock_table    = 'perch3_wheeliams_stock';
    protected $movement_table = 'perch3_wheeliams_stock_movements';

    /* Create the tables on first use. Cheap no-op once they exist. */
    public function install()
    {
        $this->db->execute("CREATE TABLE IF NOT EXISTS ".$this->stock_table." (
            perch3_wheeliams_stockID INT UNSIGNED NOT NULL AUTO_INCREMENT,
            componentID   INT UNSIGNED NOT NULL,
            current_level DECIMAL(14,3) NOT NULL DEFAULT 0,
            planned_level DECIMAL(14,3) NOT NULL DEFAULT 0,
            updated_at    DATETIME DEFAULT NULL,
            updated_by    INT UNSIGNED DEFAULT NULL,
            PRIMARY KEY (perch3_wheeliams_stockID),
            UNIQUE KEY componentID (componentID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->execute("CREATE TABLE IF NOT EXISTS ".$this->movement_table." (
            perch3_wheeliams_stock_movementID INT UNSIGNED NOT NULL AUTO_INCREMENT,
            componentID INT UNSIGNED NOT NULL,
            qty         DECIMAL(14,3) NOT NULL DEFAULT 0,
            reason      VARCHAR(32) NOT NULL DEFAULT 'manual_adjust',
            note        VARCHAR(255) DEFAULT NULL,
            member_id   INT UNSIGNED DEFAULT NULL,
            created_at  DATETIME DEFAULT NULL,
            PRIMARY KEY (perch3_wheeliams_stock_movementID),
            KEY componentID (componentID),
            KEY reason (reason)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /* Full stock row for a component, or null if none yet. */
    public function record($componentID)
    {
        $sql = 'SELECT * FROM '.$this->stock_table.' WHERE componentID='.$this->db->pdb((int)$componentID);
        return $this->db->get_row($sql);
    }

    /* Current stored stock level (0 if never set). */
    public function level($componentID)
    {
        $row = $this->record($componentID);
        return $row ? (float)$row['current_level'] : 0.0;
    }

    /* Planned/incoming level (on open POs). Populated in Phase 4; 0 for now. */
    public function planned($componentID)
    {
        $row = $this->record($componentID);
        return $row ? (float)$row['planned_level'] : 0.0;
    }

    /* Apply a signed delta, log it, and return the new level. */
    public function adjust($componentID, $delta, $reason, $memberID, $note='')
    {
        $componentID = (int)$componentID;
        $delta       = (float)$delta;
        $new         = $this->level($componentID) + $delta;
        $this->store($componentID, $new, $memberID);
        if ($delta != 0) {
            $this->logMovement($componentID, $delta, $reason, $memberID, $note);
        }
        return $new;
    }

    /* Set an absolute level (manual stock-check correction); logs the difference. */
    public function setAbsolute($componentID, $level, $reason, $memberID, $note='')
    {
        $componentID = (int)$componentID;
        $level       = (float)$level;
        $delta       = $level - $this->level($componentID);
        $this->store($componentID, $level, $memberID);
        if ($delta != 0) {
            $this->logMovement($componentID, $delta, $reason, $memberID, $note);
        }
        return $level;
    }

    /* Recent movements for a component, newest first. */
    public function movements($componentID, $limit=50)
    {
        // Resolve the member who made each adjustment: staff name if we have
        // one, otherwise their login email.
        $sql = 'SELECT mv.*, st.name AS staff_name, m.memberEmail
                FROM '.$this->movement_table.' mv
                LEFT JOIN perch3_wheeliams_staff st ON st.memberID = mv.member_id
                LEFT JOIN perch3_members m ON m.memberID = mv.member_id
                WHERE mv.componentID='.$this->db->pdb((int)$componentID).'
                ORDER BY mv.created_at DESC, mv.perch3_wheeliams_stock_movementID DESC
                LIMIT '.(int)$limit;
        return $this->db->get_rows($sql);
    }

    /*
     * All components with their stock levels joined, for the report and look-ups.
     * Left join so components with no stock row yet still appear (level 0).
     */
    public function report($type=null)
    {
        $sql = 'SELECT c.perch3_wheeliams_componentID AS componentID, c.partCode, c.type, c.dynamicFields,
                       COALESCE(s.current_level,0) AS current_level,
                       COALESCE(s.planned_level,0) AS planned_level,
                       s.updated_at, s.updated_by
                FROM perch3_wheeliams_components c
                LEFT JOIN '.$this->stock_table.' s ON s.componentID = c.perch3_wheeliams_componentID';
        if ($type) {
            $sql .= ' WHERE c.type='.$this->db->pdb($type);
        }
        $sql .= ' ORDER BY c.partCode ASC';
        return $this->db->get_rows($sql);
    }

    /* Upsert the stored current level (no movement logged here). */
    private function store($componentID, $level, $memberID)
    {
        $componentID = (int)$componentID;
        $now = date('Y-m-d H:i:s');
        if ($this->record($componentID)) {
            $this->db->update($this->stock_table, array(
                'current_level' => (float)$level,
                'updated_at'    => $now,
                'updated_by'    => (int)$memberID,
            ), 'componentID', $componentID);
        } else {
            $this->db->insert($this->stock_table, array(
                'componentID'   => $componentID,
                'current_level' => (float)$level,
                'planned_level' => 0,
                'updated_at'    => $now,
                'updated_by'    => (int)$memberID,
            ));
        }
    }

    private function logMovement($componentID, $qty, $reason, $memberID, $note='')
    {
        $this->db->insert($this->movement_table, array(
            'componentID' => (int)$componentID,
            'qty'         => (float)$qty,
            'reason'      => $reason,
            'note'        => $note,
            'member_id'   => (int)$memberID,
            'created_at'  => date('Y-m-d H:i:s'),
        ));
    }
}
