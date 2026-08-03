 <?php
/*
	 ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

    echo $HTML->side_panel_start();
    
    echo $HTML->side_panel_end();
    
    if($staffID){
    
	    echo $HTML->title_panel([
	    'heading' => $details['name'].' - Hours Worked',
	    'button'  => [
	            'text' => $Lang->get('Hours'),
	            'link' => $API->app_nav().'/staff/hours/add?id='.$_GET['id'],
	            'icon' => 'core/plus',
	        ],
	    ], $CurrentUser);
    
    }else{
	    
	    echo $HTML->title_panel([
	    'heading' => 'Hours Worked'
	    ], $CurrentUser);
	    
    }

    $Smartbar = new PerchSmartbar($CurrentUser, $HTML, $Lang);
	
	if($staffID){
		
		$Smartbar->add_item([
		    'active' => false,
		    'title' => 'Profile',
		    'link'  => $API->app_nav().'/staff/edit/?id='.$staffID,
		]);
	
		$Smartbar->add_item([
		    'active' => true,
		    'title' => 'Hours',
		    'link'  => $API->app_nav().'/staff/hours/?id='.$staffID,
		]);
		
		$Smartbar->add_item([
	    'active' => false,
	    'title' => 'Holidays',
	    'link'  => $API->app_nav().'/staff/holidays/?id='.$staffID,
		]);
		
		$Smartbar->add_item([
		    'active' => false,
		    'title' => 'Sick Days',
		    'link'  => $API->app_nav().'/staff/sick-days/?id='.$staffID,
		]);
	
	}else{
		
		$Smartbar->add_item([
		    'active' => false,
		    'title' => 'Staff',
		    'link'  => $API->app_nav().'/staff/',
		]);
		
		$Smartbar->add_item([
		    'active' => true,
		    'title' => 'Hours',
		    'link'  => $API->app_nav().'/staff/hours/',
		]);
		
		$Smartbar->add_item([
		    'active' => false,
		    'title' => 'Holidays',
		    'link'  => $API->app_nav().'/staff/holidays/',
		]);
		
	}
	
	echo $Smartbar->render();

    echo $HTML->main_panel_start(); 
    
    $date = $_GET['date'];
    
    if($date<>''){
	    $parts = explode("-",$date);
	    $month = $parts[1];
	    $year = $parts[0];
	    $monthHuman = date("F", mktime(0, 0, 0, $month, 1, $year));
	    $nextMonthHuman = date("F", mktime(0, 0, 0, $month+1, 1, $year));
	    $nextMonth = date("m", mktime(0, 0, 0, $month+1, 1, $year));
	    $nextMonthYear = date("Y", mktime(0, 0, 0, $month+1, 1, $year));
	    $nextYear = date("Y", mktime(0, 0, 0, $month+1, 1, $year));
    }else{
    	$month = date('m');
    	$year = date('Y');
    	$monthHuman = date('F');
    	$nextMonthHuman = date("F", mktime(0, 0, 0, $month+1, 1, $year));
    	$nextMonth = date("m", mktime(0, 0, 0, $month+1, 1, $year));
    	$nextMonthYear = date("Y", mktime(0, 0, 0, $month+1, 1, $year));
    	$nextYear = date("Y", mktime(0, 0, 0, $month+1, 1, $year));
    }

    if($nextMonth==13){
	    $nextMonth = 12;
	    $nextYear = $year+1;
    }
    
    $prevMonth = $month-1;
    $prevYear = $year;
    if($prevMonth==0){
	    $prevMonth=12;
	    $prevYear = $year-1;
    }
    
    $nextMonth = sprintf("%02d", $nextMonth);
    $prevMonth = sprintf("%02d", $prevMonth);
    
    if($staffID){
    
    ?>
	<h2><?php echo "$monthHuman $year" ?></h2>
	<p><a href="?id=<?php echo $_GET['id']; ?>&date=<?php echo "$prevYear-$prevMonth";?>">&larr; Previous Month</a> | <a href="?id=<?php echo $_GET['id']; ?>&date=<?php echo "$nextYear-$nextMonth";?>">Next Month &rarr;</a></p>
	
	<table class="d">
        <thead>
            <tr>
                <th class="first">Date</th>
                <th>Start Time</th> 
                <th>End Time</th> 
                <th>Hours Worked</th>
            </tr>
        </thead>
        <tbody>
	    <?php
		$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$today = date('Y-m-d');
		$i = 1;
		$totalHours = 0;
		$totalMinutes = 0;
		while($i<=$days){
			$humanDate = date("l jS F Y", mktime(0, 0, 0, $month, $i, $year));
			$queryDate = date("Y-m-d", mktime(0, 0, 0, $month, $i, $year));
			$start = $WheeliamsStaffTimes->startTime($queryDate,$details['memberID']);
			$end = $WheeliamsStaffTimes->endTime($queryDate,$details['memberID']);
			$hoursWorked = '00:00';
			if($start['timeStamp']<>'' AND $end['timeStamp']<>''){
				$time1 = $start['timeStamp'];
				$time2 = $end['timeStamp'];
				$diff = abs(strtotime($time1) - strtotime($time2));
				$tmins = $diff/60;
				$hours = floor($tmins/60);
				$mins = $tmins%60;
				if(strlen($mins)==1){
					$mins = "0".$mins;
				}
				$hoursWorked = "$hours:$mins";
				$totalHours = $totalHours+$hours;
				$totalMinutes = $totalMinutes+$mins;
			}
			
			$class = '';
			
			if($start['timeStamp']<>'' AND $end['timeStamp']=='' AND $queryDate<>$today){
				$hoursWorked = 'ERROR';
				$class = 'notification notification-warning';
			}
			
			if($queryDate==$today){
				$hoursWorked = 'TODAY';
				$class = 'notification notification-success';
			}
			
			if(strlen($totalMinutes)==1){
				$totalMinutes = "0".$totalMinutes;
			}
			
			echo "
			<tr class='$class'>
				<td>".$humanDate."</td>
				<td>";if($start['timeStamp']<>''){$parts = explode(" ", $start['timeStamp']); echo $parts[1];} echo "</td>
				<td>";if($end['timeStamp']<>''){$parts = explode(" ", $end['timeStamp']); echo $parts[1];} echo "</td>
				<td>$hoursWorked</td>
			</tr>";
			$i++;
		}  
		
		$totalMinutes_h = "00:00";
		if(convertToHoursMins($totalMinutes, '%02d:%02d')<>''){
			$totalMinutes_h = convertToHoursMins($totalMinutes, '%02d:%02d');
		}
		$parts = explode(":",$totalMinutes_h);
		$totalHours = $totalHours+$parts[0];
		$totalMinutes = $parts[1];
		
		echo "<tfoot>
				<tr><td><strong>Total Hours Worked</strong></td>
				<td></td>
				<td></td>
				<td><strong>$totalHours:$totalMinutes</strong></td>
		</tfoot>";
		?>    
        </tbody>
	</table>
	
	<h2>Full Log</h2>
	
    <table class="d">
        <thead>
            <tr>
                <th class="first">Time Type</th>
                <th>Time Stamp</th> 
                <th>View/Edit</th>
                <th class="action last">Delete</th>
            </tr>
        </thead>
        <tbody>
<?php
    foreach($times as $Time) {
?>
            <tr>
                <td><?php echo ucwords($Time['timeType']); ?></td>
                <td>
	                <?php 
		                $parts = explode(" ", $Time['timeStamp']);
		                $parts2 = explode("-", $parts[0]);
		                echo "$parts2[2]/$parts2[1]/$parts2[0] $parts[1]"; 
		            ?>
		        </td>
                <td><a class="button button-small action-info" href="<?php echo $HTML->encode($API->app_path()); ?>/staff/hours/edit/?staffID=<?= $_GET['id'] ?>&id=<?php echo $HTML->encode(urlencode($Time['wheeliams_staff_timeID'])); ?>"><?php echo 'View/Edit'; ?></a></td>
                <td><a href="<?php echo $HTML->encode($API->app_path()); ?>/staff/hours/delete/?staffID=<?= $_GET['id'] ?>&id=<?php echo $HTML->encode(urlencode($Time['wheeliams_staff_timeID'])); ?>" class="button button-small action-alert"><?php echo 'Delete'; ?></a></td>
            </tr>
<?php
	}
?>
	    </tbody>
    </table>

<?php 

	}else{

?>
	<h2>Timesheet For <?php echo "$monthHuman $year" ?> - <?php echo "$nextMonthHuman $nextMonthYear"; ?></h2>
	<p><a href="?id=<?php echo $_GET['id']; ?>&date=<?php echo "$prevYear-$prevMonth";?>">&larr; Previous Month</a> | <a href="?id=<?php echo $_GET['id']; ?>&date=<?php echo "$nextYear-$nextMonth";?>">Next Month &rarr;</a></p>
	<?php
		$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

		$now = time(); // or your date as well
		$period_start = strtotime("$year-$month-25");
		$period_end = strtotime("$nextMonthYear-$nextMonth-25");
		$datediff = $period_end - $period_start;
		
		$period_days = round($datediff / (60 * 60 * 24));
		
		$first_day = "$year-$month-25";
	?>
	<table class="d">
        <thead>
            <tr>
                <th class="first">Name</th>
                <?php
	                $i = 1;
	                $y = 25;
	                while($i<=$days){
		                $day = $y+$i-1;
		                if($day >= $days){
			                $y = 1 - $i;
		                }
		                echo "<th>$day</th>";
		                $i++;
	                }
	            ?>
	            <th>Total</th>
	            <th>Breaks</th>
	            <th>To Pay</th>
            </tr>
        </thead>
        <tbody>
<?php
    foreach($staff as $Staff) {

	    $dynamicFields = PerchUtil::json_safe_decode($Staff->wheeliams_staffDynamicFields(), true);

?>
            <tr>
                <td><?php echo $Staff->name(); ?></td>
                <?php
	                $i = 0;
	                $y = 25;
	                $totalHours = 0;
	                $totalMinutes = 0;
	                $workedHours = 0;
	                $workedMinutes = 0;
	                $holidayHours = 0;
	                $holidayMinutes = 0;
	                $totalBreaks = 0;
	                
	                while($i<$period_days){
		                
		                $date_parts = explode("-", $first_day);
		                
		                $thisDate = date("Y-m-d", mktime(0, 0, 0, $date_parts[1], $date_parts[2]+$i, $date_parts[0]));
		                $date_parts = explode("-", $thisDate);
		                $day = $date_parts[2];
		                $month = $date_parts[1];
		                $year = $date_parts[0];
		                
		                $hoursWorked = $WheeliamsStaffTimes->hoursWorked($Staff->memberID(),$year,$month,$day);
		                $parts = explode(":",$hoursWorked);
		                $hours = $parts[0];
		                $minutes = $parts[1];
		                
		                $workedHours = 0;
		                
		                $workedHours = (int)$workedHours+(int)$hours;
		                $workedMinutes = (int)$workedMinutes+(int)$minutes;
		                
		                $day = date("l", mktime(0, 0, 0, $month, (int)$day, $year));
		                $date = date("Y-m-d", mktime(0, 0, 0, $month, (int)$day, $year));
		                
/*
	this was to add breaks for all the previously worked days
		                if($workedHours>=5){
			                $WheeliamsStaffBreaks->add_staff_break($Staff->memberID(), $thisDate, '60');
		                }
*/

						$extras = '';
						
						$breaks = $WheeliamsStaffBreaks->breaks($Staff->memberID(),$thisDate);
						$totalBreaks = $totalBreaks + $breaks;
						
						$bHours = convertToHoursMins($breaks, '%01d:%02d');
						
						if($bHours){
							$hoursWorked .= "<br />($bHours)";
						}
						
						//SICK DAY
						$sick = $WheeliamsStaffSickdays->getDate($Staff->wheeliams_staffID(),$thisDate);
						if($sick){
							if($Staff->wheeliams_staffID()==20){
								$hoursWorked .= '<u>(S)</u>';
								//$hours = (int)$hours+10;
								//$holidayHours = (int)$holidayHours+10;
							}else{
								$hoursWorked .= '<u>(S)</u>';
								//$hours = (int)$hours+8;
								//$holidayHours = (int)$holidayHours+8;
							}
						}
						
						//HOLIDAY DAY
						$holiday = $WheeliamsStaffHolidays->getDate($Staff->wheeliams_staffID(),$thisDate);
						if($holiday){
							if($Staff->wheeliams_staffID()==20){
								if($holiday[0]['length']=='1.0'){
									$hoursWorked .= ' <i>10:00</i>';
									$hours = (int)$hours+8;
									$holidayHours = (int)$holidayHours+8;
								}else{
									$hoursWorked .= ' <i>5:00</i>';
									$hours = (int)$hours+4;
									$holidayHours = (int)$holidayHours+4;
								}
							}else{
								if($holiday[0]['length']=='1.0'){
									$hoursWorked .= ' <i>8:00</i>';
									$hours = (int)$hours+8;
									$holidayHours = (int)$holidayHours+8;
								}else{
									$hoursWorked .= ' <i>4:00</i>';
									$hours = (int)$hours+4;
									$holidayHours = (int)$holidayHours+4;
								}
							}
						}
		                
		                $totalHours = (int)$totalHours+(int)$hours;
		                $totalMinutes = (int)$totalMinutes+(int)$minutes;
		                
		                echo "<td class='$class'>$hoursWorked</td>";
		                
		                
		                $i++;
	                }
	                
	                
	                //CALC TOTAL
	                $totalMinutes_h = floor($totalMinutes/60);
	                if(convertToHoursMins($totalMinutes, '%02d:%02d')<>''){
						$totalMinutes_h = convertToHoursMins($totalMinutes, '%02d:%02d');
					}
					$parts = explode(":",$totalMinutes_h);
					$totalHours = $totalHours+$parts[0];
					$totalMinutes = $parts[1];
					
					if($totalMinutes==''){
						$totalMinutes='00';
					}
					
					$totalBreaks = convertToHoursMins($totalBreaks, '%01d:%02d');
					$breakParts = explode(":",$totalBreaks);
					
					$forWages_h = $totalHours-(int)$breakParts[0];
					$forWages_m = $totalMinutes-(int)$breakParts[1];
					
					//CALC WORKED
					$workedMinutes_h = floor($workedMinutes/60);
	                if(convertToHoursMins($workedMinutes, '%02d:%02d')<>''){
						$workedMinutes_h = convertToHoursMins($workedMinutes, '%02d:%02d');
					}
					$parts = explode(":",$workedMinutes_h);
					$workedHours = $workedHours+$parts[0];
					$workedMinutes = $parts[1];
					
					if($workedMinutes==''){
						$workedMinutes='00';
					}
					
					//CALC HOLIDAY
	                $holidayMinutes_h = floor($holidayMinutes/60);
	                if(convertToHoursMins($holidayMinutes, '%02d:%02d')<>''){
						$holidayMinutes_h = convertToHoursMins($holidayMinutes, '%02d:%02d');
					}
					$parts = explode(":",$holidayMinutes_h);
					$holidayHours = $holidayHours+$parts[0];
					$holidayMinutes = $parts[1];
					
					if($holidayMinutes==''){
						$holidayMinutes='00';
					}
					
					
	                
	                echo "<td>$totalHours:$totalMinutes</td>";
	                echo "<td>$totalBreaks</td>";
	                echo "<td>$forWages_h:$forWages_m</td>";
	            ?>
            </tr>
<?php
	}
?>
	    </tbody>
    </table>

    <p><i>Italic</i> = Holiday Hours</p>
    <p><u>Underlined</u> = Leave Day (C = Compassionate, S = Sick)</p>

<?php		
		
	}

	function convertToHoursMins($time, $format = '%02d:%02d') {
	    if ($time < 1) {
	        return;
	    }
	    $hours = floor($time / 60);
	    $minutes = ($time % 60);
	    return sprintf($format, $hours, $minutes);
	}

    echo $HTML->main_panel_end();