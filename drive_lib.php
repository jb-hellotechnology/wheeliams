<?php
/**
 * Shared Google Drive helpers for the Wheeliams file area.
 *
 * The part documents live at  ROOT / <TYPE> / <partCode> / [<CATEGORY> /] file
 * where TYPE ∈ COMPONENT|FASTENER|RAW MATERIALS|KIT and CATEGORY is one of the
 * canonical areas below (loose files sitting straight in the part folder are
 * treated as "General"). Deleting a file ARCHIVES it — it moves into a special
 * ARCHIVE subfolder of the part folder, hidden from the active list but
 * recoverable. This file centralises the auth + folder plumbing that the
 * upload / list / action endpoints all share.
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/drive_categories.php'; // WHEELIAMS_DRIVE_ARCHIVE + wheeliams_drive_categories()

/* Authenticated Drive service (throws on auth failure). */
function wheeliams_drive_service(): Google\Service\Drive {
	$client = new Google\Client();
	$client->setClientId(CLIENT_ID);
	$client->setClientSecret(CLIENT_SECRET);
	$client->setAccessType('offline');
	$client->addScope(Google\Service\Drive::DRIVE);
	$client->fetchAccessTokenWithRefreshToken(REFRESH_TOKEN);
	return new Google\Service\Drive($client);
}

/* Find a folder by name within a parent — returns id or null (never creates). */
function wheeliams_drive_find_folder(Google\Service\Drive $drive, string $name, string $parentId): ?string {
	$escaped = str_replace("'", "\\'", $name);
	$results = $drive->files->listFiles([
		'q'      => "mimeType='application/vnd.google-apps.folder' and name='{$escaped}'"
				  . " and '{$parentId}' in parents and trashed=false",
		'fields' => 'files(id)',
		'orderBy' => 'createdTime',
		'supportsAllDrives' => true,
		'includeItemsFromAllDrives' => true,
	]);
	$files = $results->getFiles();
	return count($files) ? $files[0]->getId() : null;
}

/* Find a folder by name within a parent, creating it if absent. */
function wheeliams_drive_find_or_create_folder(Google\Service\Drive $drive, string $name, string $parentId): string {
	$existing = wheeliams_drive_find_folder($drive, $name, $parentId);
	if ($existing) return $existing;

	$folder = new Google\Service\Drive\DriveFile([
		'name'     => $name,
		'mimeType' => 'application/vnd.google-apps.folder',
		'parents'  => [$parentId],
	]);
	$created = $drive->files->create($folder, ['fields' => 'id', 'supportsAllDrives' => true]);
	return $created->getId();
}

/*
 * The /<TYPE>/<partCode>/ folder id. With $create the TYPE and part folders are
 * made if missing; without it, returns null when either is absent.
 */
function wheeliams_drive_part_folder(Google\Service\Drive $drive, string $type, string $partCode, bool $create = false): ?string {
	if ($create) {
		$typeFolder = wheeliams_drive_find_or_create_folder($drive, $type, DRIVE_ROOT_FOLDER_ID);
		return wheeliams_drive_find_or_create_folder($drive, $partCode, $typeFolder);
	}
	$typeFolder = wheeliams_drive_find_folder($drive, $type, DRIVE_ROOT_FOLDER_ID);
	if (!$typeFolder) return null;
	return wheeliams_drive_find_folder($drive, $partCode, $typeFolder);
}

/* Resolve a category to the folder files should live in. "General" (or blank)
   means the part folder root; any other category is a subfolder of it. */
function wheeliams_drive_category_folder(Google\Service\Drive $drive, string $partFolderId, string $category, bool $create = true): string {
	$category = trim($category);
	if ($category === '' || strcasecmp($category, 'General') === 0) {
		return $partFolderId; // loose in the part folder = General
	}
	return $create
		? wheeliams_drive_find_or_create_folder($drive, $category, $partFolderId)
		: (string) wheeliams_drive_find_folder($drive, $category, $partFolderId);
}

/* The part's ARCHIVE subfolder id (created on demand). */
function wheeliams_drive_archive_folder(Google\Service\Drive $drive, string $partFolderId): string {
	return wheeliams_drive_find_or_create_folder($drive, WHEELIAMS_DRIVE_ARCHIVE, $partFolderId);
}

/* The current parent folder ids of a file, as a comma-joined string. */
function wheeliams_drive_parents(Google\Service\Drive $drive, string $fileId): string {
	$meta = $drive->files->get($fileId, ['fields' => 'parents', 'supportsAllDrives' => true]);
	return implode(',', $meta->getParents() ?: []);
}

/* Move a file from its current parent(s) into $destFolderId. */
function wheeliams_drive_move(Google\Service\Drive $drive, string $fileId, string $destFolderId): void {
	$current = wheeliams_drive_parents($drive, $fileId);
	$drive->files->update(
		$fileId,
		new Google\Service\Drive\DriveFile(),
		[
			'addParents'        => $destFolderId,
			'removeParents'     => $current,
			'fields'            => 'id, parents',
			'supportsAllDrives' => true,
		]
	);
}

/*
 * List non-folder files under a folder, recursing into subfolders. $path tracks
 * the category (subfolder chain). The ARCHIVE subfolder is skipped — archived
 * files are listed separately via wheeliams_drive_list_archived().
 */
function wheeliams_drive_list_files(Google\Service\Drive $drive, string $folderId, string $path = ''): array {
	$out = [];

	$pageToken = null;
	do {
		$params = [
			'q'      => "'{$folderId}' in parents and mimeType != 'application/vnd.google-apps.folder' and trashed=false",
			'fields' => 'nextPageToken, files(id, name, mimeType, webViewLink)',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
		];
		if ($pageToken) $params['pageToken'] = $pageToken;
		$res = $drive->files->listFiles($params);
		foreach ($res->getFiles() as $f) {
			$out[] = [
				'id'          => $f->getId(),
				'name'        => $f->getName(),
				'mimeType'    => $f->getMimeType(),
				'webViewLink' => $f->getWebViewLink(),
				'category'    => $path === '' ? 'General' : $path,
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
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
		];
		if ($pageToken) $params['pageToken'] = $pageToken;
		$res = $drive->files->listFiles($params);
		$subFolders = array_merge($subFolders, $res->getFiles());
		$pageToken = $res->getNextPageToken();
	} while ($pageToken);

	foreach ($subFolders as $sub) {
		if (strcasecmp($sub->getName(), WHEELIAMS_DRIVE_ARCHIVE) === 0) continue; // never in the active list
		$subPath = $path === '' ? $sub->getName() : $path . ' / ' . $sub->getName();
		$out = array_merge($out, wheeliams_drive_list_files($drive, $sub->getId(), $subPath));
	}
	return $out;
}

/* The archived files for a part (flat list), or [] when none. */
function wheeliams_drive_list_archived(Google\Service\Drive $drive, string $partFolderId): array {
	$archiveId = wheeliams_drive_find_folder($drive, WHEELIAMS_DRIVE_ARCHIVE, $partFolderId);
	if (!$archiveId) return [];
	$out = [];
	$pageToken = null;
	do {
		$params = [
			'q'      => "'{$archiveId}' in parents and mimeType != 'application/vnd.google-apps.folder' and trashed=false",
			'fields' => 'nextPageToken, files(id, name, mimeType, webViewLink)',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true,
		];
		if ($pageToken) $params['pageToken'] = $pageToken;
		$res = $drive->files->listFiles($params);
		foreach ($res->getFiles() as $f) {
			$out[] = [
				'id'          => $f->getId(),
				'name'        => $f->getName(),
				'mimeType'    => $f->getMimeType(),
				'webViewLink' => $f->getWebViewLink(),
				'category'    => WHEELIAMS_DRIVE_ARCHIVE,
			];
		}
		$pageToken = $res->getNextPageToken();
	} while ($pageToken);
	return $out;
}

/* A same-named, non-folder, non-trashed file already in $folderId, or null. */
function wheeliams_drive_file_by_name(Google\Service\Drive $drive, string $name, string $folderId): ?string {
	$escaped = str_replace("'", "\\'", $name);
	$res = $drive->files->listFiles([
		'q'      => "name='{$escaped}' and '{$folderId}' in parents"
				  . " and mimeType != 'application/vnd.google-apps.folder' and trashed=false",
		'fields' => 'files(id)',
		'supportsAllDrives' => true,
		'includeItemsFromAllDrives' => true,
	]);
	$files = $res->getFiles();
	return count($files) ? $files[0]->getId() : null;
}
