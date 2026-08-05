-- Foundation A: stock data model for the Wheeliams MRP.
-- These tables are also auto-created by Wheeliams_Stock::install() on first
-- visit to /stock/, so running this by hand is optional (kept as documentation
-- and a fallback). Safe to re-run.

-- Authoritative current stock level, one row per component.
CREATE TABLE IF NOT EXISTS perch3_wheeliams_stock (
    perch3_wheeliams_stockID INT UNSIGNED NOT NULL AUTO_INCREMENT,
    componentID   INT UNSIGNED NOT NULL,
    current_level DECIMAL(14,3) NOT NULL DEFAULT 0,
    planned_level DECIMAL(14,3) NOT NULL DEFAULT 0,   -- incoming on open POs (populated in Phase 4)
    updated_at    DATETIME DEFAULT NULL,
    updated_by    INT UNSIGNED DEFAULT NULL,           -- Perch member id
    PRIMARY KEY (perch3_wheeliams_stockID),
    UNIQUE KEY componentID (componentID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Append-only ledger of every stock change (the audit trail).
CREATE TABLE IF NOT EXISTS perch3_wheeliams_stock_movements (
    perch3_wheeliams_stock_movementID INT UNSIGNED NOT NULL AUTO_INCREMENT,
    componentID INT UNSIGNED NOT NULL,
    qty         DECIMAL(14,3) NOT NULL DEFAULT 0,       -- signed: +ve add, -ve remove
    reason      VARCHAR(32) NOT NULL DEFAULT 'manual_adjust', -- manual_set|manual_adjust|checkin|completion|analysis
    note        VARCHAR(255) DEFAULT NULL,
    member_id   INT UNSIGNED DEFAULT NULL,
    created_at  DATETIME DEFAULT NULL,
    PRIMARY KEY (perch3_wheeliams_stock_movementID),
    KEY componentID (componentID),
    KEY reason (reason)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
