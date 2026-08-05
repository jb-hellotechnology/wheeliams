<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
wheeliams_require_level('admin');

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
?>
<?php
perch_layout('header');
?>
<main class="full">
	<h1>Wages Log</h1>
	<div class="header period">
		<a class="button" href="/staff/wages/?m=<?= $prev_month ?>&y=<?= $prev_year ?>#breaks">&larr;</a>
		<?= $date ?>
		<a class="button" href="/staff/wages/?m=<?= $next_month ?>&y=<?= $next_year ?>#breaks">&rarr;</a>
	</div>
	<div class="section-grid">
		<?= staff_wages($month, $year) ?>
	</div>
</main>
<?php
perch_layout('footer');
?>
<?php PerchUtil::output_debug(); ?>