<?php
/**
 * Upload a file into a part's Drive area, optionally into a named category
 * (Drawings / Assembly / Images / Instructions / General). Native SolidWorks
 * files are rejected. If a file of the same name already exists in the target
 * folder the caller is asked to confirm (collision) unless overwrite=1, in
 * which case the existing file is archived first so nothing is lost.
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

if (!perch_member_logged_in()) json_error('Not authorised.', 403);

// Validate inputs
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
	json_error('No file received or upload error.', 400);
}

$type      = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $_POST['type']     ?? '');
$partCode  = preg_replace('/[^a-zA-Z0-9_\-]/',  '', $_POST['partCode'] ?? '');
$category  = $_POST['category'] ?? 'General';
$overwrite = !empty($_POST['overwrite']);

$validTypes = ['COMPONENT', 'FASTENER', 'RAW MATERIALS', 'KIT'];
if (!$type || !$partCode)           json_error('Missing type or partCode.', 400);
if (!in_array($type, $validTypes))  json_error('Invalid type.', 400);
// Keep the category within the areas valid for this part type (else the first).
$allowedCats = wheeliams_drive_categories($type);
if (!in_array($category, $allowedCats, true)) $category = $allowedCats[0];

// Native SolidWorks files are held separately, not published on the dashboard.
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (in_array($ext, array('sldprt', 'sldasm', 'slddrw', 'slddrt', 'sldlfp'), true)) {
	json_error('Native SolidWorks files (.'.$ext.') can’t be attached here — please upload a PDF, DXF, STEP, image or document instead.', 415);
}

try {
	$drive      = wheeliams_drive_service();
	$partFolder = wheeliams_drive_part_folder($drive, $type, $partCode, true);
	$targetId   = wheeliams_drive_category_folder($drive, $partFolder, $category, true);

	$fileName = basename($_FILES['file']['name']);

	// Same-name file already here? Ask for confirmation unless told to overwrite.
	$existingId = wheeliams_drive_file_by_name($drive, $fileName, $targetId);
	if ($existingId) {
		if (!$overwrite) {
			http_response_code(409);
			echo json_encode([
				'collision' => true,
				'fileName'  => $fileName,
				'category'  => $category,
			]);
			exit;
		}
		// Overwriting: archive the old version so it stays recoverable.
		$archive = wheeliams_drive_archive_folder($drive, $partFolder);
		wheeliams_drive_move($drive, $existingId, $archive);
	}

	$filePath = $_FILES['file']['tmp_name'];
	$mimeType = $_FILES['file']['type'] ?: 'application/octet-stream';

	$driveFile = new Google\Service\Drive\DriveFile([
		'name'    => $fileName,
		'parents' => [$targetId],
	]);
	$uploaded = $drive->files->create($driveFile, [
		'data'       => file_get_contents($filePath),
		'mimeType'   => $mimeType,
		'uploadType' => 'multipart',
		'fields'     => 'id, name, webViewLink',
		'supportsAllDrives' => true,
	]);

	echo json_encode([
		'fileId'      => $uploaded->getId(),
		'fileName'    => $uploaded->getName(),
		'webViewLink' => $uploaded->getWebViewLink(),
		'category'    => $category,
	]);
} catch (Exception $e) {
	json_error($e->getMessage());
}
