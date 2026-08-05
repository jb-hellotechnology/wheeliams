<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
wheeliams_require_level('admin');
?>
<?php
perch_layout('header');
?>
<main class="full">
	<h1>Edit/Delete Holiday</h1>
	<p>Holiday date(s):</p>
	<ul>
	<?php
	$holidays = get_holiday($_GET['id']);
	foreach($holidays as $holiday){
		if($holiday['wheeliams_staff_holidayID']==$_GET['id']){
			$date = date('d/m/Y', strtotime($holiday['date']));
			echo "<li><strong>$date</strong></li>";
		}else{
			$date = date('d/m/Y', strtotime($holiday['date']));
			echo "<li>$date</li>";
		}
	}
	echo '</ul>';
	echo "<button type='submit' class='delete-holiday-day button small danger' data-holiday='".$_GET['id']."'>Delete Single Day</button> ";
	if(count($holiday)>1){
		echo "<button type='submit' class='delete-holiday button small danger' data-holiday='".$_GET['id']."'>Delete Entire Holiday</button>";
	}
	?>
</main>
<?php
perch_layout('footer');
?>