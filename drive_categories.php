<?php
/**
 * Canonical Google Drive file areas for a part, in display order.
 *
 * Single source of truth shared by the standalone Drive endpoints (via
 * drive_lib.php) and the admin file-manager pages, so the folder names match
 * character-for-character everywhere. Deliberately dependency-free. The names
 * MUST match the folders that exist inside each part folder in Drive, or a
 * second folder of the slightly-different name would be created on upload.
 */

if (!defined('WHEELIAMS_DRIVE_ARCHIVE')) {
	define('WHEELIAMS_DRIVE_ARCHIVE', '00 - ARCHIVE');
}

if (!function_exists('wheeliams_drive_categories')) {

	/* The KIT areas, in display order (archive is handled separately). */
	function wheeliams_drive_kit_categories() {
		return array(
			'01 - PROFILE & FORM',
			'02 - BOUGHT OUT ITEMS',
			'03 - WELDED ASSEMBLIES',
			'04 - MECHANICAL ASSEMBLIES',
			'05 - PARTS LISTS & INSTRUCTIONS',
			'06 - VISUALS',
		);
	}

	/*
	 * Upload-target areas for a Drive type. KITs use the fixed numbered set
	 * above; other part types keep a single General area (the part-folder
	 * root, no subfolder).
	 */
	function wheeliams_drive_categories($driveType) {
		return ($driveType === 'KIT') ? wheeliams_drive_kit_categories() : array('General');
	}
}
