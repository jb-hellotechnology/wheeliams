<?php
/**
 * Streams a Google Drive file straight to the browser as a download, so staff
 * stay on the dashboard and never see Google Drive. Login-gated (Level 1+),
 * like drive_files.php. The bytes are proxied through the app using the same
 * Drive credentials — the file id comes from the file lists (drive_files.php).
 *
 * Note: large SolidWorks assemblies (100s of MB) are proxied Drive→server→browser,
 * so they use server bandwidth; the common case (PDF/DXF/STEP drawings) is small.
 */
require_once __DIR__ . '/vendor/autoload.php';
include('secrets.php');
if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php');

if (!perch_member_logged_in()) { http_response_code(403); exit('Not authorised.'); }

$id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['id'] ?? '');
if ($id === '') { http_response_code(400); exit('Missing file id.'); }

// Authenticate and get a bearer token for the raw media endpoint.
$client = new Google\Client();
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
$client->setAccessType('offline');
$client->addScope(Google\Service\Drive::DRIVE);
$client->fetchAccessTokenWithRefreshToken(REFRESH_TOKEN);
$accessToken = $client->getAccessToken()['access_token'] ?? '';
if ($accessToken === '') { http_response_code(500); exit('Drive authentication failed.'); }

// Metadata first (name / type / size for the response headers). supportsAllDrives
// is required for Shared Drive files.
$metaUrl = 'https://www.googleapis.com/drive/v3/files/'.rawurlencode($id)
         . '?fields=name,mimeType,size&supportsAllDrives=true';
$ch = curl_init($metaUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken));
$metaBody = curl_exec($ch);
$metaCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($metaCode !== 200) { http_response_code(404); exit('File not found.'); }

$meta = json_decode($metaBody, true);
$name = $meta['name'] ?? 'download';
$mime = $meta['mimeType'] ?? 'application/octet-stream';
$size = isset($meta['size']) ? (int)$meta['size'] : 0;

// Google-native docs have no downloadable binary via alt=media — bail cleanly.
if (strpos($mime, 'application/vnd.google-apps') === 0) {
    http_response_code(415);
    exit('This item is a Google document and cannot be downloaded directly.');
}

// Drop any output buffering so we stream raw bytes.
while (ob_get_level()) { ob_end_clean(); }

$asciiName = str_replace('"', '', preg_replace('/[^\x20-\x7E]/', '_', $name));
header('Content-Type: '.$mime);
header('Content-Disposition: attachment; filename="'.$asciiName.'"; filename*=UTF-8\'\''.rawurlencode($name));
if ($size > 0) { header('Content-Length: '.$size); }
header('X-Content-Type-Options: nosniff');

// Stream the media straight to the client in chunks (memory-safe for big files).
$mediaUrl = 'https://www.googleapis.com/drive/v3/files/'.rawurlencode($id).'?alt=media&supportsAllDrives=true';
$ch = curl_init($mediaUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken));
curl_setopt($ch, CURLOPT_BUFFERSIZE, 65536);
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) {
    echo $chunk;
    flush();
    return strlen($chunk);
});
curl_exec($ch);
curl_close($ch);
