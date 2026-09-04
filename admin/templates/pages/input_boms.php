<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

wheeliams_require_level('admin');
?>
<?php
perch_layout('header');
?>
<main class="full">
	<?php
	$componentData = component($_GET['id']);
	$bomData = bom($_GET['id']);
	$type = $_GET['type'];
	if($type){
		if($_GET['edit']){
			$json = json_decode($componentData['dynamicFields'],true);
			echo "<h1>".$json['part_description']." (".$componentData['partCode'].")</h1>";
		}else{
			if($type=='manufactured'){
				echo "<h1>Component BOMs</h1>";
			}else{
				echo "<h1>Kit BOMs</h1>";
			}
		}
		
		if(!$_GET['edit']){
			/* TABLE OF ITEMS */
			wheeliams_bom_table($type);
		}else{
			if(!empty($_GET['id'])){
				/* Multilevel BOM with Edit/Delete on this product's direct lines */
				wheeliams_bom_explosion_table((int)$_GET['id'], true, $_GET['type']);
			}
			echo '<div class="split">';
			echo '<div>';
		}
		PerchSystem::set_var('bomID', '');
		PerchSystem::set_var('partCode', '');
		PerchSystem::set_var('quantity', '');
		
		/* ADD NEW ITEM OF TYPE */
		
		/* LIST ITEMS OF TYPE WITH EDIT/DELETE OPTIONS */
		if($_GET['edit']){
			/* FILES */
			// Order the ASSIGN areas by likely use: Components, then Fasteners, then Raw Materials.
			if($_GET['type']=='products'){
				wheeliams_form('bom_components.html');
				wheeliams_form('bom_fasteners.html');
				wheeliams_form('bom_raw-materials.html');
			}else{
				wheeliams_form('bom_raw-materials.html');
			}
			echo '</div>';
			echo '<div>';
			
			wheeliams_form('bom_notes.html');

			PerchSystem::set_var('partCode', $componentData['partCode']);
			
			if($type=='products'){
				echo '<section class="">';
				echo '<header>';
				echo '<h2>Files</h2>';
				echo '</header>';
				echo '<article>';
				echo '<div id="product-files">
				<div id="product-files-list"></div>
				</div>';
				echo '<script>loadProductFiles("'.$componentData['partCode'].'", "KIT");</script>';
				echo '</article>';
				echo '</section>';
				wheeliams_form('bom_file_add-'.$_GET['type'].'.html');
			}

			echo '</div>';
			echo '<div>';
			/* UPDATE HISTORY HERE */
			//wheeliams_component_changelog($_GET['type'],$_GET['id']);
		}else{
			
		}
		if($_GET['edit']){
			echo '<p><a href="/boms/?type='.$_GET['type'].'" class="button back">&larr; Back</a></p>';
		}
	
	}else{
	?>
	<h1>Configure BOMs</h1>
	<div class="option-grid">
		<div class="option-card">
			<h2><a href="/boms?type=manufactured">Components</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/boms?type=products">Kits</a></h2>
		</div>
	</div>
	<?php
	}
	?>
</main>
<?php
perch_layout('footer');
?>