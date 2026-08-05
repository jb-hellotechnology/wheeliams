<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
wheeliams_require_level('admin');
?>
<?php
perch_layout('header');
?>
<main class="full">
	<?php
	if($_GET['type']){
		echo "<h1>".ucwords(str_replace("-", " ", $_GET['type']))."</h1>";
		if($_GET['delete'] && $_GET['id']){
			/* DELETE ITEM */
			wheeliams_form('settings_delete.html');
			//echo '<p><a href="/settings/input-parameters/?type='.$_GET['type'].'">&larr; Back</a></p>';
		}else{
			if(!$_GET['edit']){
				/* TABLE OF ITEMS */
				wheeliams_settings_input_table($_GET['type']);
			}
			/* ADD NEW ITEM OF TYPE */
			wheeliams_form('settings_'.$_GET['type'].'.html');
			/* LIST ITEMS OF TYPE WITH EDIT/DELETE OPTIONS */
			if($_GET['edit']){
				
				/* UPDATE HISTORY HERE */
				wheeliams_settings_changelog($_GET['type'],$_GET['id']);
			}else{
				
			}
			if($_GET['edit']){
				echo '<p><a href="/settings/input-parameters/?type='.$_GET['type'].'" class="button back">&larr; Back</a></p>';
			}
		}
	}else{
	?>
	<h1>Input Parameters</h1>
	<div class="option-grid">
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=part-no-prefix">Part No. Prefix</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=part-issue-code">Part Issue Code</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=component-type-list">Component Type List</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=part-status-code">Part Status Code</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=product-group-description">Product Group Description</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=vehicle-list">Vehicle List</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=uom">Unit of Measure</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=process-type">Process Type</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=component-type">Component Type</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=fastener-type">Fastener Type</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=fastener-finish">Fastener Finish</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=thread-size">Thread Size</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=head-type">Head Type</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=fastener-grade">Fastener Grade</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=generic-material">Generic Material</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=supplier-email-template">Supplier Email Template</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/input-parameters?type=contact-types">Contact Types</a></h2>
		</div>
	</div>
	<?php
	}
	?>
</main>
<?php
perch_layout('footer');
?>