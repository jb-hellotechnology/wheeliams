<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
if(!perch_member_logged_in()){
	header("location:/");
}	
if(check_log_owner($_GET['id'])){
	$timeStamp = get_timestamp($_GET['id']);
	
	if($_POST){
		$timeStamp = $_POST['timestamp'];
		$id = $_GET['id'];
		update_timestamp($id, $timeStamp);
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
	<h1>Edit Timestamp</h1>
	<form method="post" action="/account/edit-log/?id=<?= $_GET['id'] ?>">
		<section>
			<header>Edit</header>
			<article>
				<?php
				if($_POST){
					echo '<p class="alert alert-success"><strong>Success</strong></p>';
				}else{
				?>
					<label>Time Stamp</label>
					<input type="datetime-local" value="<?= $timeStamp ?>" name="timestamp" step="any" />
				<?php  
				}
				?>
			</article>
			<footer>
				<?php
					if(!$_POST){
				?>
						<input type="submit" value="Update" class="button primary" />
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
	<div class="panel">
		<h2>More Options</h2>
		<p><a href="/account/delete-log/?id=<?= $_GET['id'] ?>" class="warning">Delete Timestamp</a></p>
	</div>
</main>
<?php
perch_layout('footer');
?>