<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
if(!perch_member_logged_in()){
	header("location:/");
}
update_time($_POST['staffID'], $_POST['type'], $_POST['date'], $_POST['time']);	
?>