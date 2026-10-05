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

?>
<main class="full">
<?php
// One combined Stock Control screen: the searchable table of all parts (its
// search box is the look-up) and, when a part is selected, that part's stock
// detail — manage form, used-on, drawing and movements.
if(!empty($_GET['component'])){

	$id        = (int)$_GET['component'];
	$component = component($id);
	if(!$component){
		echo '<h1>Stock Control</h1><p>Part not found. <a href="/stock/">Back to stock list</a></p>';
	}else{
		$dyn = json_decode($component['dynamicFields'], true) ?: array();
		echo '<h1>'.htmlspecialchars(($dyn['part_description'] ?? 'Stock').' ('.$component['partCode'].')').'</h1>';
		echo '<p><a href="/stock/" class="button back">&larr; Back to stock list</a></p>';
		echo '<div class="split">';
		echo '<div>';
		wheeliams_form('stock_manage.html');
		wheeliams_stock_used_on_panel($id);
		echo '</div>';
		echo '<div>';
		wheeliams_component_drawings_panel($id);
		wheeliams_stock_movements_table($id);
		echo '</div>';
		echo '</div>';
	}

}else{

	echo '<h1>Stock Control</h1>';
	wheeliams_stock_table();

}
?>
</main>
<?php
perch_layout('footer');
?>
