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
		echo "<h1>Supplier Contact</h1>";
		if($_GET['delete'] && $_GET['supplier']){
			/* DELETE ITEM */

			wheeliams_form('supplier_contact_delete.html');
		}else{
			
			echo '<div class="split">';
			echo '<div>';
			/* ADD NEW ITEM OF TYPE */
			wheeliams_form('supplier_contact_edit.html');
			echo '</div>';
			echo '<div>';

			echo '</div>';
			echo '</div>';
			
			if($_GET['edit']){
				echo '<p><a href="/settings/suppliers/?edit=1&id="'.$_GET['supplier'].' class="button back">&larr; Back</a></p>';
			}
		}
	?>
</main>
<?php
perch_layout('footer');
?>