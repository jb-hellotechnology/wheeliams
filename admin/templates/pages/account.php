<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
if(!perch_member_logged_in()){
	header("location:/");
}	
?>
<?php
perch_layout('header');
?>
	<main>
	  <h1>Account</h1>
	  <h2>Your Profile</h2>
	  <?php perch_member_form('profile.html'); ?>
	  <h2>Your Password</h2>
	  <?php perch_member_form('password.html'); ?>
	  <h2>Timesheet</h2>
	  <dl>
		  <dt>Daily Hours</dt>
		  <dd><?= staff_hours('daily') ?></dd>
		  <dt>Weekly Hours</dt>
		  <dd><?= staff_hours('weekly') ?></dd>
		  <dt>Pay Period Hours</dt>
		  <dd><?= staff_hours('period') ?></dd>
	  </dl>
	  <h2>Holiday</h2>
	  <form>
		  <p><strong>Allowance: <?= staff_holiday_allowance() ?></strong> &bull; <strong>Remaining:</strong> <?= staff_holiday_remaining() ?></p>
		  <p>Days Taken This Year:</p>
		  <?php staff_holiday_taken(); ?>
	  </form>
	  <h2>Log</h2>
	  <?php staff_log('daily') ?>
	</main>
<?php
	perch_layout('footer');
?>