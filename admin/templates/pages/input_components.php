<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if(!perch_member_logged_in() OR !perch_member_has_tag('admin')){
	header("location:/");
}
?>
<?php
perch_layout('header');
?>
<main class="full">
	<p class="admin">Only Visible to Administrators</p>
	<?php
	$componentData = component($_GET['id']);
	if($_GET['type']){
		if($_GET['id']){
			echo "<h1>".$componentData['partCode']."</h1>";
		}else{
			if($_GET['type']=='manufactured'){
				$title = 'Manufactured &amp; Purchased Components';
			}elseif($_GET['type']=='fasteners'){
				$title = 'Fasteners';
			}else{
				$title = 'Raw Materials';
			}
			echo '<h1>'.$title.'</h1>';
		}
		if($_GET['delete'] && $_GET['id']){
			/* DELETE ITEM */

			wheeliams_form('component_delete.html');
		}else{
			
			if(!$_GET['edit']){
				/* TABLE OF ITEMS */
				wheeliams_component_table($_GET['type']);
			}else{
				echo '<div class="split">';
				echo '<div>';
			}
			
			/* ADD NEW ITEM OF TYPE */
			wheeliams_form('component_'.$_GET['type'].'.html');
			/* LIST ITEMS OF TYPE WITH EDIT/DELETE OPTIONS */
			if($_GET['edit']){
				/* FILES */
				$Components = new Wheeliams_Components();
				$component = $Components->find($_GET['id']);
				PerchSystem::set_var('partCode', $component->partCode());
				echo '</div>';
				echo '<div>';
				echo '<section class="">';
				echo '<header>';
				echo '<h2>Files</h2>';
				echo '</header>';
				echo '<article>';
				echo '<div id="product-files">
				  <div id="product-files-list"></div>
				</div>';
				if($_GET['type']=='manufactured'){
					echo '<script>loadProductFiles("'.$componentData['partCode'].'", "COMPONENT");</script>';	
				}elseif($_GET['type']=='fasteners'){
					echo '<script>loadProductFiles("'.$componentData['partCode'].'", "FASTENER");</script>';
				}else{
					echo '<script>loadProductFiles("'.$componentData['partCode'].'", "RAW MATERIALS");</script>';
				}
				echo '</article>';
				echo '</section>';
				wheeliams_form('component_file_add-'.$_GET['type'].'.html');
				
				/* SUPPLIERS */
				wheeliams_component_supplier_table($_GET['id']);
				wheeliams_form('component_supplier_add.html');
				/* BOMS */
				wheeliams_component_material_table($_GET['id']);
				/* DUPLICATE */
				wheeliams_form('component_duplicate.html');
				echo '</div>';
				echo '</div>';
				echo '<div>';
				/* BOMS */
				wheeliams_component_bom_table($componentData['partCode']);
				/* UPDATE HISTORY HERE */
				wheeliams_component_changelog($_GET['type'],$_GET['id']);
			}else{
				
			}
			if($_GET['edit']){
				echo '<p><a href="/components/?type='.$_GET['type'].'" class="button back">&larr; Back</a></p>';
			}
		}
	}else{
	?>
	<h1>Configure Components</h1>
	<div class="option-grid">
		<div class="option-card">
			<h2><a href="/components?type=manufactured">Manufactured &amp; Purchased</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/components?type=fasteners">Fasteners</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/components?type=raw-materials">Raw Materials</a></h2>
		</div>
	</div>
	<?php
	}
	?>
</main>
<?php
perch_layout('footer');
?>