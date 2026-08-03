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
	<h1>Holidays</h1>
	<div class="section-grid">
		<div>
			<section>
				<header>
					<h2>Totals</h2>
				</header>
				<article class="flow">
					<p><strong>Allowance:</strong> <?= staff_holiday_allowance() ?> &bull; <strong>Remaining:</strong> <?= staff_holiday_remaining() ?></p>
					<h3>Days Taken This Year</h3>
					<?php staff_holiday_taken(); ?>
				</article>
			</section>
		</div>
		<div>
			<?php perch_content('Holiday Request Form'); ?>
			<section>
				<header>
					<h2>Your Pending Requests</h2>
				</header>
				<article class="flow">
					<?php staff_holiday_requests(); ?>
				</article>
			</section>
		</div>
	</div>
	<section>
		<header>
			<h2>Staff Holidays</h2>
		</header>
		<article class="flow">
			<div id='calendar'></div>
		<script>
	$(document).ready(function() {
	
		var todayDate = moment().startOf('day');
		var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
		var TODAY = todayDate.format('YYYY-MM-DD');
		var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');
	
	if($(window).width()>767){
	  var resourceW = 140;
	}else{
	  resourceW = 100;
	}
	
		$('#calendar').fullCalendar({
			schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
			resourceAreaWidth: resourceW,
			editable: false,
			height: 600,
			slotDuration: "24:00",
			scrollTime: '<?php echo date('Y-m-d'); ?>',
			header: {
				left: 'today prev,next',
				center: 'title',
				right: ''
			},
			defaultView: 'month',
			displayEventTime : false,
			resourceLabelText: 'Staff Member',
			resources: [
				<?php
					$rows = all_staff();
					foreach($rows as $row){
						echo "{ id: '".$row['wheeliams_staffID']."', title: '".$row['name']."'},";
					}
				?>	
			],
			events: [
				<?php
					$rows = all_staff();
					foreach($rows as $row){
						$holidays = all_holidays($row['wheeliams_staffID']);
						foreach($holidays as $holiday){						
								echo "{ id: '".$holiday['wheeliams_staff_holidayID']."', resourceId: '".$holiday['staffID']."', start: \"".$holiday['date']."T00:00:00\", end: \"".$holiday['date']."T23:59:59\", title: '".$row['name']."',  color: '#ea571f' },\n";
						}
					}
					
				?>
			]
		});
	
	});
	
	// readjust sizing after font load
	$(window).ready(function() {
		$('#calendar').fullCalendar('render');
	});
	</script>
	</article>
	</section>
</main>
<?php
perch_layout('footer');
?>