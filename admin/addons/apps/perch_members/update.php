<?php

	// Prevent running directly:
    if (!defined('PERCH_DB_PREFIX')) exit;

	$sql = "

		ALTER TABLE `__PREFIX__members` CHANGE `memberPassword` `memberPassword` CHAR(255)  NULL  DEFAULT NULL;

	";

	$sql = str_replace('__PREFIX__', PERCH_DB_PREFIX, $sql);

	$DB = PerchDB::fetch();

    $statements = explode(';', $sql);
    foreach($statements as $statement) {
        $statement = trim($statement);
        if ($statement!='') $DB->execute($statement);
    }

    $Settings->set('perch_members_update', '1.4');