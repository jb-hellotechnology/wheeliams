<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
if(perch_member_logged_in()){

	header("location:/");
	
}
perch_layout('header');
?>
<main class="narrow">
	<h1>Hello 👋</h1>
	<section>
		<header>
			<h2>Sign In</h2>
		</header>
		<?php perch_members_login_form(); ?>
	</section>
	<p>Forgotten your password? <a href="/reset/">Reset it now</a>.</p>
</main>
<?php
perch_layout('footer');
?>