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
		echo "<h1>BOM Component</h1>";
		if($_GET['delete'] && $_GET['id']){
			/* DELETE ITEM */

			wheeliams_form('bom_delete_row.html');
		}else{
			
			echo '<div class="split">';
			echo '<div>';
			/* ADD NEW ITEM OF TYPE */
			wheeliams_form('bom_edit_row.html');
			echo '</div>';
			echo '<div>';

			echo '</div>';
			echo '</div>';
			
			if($_GET['edit']){
				echo '<p><a href="/boms/?type='.$_GET['type'].'&edit=1&id='.$_GET['id'].'" class="button back">&larr; Back</a></p>';
			}
		}
	?>
</main>
<?php
perch_layout('footer');
?>