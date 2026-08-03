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
	<h1>Staff</h1>
	<h2>Holiday Requests</h2>
	<?php holiday_requests(); ?>
</main>
<?php
perch_layout('footer');
?>