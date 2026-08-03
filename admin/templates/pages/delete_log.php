<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
if(!perch_member_logged_in()){
	header("location:/");
}	
if(check_log_owner($_GET['id'])){
	$timeStamp = get_timestamp($_GET['id']);

	if($_POST){
		$id = $_POST['timestamp'];
		delete_timestamp($id);
	}
	
}else{
	perch_member_log_out();
	header("location:/");
}
?>
<?php
perch_layout('header');
?>
<main>
	<h1>Delete Timestamp</h1>
	<form method="post" action="/account/delete-log/?id=<?= $_GET['id'] ?>">
		<section>
			<header>Delete</header>
			<article>
				<?php
				if($_POST){
					echo '<p class="alert alert-success"><strong>Success</strong></p>';
				}else{
					echo '<p>Delete <strong>'.$timeStamp.'</strong> from your log?</p>';
				}
				?>
			</article>
			<footer>
				<?php
					if(!$_POST){
				?>
						<input type="submit" value="Delete" class="button danger" />
						<input type="hidden" value="<?= $_GET['id'] ?>" name="timestamp" />
				<?php
					}else{
				?>
						<a class="button secondary" href="/account/time-log/">Back to Log</a>
				<?php
					}
				?>
			</footer>
		</section>
	</form>
</main>
<?php
perch_layout('footer');
?>