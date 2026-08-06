<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
// Purchase Orders — Access Level 2 (View & Order) and 3 (Admin).
if(!perch_member_logged_in()){
	header('location:/');
	exit;
}
wheeliams_require_level('view_order');

$PO = new Wheeliams_Purchase_Orders();
$PO->install();

// Check-in action (before any output — it redirects).
if($_SERVER['REQUEST_METHOD'] === 'POST' && wheeliams_can_order() && ($_POST['action'] ?? '') === 'checkin'){
	$Session  = PerchMembers_Session::fetch();
	$poID     = (int)($_POST['po'] ?? 0);
	$received = (isset($_POST['received']) && is_array($_POST['received'])) ? $_POST['received'] : array();
	$checked  = (isset($_POST['checked'])  && is_array($_POST['checked']))  ? $_POST['checked']  : array();
	$PO->checkIn($poID, $received, $checked, $Session->get('memberID'));
	PerchUtil::redirect('/purchase-orders/?po='.$poID.'&checkedin=1');
}

// Send-to-supplier action (Brevo). Redirects with a status message.
if($_SERVER['REQUEST_METHOD'] === 'POST' && wheeliams_can_order() && ($_POST['action'] ?? '') === 'send_email'){
	require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
	include_once $_SERVER['DOCUMENT_ROOT'].'/admin/addons/apps/wheeliams/Wheeliams.orderemail.class.php';

	$Session    = PerchMembers_Session::fetch();
	$poID       = (int)($_POST['po'] ?? 0);
	$templateID = (int)($_POST['template'] ?? 0);

	$po        = $PO->order($poID);
	$lines     = $PO->lines($poID);
	$Templates = new Wheeliams_Email_Templates();
	$template  = $templateID ? $Templates->get($templateID) : null;

	$result = array('ok' => false, 'msg' => 'Missing order or template.');
	if($po && $template){
		$Mailer = new Wheeliams_Order_Email();
		$result = $Mailer->send($po, $lines, $template);
		if($result['ok']){
			$PO->markSent($poID, $Session->get('memberID'), $result['to'] ?? '');
		}
	}
	PerchUtil::redirect('/purchase-orders/?po='.$poID.'&'.($result['ok'] ? 'sent' : 'senderr').'='.rawurlencode($result['msg']));
}

perch_layout('header');

$poID = (int)($_GET['po'] ?? 0);
?>
<style>@media print{ .no-print{ display:none !important; } nav, header .tabs, .main-nav-container{ display:none !important; } }</style>
<main class="full">
<?php
if($poID){

	echo '<h1>Purchase Order</h1>';
	if(($_GET['checkedin'] ?? '') === '1'){
		echo '<p class="alert success no-print">Received quantities checked in to stock.</p>';
	}
	if(($_GET['sent'] ?? '') !== ''){
		echo '<p class="alert success no-print">Order email sent. '.htmlspecialchars($_GET['sent']).'</p>';
	}
	if(($_GET['senderr'] ?? '') !== ''){
		echo '<p class="alert warning no-print">Order email not sent &mdash; '.htmlspecialchars($_GET['senderr']).'</p>';
	}
	wheeliams_po_detail($poID);

}else{

	echo '<h1>Purchase Orders</h1>';

	// Search + supplier filter
	$q = trim($_GET['q'] ?? '');
	echo '<form method="get" action="/purchase-orders/" class="flow no-print">';
	echo '<label for="q">Search</label> <input type="text" name="q" id="q" value="'.htmlspecialchars($q).'" placeholder="Number or supplier"> ';

	echo '<label for="supplier">Supplier</label> <select name="supplier" id="supplier" onchange="this.form.submit()"><option value="">All</option>';
	foreach($PO->suppliersWithOrders() as $s){
		$sid = (int)$s['supplierID'];
		$sel = ((string)$sid === (string)($_GET['supplier'] ?? '')) ? ' selected' : '';
		echo '<option value="'.$sid.'"'.$sel.'>'.htmlspecialchars($s['supplierName'] ?: '—').'</option>';
	}
	echo '</select> ';
	echo '<button type="submit" class="button">Search</button>';
	echo '</form>';

	if($q !== ''){
		// Simple search view
		$rows = $PO->search($q);
		if(!$rows){
			echo '<p>No orders match &ldquo;'.htmlspecialchars($q).'&rdquo;.</p>';
		}else{
			echo '<div class="table-container"><table class="datatable"><thead class="first-row"><th>Number</th><th>Type</th><th>Supplier</th><th>Date</th><th>Status</th><th></th></thead><tbody>';
			foreach($rows as $r){
				$id = (int)$r['perch3_wheeliams_purchase_orderID'];
				echo '<tr>';
				echo '<td>'.htmlspecialchars($r['number']).'</td>';
				echo '<td>'.($r['is_enquiry'] ? 'Enquiry' : 'Purchase Order').'</td>';
				echo '<td>'.htmlspecialchars($r['supplierName'] ?: '—').'</td>';
				echo '<td>'.htmlspecialchars($r['created_at']).'</td>';
				echo '<td>'.htmlspecialchars(ucfirst(str_replace('-', ' ', $r['status']))).'</td>';
				echo '<td><a class="button small" href="?po='.$id.'">View</a></td>';
				echo '</tr>';
			}
			echo '</tbody></table></div>';
		}
	}else{
		$supplierFilter = !empty($_GET['supplier']) ? (int)$_GET['supplier'] : null;
		wheeliams_po_list_table($supplierFilter);
	}

}
?>
</main>
<?php
perch_layout('footer');
?>
