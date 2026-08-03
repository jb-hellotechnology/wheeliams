<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
if(perch_member_logged_in()){

	header("location:/");
	
}
perch_layout('header');
?>
<main class="narrow">
	<h1>Forgotten Your Password? 🔑</h1>
	<section>
		<header>
			<h2>Reset Your Password</h2>
		</header>
		<?php perch_member_form('reset.html'); ?>
	</section>
	
	<p>Already a member? <a href="/">Sign in</a>.</p>
</main>
<?php
perch_layout('footer');
?>