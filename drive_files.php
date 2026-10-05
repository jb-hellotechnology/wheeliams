<?php
/**
 * READ-ONLY Google Drive file lister for a part code.
 *
 * Lists the files already organised under /<TYPE>/<code>/, recursing into
 * category subfolders (Drawings, Assembly, Images, Instructions, …) and
 * reporting each file's `category` (loose files in the part folder = General).
 * The ARCHIVE subfolder is excluded from the active list; pass scope=archived
 * to list only archived files. Safe to call on every page view.
 */
include('secrets.php');
require_once __DIR__ . '/drive_lib.php';
header('Content-Type: application/json');

if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php');

function json_error(string $message, int $status = 500): void {
	http_response_code($status);
	echo json_encode(['error' => $message]);
	exit;
}

// Any logged-in member may view documents (Level 1+).
if (!perch_member_logged_in()) {
	json_error('Not authorised.', 403);
}

$productCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['productCode'] ?? '');
$type        = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $_GET['type']        ?? '');
$scope       = ($_GET['scope'] ?? 'active') === 'archived' ? 'archived' : 'active';
$validTypes  = ['COMPONENT', 'FASTENER', 'RAW MATERIALS', 'KIT'];

if (!$productCode)                 json_error('Missing productCode.', 400);
if (!in_array($type, $validTypes)) json_error('Invalid type.', 400);

try {
	$drive      = wheeliams_drive_service();
	$partFolder = wheeliams_drive_part_folder($drive, $type, $productCode, false);
	if (!$partFolder) { echo json_encode(['files' => []]); exit; }

	$files = $scope === 'archived'
		? wheeliams_drive_list_archived($drive, $partFolder)
		: wheeliams_drive_list_files($drive, $partFolder);

	// `path` kept as a back-compat alias (root = '' as before); `category`
	// is the canonical field used by the file manager.
	foreach ($files as &$f) {
		$f['path'] = ($f['category'] === 'General') ? '' : $f['category'];
	}
	unset($f);

	echo json_encode(['files' => $files]);
} catch (Exception $e) {
	json_error($e->getMessage());
}
