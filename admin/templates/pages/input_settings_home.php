<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
wheeliams_require_level('admin');
?>
<?php
perch_layout('header');
?>
<main class="full">
	<h1>Settings</h1>
	<div class="option-grid">
		<div class="option-card">
			<h2><a href="/settings/input-parameters/">Input Parameters</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/suppliers/">Suppliers</a></h2>
		</div>
		<div class="option-card">
			<h2><a href="/settings/email-templates/">Email Templates</a></h2>
		</div>
	</div>
</main>
<?php
perch_layout('footer');
?>