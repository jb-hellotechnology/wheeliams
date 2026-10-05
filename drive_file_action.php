<?php
/**
 * Archive / restore a single Drive file for a part.
 *
 * "Delete" in the file area never hard-deletes: it moves the file into the
 * part's ARCHIVE subfolder (hidden from the active list, recoverable). Restore
 * moves it back into the part folder root (General). POST only, logged-in
 * members with ordering access (L2+) — the same level that edits parts.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST')   json_error('POST required.', 405);
if (!perch_member_logged_in())               json_error('Not authorised.', 403);
if (function_exists('wheeliams_can_order') && !wheeliams_can_order()) json_error('Not authorised.', 403);

$action      = $_POST['action']   ?? '';
$fileId      = $_POST['id']        ?? '';
$productCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['partCode'] ?? '');
$type        = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $_POST['type']    ?? '');
$validTypes  = ['COMPONENT', 'FASTENER', 'RAW MATERIALS', 'KIT'];

if (!in_array($action, ['archive', 'restore'], true)) json_error('Unknown action.', 400);
if ($fileId === '')                 json_error('Missing file id.', 400);
if (!$productCode)                  json_error('Missing partCode.', 400);
if (!in_array($type, $validTypes))  json_error('Invalid type.', 400);

try {
	$drive      = wheeliams_drive_service();
	$partFolder = wheeliams_drive_part_folder($drive, $type, $productCode, true);

	if ($action === 'archive') {
		$dest = wheeliams_drive_archive_folder($drive, $partFolder);
	} else { // restore → back to the part folder root (General)
		$dest = $partFolder;
	}

	wheeliams_drive_move($drive, $fileId, $dest);
	echo json_encode(['ok' => true, 'action' => $action]);
} catch (Exception $e) {
	json_error($e->getMessage());
}
