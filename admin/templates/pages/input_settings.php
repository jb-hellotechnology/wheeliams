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
		if($_GET['type'] === 'tube-description'){ wheeliams_seed_tube_descriptions(); } // first-visit seed
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
			<?php
			// Parameter buttons kept in alphabetical order by label (sorted below)
			// so they stay easy to find as the list grows.
			$inputParameters = array(
				'part-no-prefix'            => 'Part No. Prefix',
				'part-issue-code'           => 'Part Issue Code',
				'component-type-list'       => 'Component Type List',
				'part-status-code'          => 'Part Status Code',
				'product-group-description' => 'Product Group Description',
				'vehicle-list'              => 'Vehicle List',
				'uom'                       => 'Unit of Measure',
				'process-type'              => 'Process Type',
				'component-type'            => 'Component Type',
				'fastener-type'             => 'Fastener Type',
				'fastener-finish'           => 'Fastener Finish',
				'thread-size'               => 'Thread Size',
				'head-type'                 => 'Head Type',
				'fastener-grade'            => 'Fastener Grade',
				'generic-material'          => 'Generic Material',
				'tube-description'          => 'Tube Descriptions',
				'supplier-email-template'   => 'Supplier Email Template',
				'contact-types'             => 'Contact Types',
			);
			asort($inputParameters, SORT_NATURAL | SORT_FLAG_CASE);
			foreach($inputParameters as $slug => $label){
			?>
			<div class="option-card">
				<h2><a href="/settings/input-parameters?type=<?php echo $slug; ?>"><?php echo htmlspecialchars($label); ?></a></h2>
			</div>
			<?php } ?>
		</div>
	</div>
	<?php
	}
	?>
</main>
<?php
perch_layout('footer');
?>