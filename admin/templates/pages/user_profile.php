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
	<h1>Profile</h1>
	<section>
		<header>
			<h2>Update Profile</h2>
		</header>
		<?php perch_member_form('profile.html'); ?>
	</section>
</main>
<?php
perch_layout('footer');
?>