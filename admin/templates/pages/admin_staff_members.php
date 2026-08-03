<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
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
	if($_GET['id']){
	?>
		<h1>Manage Staff</h1>
		<?php wheeliams_form('staff_profile_form.html'); ?> 
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