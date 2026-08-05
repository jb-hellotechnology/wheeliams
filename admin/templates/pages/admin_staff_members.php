<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
wheeliams_require_level('admin');
?>
<?php
perch_layout('header');
?>
<main class="full">
	<?php
	if($_GET['id']){
	?>
		<h1>Manage Staff</h1>
		<?php
		if(($_GET['access'] ?? '') === 'saved'){
			echo '<p class="alert success">Access level updated.</p>';
		}
		if(($_GET['access'] ?? '') === 'blocked'){
			echo '<p class="alert warning">You can’t remove your own Admin access.</p>';
		}
		wheeliams_form('staff_profile_form.html');
		wheeliams_staff_access_panel($_GET['id']);
		?>
	<?php
	}else{
	?>
		<h1>Staff Members</h1>
		<?php list_staff(); ?>
		<?php perch_member_form('register.html'); ?> 
	<?php
	}
	?>
</main>
<?php
perch_layout('footer');
?>