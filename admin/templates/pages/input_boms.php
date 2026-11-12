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

		// Tabs on the edit view: BOM / Cutting & Rolling (tubes) / Workshop Notes.
		$crTab = 'bom';
		if($_GET['edit'] && ($_GET['tab'] ?? '') === 'cutting') $crTab = 'cutting';
		elseif($_GET['edit'] && ($_GET['tab'] ?? '') === 'notes') $crTab = 'notes';
		if($_GET['edit'] && !empty($_GET['id'])){
			$base = '/boms/?type='.htmlspecialchars($_GET['type']).'&edit=1&id='.(int)$_GET['id'];
			echo '<nav class="bom-tabs">';
			echo '<a class="'.($crTab==='bom'?'active':'').'" href="'.$base.'">'.($type==='manufactured'?'Component BOM':'Kit BOM').'</a>';
			echo '<a class="'.($crTab==='cutting'?'active':'').'" href="'.$base.'&tab=cutting">Cutting &amp; Rolling</a>';
			echo '<a class="'.($crTab==='notes'?'active':'').'" href="'.$base.'&tab=notes">Workshop Notes</a>';
			echo '</nav>';
		}

		if(!$_GET['edit']){
			/* TABLE OF ITEMS */
			wheeliams_bom_table($type);
		}elseif($crTab === 'cutting'){
			if(!empty($_GET['id'])){
				wheeliams_cutting_rolling_panel((int)$_GET['id'], $type);
			}
		}elseif($crTab === 'notes'){
			if(!empty($_GET['id'])){
				wheeliams_workshop_notes_panel((int)$_GET['id'], $componentData['partCode'], $type);
			}
		}else{
			if(!empty($_GET['id'])){
				/* Multilevel BOM with Edit/Delete on this product's direct lines */
				wheeliams_bom_explosion_table((int)$_GET['id'], true, $_GET['type']);
				/* Paste-to-import panel (match part numbers to the database) */
				wheeliams_bom_import_panel((int)$_GET['id'], $_GET['type']);
			}
			echo '<div class="split">';
			echo '<div>';
		}
		PerchSystem::set_var('bomID', '');
		PerchSystem::set_var('partCode', '');
		PerchSystem::set_var('quantity', '');
		
		/* ADD NEW ITEM OF TYPE */
		
		/* LIST ITEMS OF TYPE WITH EDIT/DELETE OPTIONS */
		if($_GET['edit'] && $crTab === 'bom'){
			/* FILES */
			// Order the ASSIGN areas by likely use: Components, then Fasteners, then Raw Materials.
			if($_GET['type']=='products'){
				wheeliams_form('bom_components.html');
				wheeliams_form('bom_fasteners.html');
				wheeliams_form('bom_raw-materials.html');
				wheeliams_form('bom_tubes.html');
			}else{
				// Components can take fasteners, raw materials and tubes; all roll into cost.
				wheeliams_form('bom_fasteners.html');
				wheeliams_form('bom_raw-materials.html');
				wheeliams_form('bom_tubes.html');
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
				echo '<div id="product-files"></div>';
				$canEdit  = wheeliams_can_order() ? 'true' : 'false';
				echo '<script>wheeliamsInitFileManager("product-files", '
					.json_encode($componentData['partCode']).', "KIT", { canEdit: '.$canEdit
					.', categories: '.json_encode(wheeliams_drive_categories('KIT')).' });</script>';
				echo '</article>';
				echo '</section>';
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