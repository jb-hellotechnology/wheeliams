<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
wheeliams_require_level('admin');
?>
<?php
perch_layout('header');
?>
<main class="full">
	<h1>Staff</h1>
	<h2>Holiday Requests</h2>
	<?php holiday_requests(); ?>
</main>
<?php
perch_layout('footer');
?>