<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
if(!perch_member_logged_in()){
	header("location:/");
}

if($_GET['y']){
	$year = $_GET['y'];
}else{
	$year = date('Y');
}

if($_GET['m']){
	$month = $_GET['m'];
}else{
	$month = date('n')-1;
}

if($month==0){
	$month = 12;
	$year = $year-1;
}

if($month==1){
	$prev_month = 12;
	$prev_year = $year - 1;
	$next_month = $month + 1;
	$next_year = $year;
}elseif($month==12){
	$prev_month = $month - 1;
	$prev_year = $year;
	$next_month = 1;
	$next_year = $year + 1;
}else{
	$prev_month = $month - 1;
	$prev_year = $year;
	$next_month = $month + 1;
	$next_year = $year;
}

if(strlen($month)==1){
	$month = "0".$month;
}

$date = date("F Y", mktime(0, 0, 0, $month+1, 1, $year));
$date_log = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
?>
<?php
perch_layout('header');
?>
<main class="full">
	<h1>Time Log</h1>
	<div class="section">
		<div>
			<section>
				<header class="header">
					<a class="button" href="/account/time-log/?m=<?= $prev_month ?>&y=<?= $prev_year ?>">&larr;</a>
					<p><?= $date ?></p>
					<a class="button" href="/account/time-log/?m=<?= $next_month ?>&y=<?= $next_year ?>">&rarr;</a>
				</header>
				<article>
					<?php
						echo staff_hours('monthly', $date_log);
					?>
				</article>
			</section>
		</div>
	</div>
</main>
<?php
perch_layout('footer');
?>