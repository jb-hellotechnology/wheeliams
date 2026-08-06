<?php
/**
 * READ-ONLY Google Drive file lister for a part code.
 *
 * Unlike organise_drive_files.php (which moves/creates/deletes files as a side
 * effect), this only *lists* the files already organised under /<TYPE>/<code>/,
 * recursing into subfolders and reporting each file's category via `path`
 * (the subfolder it lives in — e.g. Drawings, Instructions, Images). Safe to
 * call on every page view.
 */
require_once __DIR__ . '/vendor/autoload.php';
include('secrets.php');
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
$validTypes  = ['COMPONENT', 'FASTENER', 'RAW MATERIALS', 'KIT'];

if (!$productCode)                 json_error('Missing productCode.', 400);
if (!in_array($type, $validTypes)) json_error('Invalid type.', 400);

$client = new Google\Client();
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
$client->setAccessType('offline');
$client->addScope(Google\Service\Drive::DRIVE);
$client->fetchAccessTokenWithRefreshToken(REFRESH_TOKEN);
$drive = new Google\Service\Drive($client);

/* Find a folder by name within a parent — returns id or null (never creates). */
function findFolder(Google\Service\Drive $drive, string $name, string $parentId): ?string {
	$escaped = str_replace("'", "\\'", $name);
	$results = $drive->files->listFiles([
		'q'      => "mimeType='application/vnd.google-apps.folder'"
				  . " and name='{$escaped}' and '{$parentId}' in parents and trashed=false",
		'fields' => 'files(id)',
	]);
	$files = $results->getFiles();
	return count($files) ? $files[0]->getId() : null;
}

/* Recursively list non-folder files under a folder; $path = category (subfolder chain). */
function listFilesRecursive(Google\Service\Drive $drive, string $folderId, string $path = ''): array {
	$out = [];

	$pageToken = null;
	do {
		$params = [
			'q'      => "'{$folderId}' in parents and mimeType != 'application/vnd.google-apps.folder' and trashed=false",
			'fields' => 'nextPageToken, files(id, name, mimeType, webViewLink)',
		];
		if ($pageToken) $params['pageToken'] = $pageToken;
		$res = $drive->files->listFiles($params);
		foreach ($res->getFiles() as $f) {
			$out[] = [
				'id'          => $f->getId(),
				'name'        => $f->getName(),
				'mimeType'    => $f->getMimeType(),
				'webViewLink' => $f->getWebViewLink(),
				'path'        => $path,
			];
		}
		$pageToken = $res->getNextPageToken();
	} while ($pageToken);

	$subFolders = [];
	$pageToken  = null;
	do {
		$params = [
			'q'      => "'{$folderId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed=false",
			'fields' => 'nextPageToken, files(id, name)',
		];
		if ($pageToken) $params['pageToken'] = $pageToken;
		$res = $drive->files->listFiles($params);
		$subFolders = array_merge($subFolders, $res->getFiles());
		$pageToken = $res->getNextPageToken();
	} while ($pageToken);

	foreach ($subFolders as $sub) {
		$subPath = $path ? $path . ' / ' . $sub->getName() : $sub->getName();
		$out = array_merge($out, listFilesRecursive($drive, $sub->getId(), $subPath));
	}
	return $out;
}

try {
	$typeFolder = findFolder($drive, $type, DRIVE_ROOT_FOLDER_ID);
	if (!$typeFolder) { echo json_encode(['files' => []]); exit; }

	$productFolder = findFolder($drive, $productCode, $typeFolder);
	if (!$productFolder) { echo json_encode(['files' => []]); exit; }

	echo json_encode(['files' => listFilesRecursive($drive, $productFolder)]);
} catch (Exception $e) {
	json_error($e->getMessage());
}
