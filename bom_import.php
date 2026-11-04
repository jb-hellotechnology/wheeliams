<?php
/**
 * Quick-import a BOM by pasting rows from a spreadsheet.
 *
 * The user pastes tab-separated rows (as copied from Excel / Google Sheets) into
 * the BOM page's "Quick Import" panel. We parse them (handling quoted cells that
 * contain newlines/tabs), map the columns to Part Number / Description / Quantity,
 * and match each Part Number to an existing component by its part code.
 *
 *   action=preview  → parse + match, return per-line status for the UI to show
 *   action=import   → re-validate ALL lines; if every line matches, upsert the
 *                     BOM rows (all-or-nothing). If any line fails it imports
 *                     nothing and returns the failing lines.
 *
 * POST only, admin members (same level that edits BOMs).
 */
include('secrets.php');
header('Content-Type: application/json');

if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php');

function json_error(string $message, int $status = 500): void {
	http_response_code($status);
	echo json_encode(['error' => $message]);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('POST required.', 405);
if (!perch_member_logged_in())             json_error('Not authorised.', 403);
if (!function_exists('wheeliams_access_level') || wheeliams_access_level() !== 'admin') json_error('Not authorised.', 403);

$action = $_POST['action'] ?? 'preview';
$kitID  = (int)($_POST['kitID'] ?? 0);
$type   = in_array($_POST['type'] ?? '', ['products', 'manufactured'], true) ? $_POST['type'] : '';
$raw    = (string)($_POST['data'] ?? '');

if (!$kitID)  json_error('Missing BOM id.', 400);
if (!$type)   json_error('Invalid BOM type.', 400);

/* Parse pasted spreadsheet text into rows of cells. fgetcsv with a tab
   delimiter handles quoted cells that contain commas, tabs or newlines. */
function wheeliams_parse_pasted(string $text): array {
	$text = str_replace("\r\n", "\n", $text);
	$text = str_replace("\r", "\n", $text);
	$fh = fopen('php://temp', 'r+');
	fwrite($fh, $text);
	rewind($fh);
	$rows = [];
	while (($cells = fgetcsv($fh, 0, "\t", '"', '\\')) !== false) {
		// Skip rows that are entirely empty.
		$nonEmpty = array_filter($cells, fn($c) => trim((string)$c) !== '');
		if (!count($nonEmpty)) continue;
		$rows[] = array_map(fn($c) => trim((string)$c), $cells);
	}
	fclose($fh);
	return $rows;
}

/* Does a row look like a header (labels, not data)? */
function wheeliams_is_header(array $row): bool {
	foreach ($row as $c) {
		if (preg_match('/^(part\s*(no|number|code)?|qty|quantity|desc(ription)?)\b/i', trim($c))) return true;
	}
	return false;
}

/* Auto-detect the column mapping from a header row. */
function wheeliams_detect_mapping(array $header): array {
	$map = ['part' => null, 'desc' => null, 'qty' => null];
	foreach ($header as $i => $label) {
		$l = strtolower(trim($label));
		if ($map['part'] === null && strpos($l, 'part') !== false) $map['part'] = $i;
		elseif ($map['qty']  === null && (strpos($l, 'qty') !== false || strpos($l, 'quantity') !== false)) $map['qty'] = $i;
		elseif ($map['desc'] === null && strpos($l, 'desc') !== false) $map['desc'] = $i;
	}
	return $map;
}

$rows = wheeliams_parse_pasted($raw);
if (!count($rows)) json_error('Nothing to import — paste some rows first.', 400);

// Work out the mapping. The UI may send an explicit mapping; otherwise detect it.
$hasHeaderFlag = array_key_exists('has_header', $_POST) ? (!empty($_POST['has_header'])) : null;
$explicit = array_key_exists('part_col', $_POST) || array_key_exists('qty_col', $_POST) || array_key_exists('desc_col', $_POST);

$colCount = 0;
foreach ($rows as $r) { $colCount = max($colCount, count($r)); }

$headerRow = null;
if ($hasHeaderFlag === true || ($hasHeaderFlag === null && wheeliams_is_header($rows[0]))) {
	$headerRow = $rows[0];
	$hasHeader = true;
} else {
	$hasHeader = false;
}

if ($explicit) {
	$partCol = ($_POST['part_col'] ?? '') === '' ? null : (int)$_POST['part_col'];
	$qtyCol  = ($_POST['qty_col']  ?? '') === '' ? null : (int)$_POST['qty_col'];
	$descCol = ($_POST['desc_col'] ?? '') === '' ? null : (int)$_POST['desc_col'];
} elseif ($hasHeader) {
	$m = wheeliams_detect_mapping($headerRow);
	$partCol = $m['part']; $qtyCol = $m['qty']; $descCol = $m['desc'];
} else {
	// No header: assume Part Number, Description, Quantity (the common layout).
	$partCol = 0;
	$descCol = $colCount > 2 ? 1 : null;
	$qtyCol  = $colCount > 2 ? 2 : ($colCount > 1 ? 1 : null);
}

// Column labels for the UI's mapping dropdowns.
$columns = [];
for ($i = 0; $i < $colCount; $i++) {
	$columns[] = $hasHeader ? ($headerRow[$i] ?? ('Column '.($i + 1))) : ('Column '.($i + 1));
}

$dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

$db        = PerchDB::fetch();
$Boms      = new Wheeliams_Boms();
$memberID  = (int)(PerchMembers_Session::fetch()->get('memberID'));

// Existing children of this BOM, so a re-import updates rather than duplicates.
$existing = [];
foreach ((array)$Boms->bom($kitID) as $br) {
	$existing[(int)$br['partCode']] = (int)$br['perch3_wheeliams_bomID'];
}

$lines   = [];
$anyError = false;
foreach ($dataRows as $idx => $r) {
	$partNo = $partCol !== null ? trim($r[$partCol] ?? '') : '';
	$qtyRaw = $qtyCol  !== null ? trim($r[$qtyCol]  ?? '') : '';
	$desc   = $descCol !== null ? trim($r[$descCol] ?? '') : '';

	$status = 'ok';
	$message = '';
	$childID = null;

	if ($partNo === '') {
		$status = 'error'; $message = 'No part number';
	} elseif ($qtyRaw === '' || !is_numeric($qtyRaw) || (float)$qtyRaw <= 0) {
		$status = 'error'; $message = 'Invalid quantity';
	} else {
		$comp = $db->get_row('SELECT perch3_wheeliams_componentID, partCode FROM perch3_wheeliams_components WHERE partCode='.$db->pdb($partNo).' LIMIT 1');
		if (!$comp) {
			$status = 'error'; $message = 'Not found in database';
		} elseif ((int)$comp['perch3_wheeliams_componentID'] === $kitID) {
			$status = 'error'; $message = 'A BOM can’t contain itself';
		} else {
			$childID = (int)$comp['perch3_wheeliams_componentID'];
			if (isset($existing[$childID])) { $message = 'Already in BOM — quantity will be updated'; }
		}
	}

	if ($status === 'error') $anyError = true;

	$lines[] = [
		'row'        => (int)$idx + 1,
		'partNumber' => $partNo,
		'description'=> $desc,
		'quantity'   => $qtyRaw,
		'status'     => $status,
		'message'    => $message,
		'childID'    => $childID,
	];
}

$okCount = count(array_filter($lines, fn($l) => $l['status'] === 'ok'));

if ($action === 'preview') {
	echo json_encode([
		'columns'   => $columns,
		'hasHeader' => $hasHeader,
		'mapping'   => ['part' => $partCol, 'desc' => $descCol, 'qty' => $qtyCol],
		'lines'     => $lines,
		'okCount'   => $okCount,
		'errorCount'=> count($lines) - $okCount,
		'canImport' => (!$anyError && $okCount > 0 && $partCol !== null && $qtyCol !== null),
	]);
	exit;
}

if ($action === 'import') {
	if ($partCol === null || $qtyCol === null) json_error('Map the Part Number and Quantity columns first.', 400);
	// All-or-nothing: refuse the whole import if any line is in error.
	if ($anyError) {
		http_response_code(422);
		echo json_encode([
			'error'      => 'Import cancelled — some lines could not be matched.',
			'lines'      => $lines,
			'errorCount' => count($lines) - $okCount,
		]);
		exit;
	}

	$added = 0; $updated = 0; $doneNew = [];
	foreach ($lines as $l) {
		$childID = (int)$l['childID'];
		$qty     = (float)$l['quantity'];
		if ($childID <= 0) continue; // defensive; shouldn't happen when !anyError
		if (isset($existing[$childID])) {
			$Boms->updateBom((int)$existing[$childID], $qty, $memberID);
			$updated++;
		} elseif (isset($doneNew[$childID])) {
			continue; // same new part pasted twice in one import — add it only once
		} else {
			$Boms->addToBom($type, $kitID, $childID, $qty, $memberID);
			$doneNew[$childID] = true;
			$added++;
		}
	}

	echo json_encode(['ok' => true, 'added' => $added, 'updated' => $updated, 'total' => $added + $updated]);
	exit;
}

json_error('Unknown action.', 400);
