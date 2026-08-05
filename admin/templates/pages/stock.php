<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
// Stock Control is for Access Level 2 (View & Order) and Level 3 (Admin) only.
if(!perch_member_logged_in()){
	header('location:/');
	exit;
}
wheeliams_require_level('view_order');

// Ensure the stock tables exist (cheap no-op once created).
$Stock = new Wheeliams_Stock();
$Stock->install();

perch_layout('header');

$action = $_GET['action'] ?? '';
?>
<main class="full">
<?php
if($action === 'manage'){

	echo '<h1>Manage Stock Level</h1>';
	if(empty($_GET['component'])){
		wheeliams_stock_picker_table('manage', 'Manage Stock');
	}else{
		echo '<p><a href="?action=manage" class="button back">&larr; Back to list</a></p>';
		wheeliams_form('stock_manage.html');
		wheeliams_stock_movements_table((int)$_GET['component']);
	}

}elseif($action === 'lookup'){

	echo '<h1>Stock Look-up</h1>';
	wheeliams_stock_lookup();

}elseif($action === 'report'){

	echo '<h1>Stock Level Report</h1>';
	wheeliams_stock_report();

}else{

	echo '<h1>Stock Control</h1>';
	echo '<div class="option-grid">';
	echo '<div class="option-card"><h2><a href="/stock/?action=manage">Manage Stock Level</a></h2></div>';
	echo '<div class="option-card"><h2><a href="/stock/?action=lookup">Look-up</a></h2></div>';
	echo '<div class="option-card"><h2><a href="/stock/?action=report">Stock Report</a></h2></div>';
	echo '</div>';

}
?>
</main>
<?php
perch_layout('footer');
?>
