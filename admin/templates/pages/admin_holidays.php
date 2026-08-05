<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
wheeliams_require_level('admin');
?>
<?php
perch_layout('header');
?>
<main class="full">
	<h1>Holidays</h1>
	<div class="section">
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
								echo "{ id: '".$holiday['wheeliams_staff_holidayID']."', resourceId: '".$holiday['staffID']."', start: \"".$holiday['date']."T00:00:00\", end: \"".$holiday['date']."T23:59:59\", title: '".$row['name']."',  color: '#ea571f', url: '/staff/holidays/edit?id=".$holiday['wheeliams_staff_holidayID']."' },\n";
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
	</div>
</main>
<?php
perch_layout('footer');
?>