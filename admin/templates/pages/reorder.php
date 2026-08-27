<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php

// Order Analysis / Reorder List — Access Level 2 (View & Order) and 3 (Admin).
if(!perch_member_logged_in()){
	header('location:/');
	exit;
}
wheeliams_require_level('view_order');

$Analysis = new Wheeliams_Analysis();
$Analysis->install();
(new Wheeliams_Purchase_Orders())->install(); // needed for the cascading supplier exclusion

// Handle actions before any output (they redirect).
if($_SERVER['REQUEST_METHOD'] === 'POST' && wheeliams_can_order()){
	$Session = PerchMembers_Session::fetch();
	$action  = $_POST['action'] ?? '';

	if($action === 'run_analysis'){
		$runID = $Analysis->run($Session->get('memberID'));
		PerchUtil::redirect('/reorder/?run='.$runID);
	}

	if($action === 'update_aq'){
		if(!empty($_POST['aq']) && is_array($_POST['aq'])){
			foreach($_POST['aq'] as $lineID => $aq){
				$Analysis->updateAQ((int)$lineID, $aq);
			}
		}
		PerchUtil::redirect('/reorder/?run='.(int)($_POST['run'] ?? 0).'&saved=1');
	}

	if($action === 'save_po'){
		$isPO = (($_POST['otype'] ?? '') === 'po');
		$poID = wheeliams_save_purchase_order(
			(int)($_POST['run'] ?? 0),
			$_POST['supplier'] ?? '',
			$_POST['ctype'] ?? '',
			$_POST['ptype'] ?? '',
			$isPO,
			$Session->get('memberID'),
			(int)($_POST['template'] ?? 0)
		);
		if($poID){
			PerchUtil::redirect('/purchase-orders/?po='.$poID);
		}
		PerchUtil::redirect('/reorder/?run='.(int)($_POST['run'] ?? 0).'&noorder=1');
	}
}

perch_layout('header');

$runID = (int)($_GET['run'] ?? 0);
if(!$runID){ $runID = $Analysis->latestRunID(); }
?>
<main class="full">
	<h1 class="with-button">
		<span>Order Analysis &amp; Reorder List</span>
		<form method="post" action="/reorder/" class="flow">
			<input type="hidden" name="action" value="run_analysis">
			<button type="submit" class="button primary">Run Analysis Now</button>
			<!-- <small class="help">Reads saleable products currently on order from Shopify and builds a reorder list.</small> -->
		</form>
	</h1>

	<?php
	if(($_GET['saved'] ?? '') === '1'){
		echo '<p class="alert success">Order quantities saved.</p>';
	}
	if(($_GET['noorder'] ?? '') === '1'){
		echo '<p class="alert warning">Nothing to save — pick a specific supplier with at least one line.</p>';
	}

	// Run selector
	$runs = $Analysis->allRuns();
	if($runs){
		echo '<section><form method="get" action="/reorder/"><header><h2>Analysis Run</h2></header><article>';
		echo '<label for="run">Select</label><select name="run" id="run">';
		foreach($runs as $r){
			$rid = (int)$r['perch3_wheeliams_analysis_runID'];
			$sel = ($rid === $runID) ? ' selected' : '';
			echo '<option value="'.$rid.'"'.$sel.'>#'.$rid.' — '.htmlspecialchars($r['created_at']).'</option>';
		}
		echo '</select></article><footer><input type="submit" value="Select" class="button primary" /></footer></form></section>';
	}

	if($runID){
		$info = $Analysis->runInfo($runID);
		if($info){
			echo '<section><header><h2>Reorder list — run #'.$runID.'</h2></header><article><p><strong>Created at:</strong> '.htmlspecialchars($info['created_at']).'</p>';
		}
		wheeliams_reorder_list_table($runID);

		echo '</article></section>';
		
		// ---- Generate order email (preview only) ----
		$templates = wheeliams_email_templates();
		echo '<section><header><h2>Generate Order Email</h2></header><article>';
		if(!$templates){
			echo '<p>No email templates yet. <a href="/settings/email-templates/?new=1">Create one</a> first.</p>';
		}else{
			$suppliers = wheeliams_run_suppliers($runID);
			$types     = wheeliams_run_types($runID);
			$processes = wheeliams_run_process_types($runID);

			echo '<form method="get" action="/reorder/" class="order-email-picker">';
			echo '<input type="hidden" name="run" value="'.(int)$runID.'">';
			echo '<input type="hidden" name="preview" value="1">';

			echo '<label for="supplier">Supplier</label> <select name="supplier" id="supplier"><option value="">All</option>';
			foreach($suppliers as $s){
				$sid = (int)$s['supplierID'];
				$sel = ((string)$sid === (string)($_GET['supplier'] ?? '')) ? ' selected' : '';
				echo '<option value="'.$sid.'"'.$sel.'>'.htmlspecialchars($s['supplierName'] ?: '—').'</option>';
			}
			echo '</select> ';

			echo '<label for="ctype">Component Type</label> <select name="ctype" id="ctype"><option value="">All</option>';
			foreach($types as $t){
				$sel = ($t['type'] === ($_GET['ctype'] ?? '')) ? ' selected' : '';
				echo '<option value="'.htmlspecialchars($t['type']).'"'.$sel.'>'.htmlspecialchars($t['type']).'</option>';
			}
			echo '</select> ';

			echo '<label for="ptype">Process Type</label> <select name="ptype" id="ptype"><option value="">All</option>';
			foreach($processes as $p){
				$sel = ($p['process_type'] === ($_GET['ptype'] ?? '')) ? ' selected' : '';
				echo '<option value="'.htmlspecialchars($p['process_type']).'"'.$sel.'>'.htmlspecialchars($p['process_type']).'</option>';
			}
			echo '</select> ';

			echo '<label for="otype">Order Type</label> <select name="otype" id="otype">';
			$isPO = (($_GET['otype'] ?? '') === 'po');
			echo '<option value="enquiry"'.($isPO ? '' : ' selected').'>Enquiry</option>';
			echo '<option value="po"'.($isPO ? ' selected' : '').'>Purchase Order</option>';
			echo '</select> ';

			echo '<label for="template">Template</label> <select name="template" id="template">';
			foreach($templates as $tpl){
				$tid = (int)$tpl['perch3_wheeliams_email_templateID'];
				$sel = ((string)$tid === (string)($_GET['template'] ?? '')) ? ' selected' : '';
				echo '<option value="'.$tid.'"'.$sel.'>'.htmlspecialchars($tpl['name']).'</option>';
			}
			echo '</select> ';

			echo '</article><footer><button type="submit" class="button primary">Preview Email</button></footer>';
			echo '</form>';
		}
		echo '<footer></footer></section>';
		
		if(!empty($_GET['preview'])){
			wheeliams_order_email_preview(
				$runID,
				$_GET['supplier'] ?? '',
				$_GET['ctype'] ?? '',
				$_GET['ptype'] ?? '',
				(($_GET['otype'] ?? '') === 'po'),
				(int)($_GET['template'] ?? 0)
			);
		}
	}else{
		echo '<p>No analysis has been run yet. Use <strong>Run Analysis Now</strong> to create the first reorder list.</p>';
	}
	?>
</main>
<?php
perch_layout('footer');
?>
