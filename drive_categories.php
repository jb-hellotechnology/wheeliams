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
			'01 - BOUGHT OUT ITEMS',
			'02 - PROFILE & FORM',
			'03 - WORKSHOP COMPONENTS',
			'04 - WORKSHOP ASSEMBLIES',
			'05 - PARTS LISTS & INSTRUCTIONS',
			'06 - SCANS',
			'07 - 3D PRINT DATA',
			'08 - PRODUCT VISUALS',
		);
	}

	/* The COMPONENT file areas (original spec: typed fields + General). 'General'
	   is the part-folder root; the rest are named subfolders. */
	function wheeliams_drive_component_categories() {
		return array('DRAWINGS', 'STEP', 'DXF', 'STL', 'General');
	}

	/*
	 * Upload-target areas for a Drive type. KITs and COMPONENTs use their fixed
	 * sets above; other part types (fasteners, raw materials) keep a single
	 * General area (the part-folder root, no subfolder).
	 */
	function wheeliams_drive_categories($driveType) {
		if ($driveType === 'KIT')       return wheeliams_drive_kit_categories();
		if ($driveType === 'COMPONENT') return wheeliams_drive_component_categories();
		return array('General');
	}
}
