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
		echo "<h1>Suppliers</h1>";
		if($_GET['delete'] && $_GET['id']){
			/* DELETE ITEM */

			wheeliams_form('supplier_delete.html');
		}else{
			if(!$_GET['edit']){
				/* TABLE OF ITEMS */
				wheeliams_supplier_table($_GET['type']);
			}
			
			echo '<div class="split">';
			echo '<div>';
			/* ADD NEW ITEM OF TYPE */
			wheeliams_form('supplier.html');
			echo '</div>';
			echo '<div>';
			/* CONTACTS */
			wheeliams_supplier_contact_table($_GET['id']);
			wheeliams_form('supplier_contact_add.html');
			echo '</div>';
			echo '</div>';
			
			/* LIST ITEMS OF TYPE WITH EDIT/DELETE OPTIONS */
			if($_GET['edit']){
				/* UPDATE HISTORY HERE */
				wheeliams_supplier_changelog($_GET['id']);
			}else{
				
			}
			
			if($_GET['edit']){
				echo '<p><a href="/settings/suppliers/" class="button back">&larr; Back</a></p>';
			}
		}
	?>
</main>
<?php
perch_layout('footer');
?>