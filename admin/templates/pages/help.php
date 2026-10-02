<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
// Help & guidance — visible to any logged-in member; editable by admins.
if(!perch_member_logged_in()){
	header('location:/');
	exit;
}

$db = PerchDB::fetch();
$db->execute("CREATE TABLE IF NOT EXISTS perch3_wheeliams_help (
	id INT UNSIGNED NOT NULL DEFAULT 1,
	content MEDIUMTEXT,
	updated_at DATETIME DEFAULT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$isAdmin = perch_member_has_tag('admin');

// Save (admins only).
if($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['help_content'])){
	$content = $_POST['help_content'];
	if($db->get_row("SELECT id FROM perch3_wheeliams_help WHERE id=1")){
		$db->update('perch3_wheeliams_help', array('content' => $content, 'updated_at' => date('Y-m-d H:i:s')), 'id', 1);
	}else{
		$db->insert('perch3_wheeliams_help', array('id' => 1, 'content' => $content, 'updated_at' => date('Y-m-d H:i:s')));
	}
	PerchUtil::redirect('/help/?saved=1');
}

$row     = $db->get_row("SELECT content FROM perch3_wheeliams_help WHERE id=1");
$content = $row['content'] ?? '';

perch_layout('header');
?>
<main class="full">
	<h1>Help &amp; Guidance</h1>
	<?php if(($_GET['saved'] ?? '') === '1'){ echo '<p class="alert success no-print">Guidance saved.</p>'; } ?>

	<div class="help-content flow">
		<?php echo $content !== '' ? $content : '<p><em>No guidance has been added yet.</em></p>'; ?>
	</div>

	<?php if($isAdmin){ ?>
	<details class="help-edit no-print" <?php echo $content === '' ? 'open' : ''; ?>>
		<summary>Edit guidance</summary>
		<form method="post" action="/help/" class="flow">
			<textarea name="help_content" class="redactor"><?php echo htmlspecialchars($content); ?></textarea>
			<p><button type="submit" class="button primary">Save Guidance</button></p>
		</form>
	</details>
	<?php } ?>
</main>
<?php perch_layout('footer'); ?>
