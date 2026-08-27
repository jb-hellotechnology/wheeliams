<?php
require_once __DIR__ . '/vendor/autoload.php';
include('secrets.php');
header('Content-Type: application/json');

// ─────────────────────────────────────────────────────────────────────────────

function json_error(string $message, int $status = 500): void {
	http_response_code($status);
	echo json_encode(['error' => $message]);
	exit;
}

// Validate inputs
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
	json_error('No file received or upload error.', 400);
}

$type     = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['type']     ?? '');
$partCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['partCode'] ?? '');

if (!$type || !$partCode) {
	json_error('Missing type or partCode.', 400);
}

// Authenticate via OAuth2
$client = new Google\Client();
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
$client->setAccessType('offline');
$client->addScope(Google\Service\Drive::DRIVE);
$client->fetchAccessTokenWithRefreshToken(REFRESH_TOKEN);

if ($client->isAccessTokenExpired() && !$client->getRefreshToken()) {
	json_error('OAuth token error — re-run the auth script to get a new refresh token.');
}

$drive = new Google\Service\Drive($client);

/**
 * Find a folder by name within a parent, or create it if it doesn't exist.
 */
function findOrCreateFolder(Google\Service\Drive $drive, string $name, string $parentId): string {
	$escaped = str_replace("'", "\\'", $name);

	$results = $drive->files->listFiles([
		'q'      => "mimeType='application/vnd.google-apps.folder'"
				  . " and name='{$escaped}'"
				  . " and '{$parentId}' in parents"
				  . " and trashed=false",
		'fields' => 'files(id)',
		'orderBy' => 'createdTime',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
	]);

	if (count($results->getFiles()) > 0) {
		return $results->getFiles()[0]->getId();
	}

	// Create the folder
	$folder = new Google\Service\Drive\DriveFile([
		'name'     => $name,
		'mimeType' => 'application/vnd.google-apps.folder',
		'parents'  => [$parentId],
	]);

	$created = $drive->files->create($folder, ['fields' => 'id', 'supportsAllDrives' => true]);
	return $created->getId();
}

try {
	// Build folder path: ROOT > type > partCode
	$typeFolderId     = findOrCreateFolder($drive, $type,     DRIVE_ROOT_FOLDER_ID);
	$partCodeFolderId = findOrCreateFolder($drive, $partCode, $typeFolderId);

	// Upload the file
	$filePath = $_FILES['file']['tmp_name'];
	$fileName = basename($_FILES['file']['name']);
	$mimeType = $_FILES['file']['type'] ?: 'application/octet-stream';

	$driveFile = new Google\Service\Drive\DriveFile([
		'name'    => $fileName,
		'parents' => [$partCodeFolderId],
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
	]);

} catch (Exception $e) {
	json_error($e->getMessage());
}