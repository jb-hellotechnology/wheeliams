<?php
require_once __DIR__ . '/vendor/autoload.php';
include('secrets.php');
header('Content-Type: application/json');

function json_error(string $message, int $status = 500): void {
	http_response_code($status);
	echo json_encode(['error' => $message]);
	exit;
}

$productCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['productCode'] ?? '');
$type        = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $_GET['type']        ?? '');

$validTypes  = ['COMPONENT', 'FASTENER', 'RAW MATERIALS', 'KIT'];

if (!$productCode) {
	json_error('Missing productCode.', 400);
}

if (!in_array($type, $validTypes)) {
	json_error('Invalid type. Must be one of: ' . implode(', ', $validTypes), 400);
}

// Authenticate
$client = new Google\Client();
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
$client->setAccessType('offline');
$client->addScope(Google\Service\Drive::DRIVE);
$client->fetchAccessTokenWithRefreshToken(REFRESH_TOKEN);

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
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
	]);

	if (count($results->getFiles()) > 0) {
		return $results->getFiles()[0]->getId();
	}

	$folder = new Google\Service\Drive\DriveFile([
		'name'     => $name,
		'mimeType' => 'application/vnd.google-apps.folder',
		'parents'  => [$parentId],
	]);

	$created = $drive->files->create($folder, ['fields' => 'id', 'supportsAllDrives' => true]);
	return $created->getId();
}

/**
 * Find all files in a folder (and subfolders) whose name contains a string,
 * move them into a dedicated subfolder, and return the folder's contents.
 */
function organiseFilesByProductCode(
	 Google\Service\Drive $drive,
	 string $productCode,
	 string $searchFolderId,  // where to look for files
	 string $destFolderId     // where to create the productCode folder
 ): array {
 
	 $escaped = str_replace("'", "\\'", $productCode);
 
	 // 1. Search for files in the ROOT folder whose name contains the productCode
	 $allFiles  = [];
	 $pageToken = null;
 
	 do {
		 $params = [
			 'q'      => "name contains '{$escaped}'"
					   . " and mimeType != 'application/vnd.google-apps.folder'"
					   . " and '{$searchFolderId}' in parents"
					   . " and trashed=false",
			 'fields' => 'nextPageToken, files(id, name, mimeType, webViewLink)',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
		 ];
 
		 if ($pageToken) $params['pageToken'] = $pageToken;
 
		 $results   = $drive->files->listFiles($params);
		 $allFiles  = array_merge($allFiles, $results->getFiles());
		 $pageToken = $results->getNextPageToken();
 
	 } while ($pageToken);
 
	 // 2. Find or create the productCode subfolder inside the TYPE folder
	 $productFolder = findOrCreateFolder($drive, $productCode, $destFolderId);
 
	 // 3. Move each matching file into the productCode folder
	 foreach ($allFiles as $file) {
		 $fileDetails    = $drive->files->get($file->getId(), ['fields' => 'parents', 'supportsAllDrives' => true]);
		 $currentParents = implode(',', $fileDetails->getParents());
 
		 $drive->files->update(
			 $file->getId(),
			 new Google\Service\Drive\DriveFile(),
			 [
				 'addParents'    => $productFolder,
				 'removeParents' => $currentParents,
				 'fields'        => 'id, parents',
					'supportsAllDrives' => true,
			 ]
		 );
	 }
 
	 // 4. List all files now in the productCode folder
	 $folderContents = [];
	 $pageToken      = null;
 
	 do {
		 $params = [
			 'q'      => "'{$productFolder}' in parents"
					   . " and mimeType != 'application/vnd.google-apps.folder'"
					   . " and trashed=false",
			 'fields' => 'nextPageToken, files(id, name, mimeType, webViewLink)',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
		 ];
 
		 if ($pageToken) $params['pageToken'] = $pageToken;
 
		 $results        = $drive->files->listFiles($params);
		 $folderContents = array_merge($folderContents, $results->getFiles());
		 $pageToken      = $results->getNextPageToken();
 
	 } while ($pageToken);
 
	 return array_map(fn($f) => [
		 'id'          => $f->getId(),
		 'name'        => $f->getName(),
		 'mimeType'    => $f->getMimeType(),
		 'webViewLink' => $f->getWebViewLink(),
		 'path'        => '',
	 ], $folderContents);
 }
 
 /**
  * Find a folder in the root whose name contains $productCode,
  * move it into /KIT/[productCode], and return its contents (recursive).
  */
 function organiseKitFolderByProductCode(
	 Google\Service\Drive $drive,
	 string $productCode,
	 string $searchFolderId,
	 string $kitFolderId
 ): array {
	 $escaped = str_replace("'", "\\'", $productCode);
 
	 // 2. Find or create /KIT/[productCode] as the destination
	 $destFolder = findOrCreateFolder($drive, $productCode, $kitFolderId);
 
	 // 1. Find folders in the root whose name contains the productCode
	 $matchingFolders = [];
	 $pageToken = null;
 
	 do {
		 $params = [
			 'q'      => "name contains '{$escaped}'"
					   . " and mimeType = 'application/vnd.google-apps.folder'"
					   . " and '{$searchFolderId}' in parents"
					   . " and trashed=false",
			 'fields' => 'nextPageToken, files(id, name)',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
		 ];
 
		 if ($pageToken) $params['pageToken'] = $pageToken;
 
		 $results         = $drive->files->listFiles($params);
		 $matchingFolders = array_merge($matchingFolders, $results->getFiles());
		 $pageToken       = $results->getNextPageToken();
	 } while ($pageToken);
 
	 // 3. Move contents of each matching folder into the destination
	 foreach ($matchingFolders as $folder) {
		 $pageToken = null;
	 
		 do {
			 $params = [
				 'q'      => "'{$folder->getId()}' in parents and trashed=false",
				 'fields' => 'nextPageToken, files(id)',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
			 ];
	 
			 if ($pageToken) $params['pageToken'] = $pageToken;
	 
			 $results = $drive->files->listFiles($params);
	 
			 foreach ($results->getFiles() as $child) {
				 $drive->files->update(
					 $child->getId(),
					 new Google\Service\Drive\DriveFile(),
					 [
						 'addParents'    => $destFolder,
						 'removeParents' => $folder->getId(),
						 'fields'        => 'id, parents',
					'supportsAllDrives' => true,
					 ]
				 );
			 }
	 
			 $pageToken = $results->getNextPageToken();
		 } while ($pageToken);
	 }
	 
	 // Optionally delete the now-empty source folders
	 foreach ($matchingFolders as $folder) {
		 $drive->files->delete($folder->getId(), ['supportsAllDrives' => true]);
	 }
 
	 // 4. List what's already in the destination folder (covers reloads)
	 $alreadyMoved = [];
	 $pageToken    = null;
 
	 do {
		 $params = [
			 'q'      => "mimeType = 'application/vnd.google-apps.folder'"
					   . " and '{$destFolder}' in parents"
					   . " and trashed=false",
			 'fields' => 'nextPageToken, files(id, name)',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
		 ];
 
		 if ($pageToken) $params['pageToken'] = $pageToken;
 
		 $results      = $drive->files->listFiles($params);
		 $alreadyMoved = array_merge($alreadyMoved, $results->getFiles());
		 $pageToken    = $results->getNextPageToken();
	 } while ($pageToken);
 
	 // 5. Recurse from the destination folder — subfolders become the path
	 return listFilesRecursive($drive, $destFolder);
 
	 if (empty($allFolders)) {
		 return [];
	 }
 
	 $allFiles = [];
	 foreach ($allFolders as $folder) {
		 $allFiles = array_merge($allFiles, listFilesRecursive($drive, $folder->getId()));
	 }
	 return $allFiles;
 }
 
 /**
  * Recursively list all non-folder files under a given folder.
  */
  function listFilesRecursive(Google\Service\Drive $drive, string $folderId, string $path = ''): array {
	  $allFiles  = [];
	  $pageToken = null;
  
	  do {
		  $params = [
			  'q'      => "'{$folderId}' in parents"
						. " and mimeType != 'application/vnd.google-apps.folder'"
						. " and trashed=false",
			  'fields' => 'nextPageToken, files(id, name, mimeType, webViewLink)',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
		  ];
  
		  if ($pageToken) $params['pageToken'] = $pageToken;
  
		  $results   = $drive->files->listFiles($params);
  
		  foreach ($results->getFiles() as $file) {
			  $allFiles[] = [
				  'id'          => $file->getId(),
				  'name'        => $file->getName(),
				  'mimeType'    => $file->getMimeType(),
				  'webViewLink' => $file->getWebViewLink(),
				  'path'        => $path,
			  ];
		  }
  
		  $pageToken = $results->getNextPageToken();
	  } while ($pageToken);
  
	  // Recurse into subfolders
	  $subFolders = [];
	  $pageToken  = null;
  
	  do {
		  $params = [
			  'q'      => "'{$folderId}' in parents"
						. " and mimeType = 'application/vnd.google-apps.folder'"
						. " and trashed=false",
			  'fields' => 'nextPageToken, files(id, name)',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
		  ];
  
		  if ($pageToken) $params['pageToken'] = $pageToken;
  
		  $results    = $drive->files->listFiles($params);
		  $subFolders = array_merge($subFolders, $results->getFiles());
		  $pageToken  = $results->getNextPageToken();
	  } while ($pageToken);
  
	  foreach ($subFolders as $sub) {
		  $subPath  = $path ? $path . ' / ' . $sub->getName() : $sub->getName();
		  $allFiles = array_merge($allFiles, listFilesRecursive($drive, $sub->getId(), $subPath));
	  }
  
	  return $allFiles;
  }

try {
	  $typeFolder = findOrCreateFolder($drive, $type, DRIVE_ROOT_FOLDER_ID);
  
	  if ($type === 'KIT') {
		  $files = organiseKitFolderByProductCode($drive, $productCode, DRIVE_ROOT_FOLDER_ID, $typeFolder);
	  } else {
		  $files = organiseFilesByProductCode($drive, $productCode, DRIVE_ROOT_FOLDER_ID, $typeFolder);
	  }
  
	  echo json_encode(['files' => $files]);
  } catch (Exception $e) {
	  json_error($e->getMessage());
  }