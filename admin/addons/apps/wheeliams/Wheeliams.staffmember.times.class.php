<?php

class Wheeliams_Staff_Member_Times extends PerchAPI_Factory
{
    protected $table     = 'wheeliams_staff_time';
	protected $pk        = 'wheeliams_staff_timeID';
	protected $singular_classname = 'Wheeliams_Staff_Member_Time';
	
	protected $default_sort_column = 'timeStamp';
	
	public $static_fields = array('wheeliams_staff_timeID,','staffID','timeType','timeStamp','timemotoData','wheeliams_staff_timeDynamicFields');	
	
	public function staff_status(){
		
		$Session = PerchMembers_Session::fetch();
		$memberID = $Session->get('memberID');
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE staffID="'.$memberID.'" ORDER BY timeStamp DESC LIMIT 1';
		$data = $this->db->get_row($sql);
		
		if($data){
			return $data['timeType'];
		}else{
			return 'clock out';
		}
		
	}
	
	public function clock_in(){
		
		$Session = PerchMembers_Session::fetch();
			
		date_default_timezone_set("Europe/London");
		
		$time = array();
	    $time['staffID'] = $Session->get('memberID');
	    $time['timeType'] = 'clock in';
	    $time['timeStamp'] = date('Y-m-d H:i:s');
	    $time['timemotoData'] = '';

	    $insert = $this->db->insert('perch3_wheeliams_staff_time', $time);
		
	}
	
	public function clock_out(){
		
		$Session = PerchMembers_Session::fetch();
			
		date_default_timezone_set("Europe/London");
		
		$time = array();
	    $time['staffID'] = $Session->get('memberID');
	    $time['timeType'] = 'clock out';
	    $time['timeStamp'] = date('Y-m-d H:i:s');
	    $time['timemotoData'] = '';

	    $insert = $this->db->insert('perch3_wheeliams_staff_time', $time);
	    
	    $today = date('Y-m-d');
	    
	    $sql = 'SELECT * FROM perch3_wheeliams_staff WHERE staffID="'.$Session->get('memberID').'" WHERE timeType="clock in" AND LEFT(timeStamp, 10)="'.$today.'"';
		$data = $this->db->get_row($sql);
		
		$clockIn = $data['timeStamp'];
	    $clockOut = $time['timeStamp'];
		
		$seconds = strtotime($clockOut) - strtotime($clockIn);
		if($seconds>18000){
			$sql = 'INSERT INTO perch3_wheeliams_staff_breaks (staffID, date, breakLength) VALUES ("'.$Session->get('memberID').'", "'.$today.'", "60")';
			$data = $this->db->execute($sql);	
		}
		
	}
	
	public function timemoto_log($name,$timeLoggedRounded,$attendanceStatus,$timemotoData){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff WHERE name="'.$name.'" ORDER BY name ASC LIMIT 1';
		$data = $this->db->get_row($sql);
		
		$timeParts = explode("T",$timeLoggedRounded);
		$date_h = $timeParts[0];
		$time_h = $timeParts[1];
		
		$time = array();
	    $time['staffID'] = $data['wheeliams_staffID'];
	    $time['timeType'] = $attendanceStatus;
	    $time['timeStamp'] = $date_h." ".$time_h;
	    $time['timemotoData'] = $timemotoData;

	    $insert = $this->db->insert('perch3_wheeliams_staff_time', $time);
	    
	    $names = explode(" ",$name);
	    $name = $names[0];
	    if($attendanceStatus=='clock in'){
		    $status = 'clocked in';
	    }else{
		    $status = 'clocked out';
	    }
	}
	
	public function forMonth($month,$staffID){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,7)="'.$month.'" AND staffID="'.$staffID.'" ORDER BY timeStamp ASC';
		$data = $this->db->get_rows($sql);
		
		return $data;
		
	}
	
	public function startTime($date,$staffID){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND staffID="'.$staffID.'" AND timeType="clock in" ORDER BY timeStamp ASC LIMIT 1';
		$data = $this->db->get_row($sql);
		
		if(!$data){
			$data['timeStamp']='';
		}
		
		return $data;
		
	}
	
	public function endTime($date,$staffID){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND staffID="'.$staffID.'" AND timeType="clock out" ORDER BY timeStamp DESC LIMIT 1';
		$data = $this->db->get_row($sql);
		
		if(!$data){
			$data['timeStamp']='';
		}
		
		return $data;
		
	}
	
	public function hoursWorked($staffID,$year,$month,$day){
		$day = str_pad($day, 2, "0", STR_PAD_LEFT);
		$date = "$year-$month-$day";
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND staffID="'.$staffID.'" AND timeType="clock in" ORDER BY timeStamp ASC LIMIT 1';
		$data = $this->db->get_row($sql);
		if($data){
			$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND staffID="'.$staffID.'" AND timeType="clock out" ORDER BY timeStamp DESC LIMIT 1';
			$data2 = $this->db->get_row($sql);
			if($data2){
				
				$hoursWorked = '00:00';
				$time1 = $data['timeStamp'];
				$time2 = $data2['timeStamp'];
				$diff = abs(strtotime($time1) - strtotime($time2));
				$tmins = $diff/60;
				$hours = floor($tmins/60);
				$mins = $tmins%60;
				if($mins==0){
					$mins = '00';
				}
				if(strlen($mins)==1){
					$mins = '0'.$mins;
				}
				$hoursWorked = "$hours:$mins";
			
				return $hoursWorked;
				
			}else{
				return;
			}
		}else{
			return;
		}
	}
	
	public function clockedIn(){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff ORDER BY name ASC';
		$data = $this->db->get_rows($sql);
		
		$date = date('Y-m-d H:i:s');
		
		$date = date("Y-m-d H:i:s", mktime(date('H')+1, date('i'), date('s'), date('m'), date('d'), date('Y')));
		
		$string = '';
		
		foreach($data as $staff){
			
			$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE timeStamp<="'.$date.'" AND staffID="'.$staff['wheeliams_staffID'].'" ORDER BY timeStamp DESC LIMIT 1';
			$data2 = $this->db->get_row($sql);
			if($data2['timeType']=='clock in'){
				$string .= "$staff[name],";
			}
		
		}
		
		$string = substr($string,0,-1);
		
		echo $string;
		
	}
	
	public function staff_log(){
		$Time = new Wheeliams_Staff_Member_Times();
		
		$Session = PerchMembers_Session::fetch();
		$staffID = $Session->get('memberID');
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE staffID="'.$staffID.'" ORDER BY timeStamp DESC LIMIT 50';
		$data = $this->db->get_rows($sql);
		
		$html = '<ul>';
		foreach($data as $row){
			$html .= '<li class="'.$row['timeType'].'"><a href="/account/edit-log/?id='.$row['wheeliams_staff_timeID'].'">'.strtoupper($row['timeType']).': '.$row['timeStamp'].'</a></li>';
		}
		$html .= '</ul>';
		return $html;
	}
	
	public function hours($memberID, $type, $start, $end, $return){
		
		$Staff = new Wheeliams_Staff_Members();
		$Time = new Wheeliams_Staff_Member_Times();
		$Breaks = new Wheeliams_Staff_Member_Breaks();
		$Holidays = new Wheeliams_Staff_Member_Holidays();
	
		$staff = $Staff->byMemberID($memberID);
		$staffID = $staff['wheeliams_staffID'];
		
		$sDates = explode("-", $start);
		$eDates = explode("-", $end);
		
		$startH = date("d/m/Y", mktime(0, 0, 0, $sDates[1], $sDates[2], $sDates[0]));
		$endH = date("d/m/Y", mktime(0, 0, 0, $eDates[1], $eDates[2], $eDates[0]));
		
		// if($startH<>$endH){
		// 	$html = "<p><strong>".ucwords($type).":</strong> $startH - $endH</p>";
		// }else{
		// 	$html = "<p><strong>".ucwords($type).":</strong> $startH</p>";
		// }
		
		$html .= "
			<table class='log'>
				<tr>
					<th>Day</th>
					<th>Start</th>
					<th>Finish</th>
					<th>Breaks</th>
					<th>Holiday</th>
					<th>Total</th>
				</tr>";
		
		$thisDate = $start;
		$periodTotalMinutes = 0;

		while($thisDate<=$end){
			
			$today = date('Y-m-d');
			
			$dDates = explode("-", $thisDate);
			$dateH = date("D jS", mktime(0, 0, 0, $dDates[1], $dDates[2], $dDates[0]));
			
			// SETUP THE VARIABLES FOR EACH DAY
			$workedMinutes = 0;
			$breakMinutes = 0;
			$holidayMinutes = 0;
			$totalMinutes = 0;
			
			// GET CLOCK IN AND OUT TIMES
			$clockIn = $Time->daily_clockin($memberID, $thisDate);
			$clockOut = $Time->daily_clockout($memberID, $thisDate);
			
			// GET WORKED MINUTES BASED ON CLOCK DATA
			$workedMinutes = $Time->daily_minutes($memberID, $thisDate);
			
			// CONVERT TO TIMESTAMP
			$workedHours = $Time->convertToHoursMins($workedMinutes, '%02d:%02d');
			
			// ADD WORKED MINUTES TO TOTAL
			$totalMinutes = $workedMinutes;
			
			// GET BREAK LENGTH FOR DATE
			$breakMinutes = $Breaks->breaks($memberID,$thisDate);
			
			// CONVERT BREAK MINUTES TO TIMESTAMP
			$breakHours = $Time->convertToHoursMins($breakMinutes, '%02d:%02d');
			
			// GET HOLIDAY DATA
			$holiday = $Holidays->getDate($staffID, $thisDate);
			
			// IF HOLIDAY HOLIDAY MINUTES
			if($holiday){
				if($staffID==20){
					if($holiday[0]['length']=='1.0'){
						$holidayHours = '10:00';
						$holidayMinutes = 600;
					}else{
						$holidayHours = '5:00';
						$holidayMinutes = 300;
					}
				}else{
					if($holiday[0]['length']=='1.0'){
						$holidayHours = '8:00';
						$holidayMinutes = 480;
					}else{
						$holidayHours = '4:00';
						$holidayMinutes = 240;
					}
				}
			}else{
				$holidayHours = '';
			}

			// ADD HOLIDAY MINUTES TO TOTAL
			$totalMinutes = $totalMinutes + $holidayMinutes - $breakMinutes;
			
			// ADD DAILY TOTAL TO PERIOD TOTAL
			$periodTotalMinutes = $periodTotalMinutes + $totalMinutes;
			
			// CONVERT TOTAL MINUTES TO TIMESTAMP
			$totalHours = $Time->convertToHoursMins($totalMinutes, '%02d:%02d');
			
			// CHECK FOR WEEKEND
			if(date('N', strtotime($thisDate)) >= 6){
				$weekend = 'weekend';
			}elseif(date('N', strtotime($thisDate)) == 5){
				$weekend = 'no-border';
			}else{
				$weekend = '';
			}
			
			$html .= "<tr class='$weekend'>
						<td>$dateH</td>
						<td>
							<input type='time' value='".$clockIn."' class='time' data-type='clockIn' data-date='".$thisDate."' data-staff='".$memberID."' />
						</td>
						<td>
							<input type='time' value='".$clockOut."' class='time' data-type='clockOut' data-date='".$thisDate."' data-staff='".$memberID."' "; if($thisDate==$today AND $clockOut=="00:00"){$html .= 'DISABLED';} $html .=" />
						</td>
						<td>
							<input type='number' step='15' class='time' data-type='break' data-date='".$thisDate."' data-staff='".$memberID."' value='".$breakMinutes."'/>
						</td>
						<td>$holidayHours</td>
						<td class='total' data-date='".$thisDate."' data-staff='".$memberID."'>$totalHours</td>
					</tr>";
			
			$thisDate = date("Y-m-d", mktime(0, 0, 0, $dDates[1], $dDates[2]+1, $dDates[0]));
		}
		
		// CONVERT PERIOD TOTAL MINUTES TO TIMESTAMP
		$periodTotalHours = $Time->convertToHoursMins($periodTotalMinutes, '%02d:%02d');
		
		$html .= '<tr>
					<td colspan="5">
						<strong>Total:</strong>
					</td>
					<td>
						'.$periodTotalHours.'
					</td>
				</tr>';
			
		$html .= "</table>";
		
		if($return == 'all'){
			return $html;
		}else{
			return $periodTotalHours;
		}
		
	}
	
	function convertToHoursMins($time, $format = '%02d:%02d') {
	    if ($time < 1) {
	        return;
	    }
	    $hours = floor($time / 60);
	    $minutes = ($time % 60);
	    return sprintf($format, $hours, $minutes);
	}
	
	public function daily_minutes($staffID, $date){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND staffID="'.$staffID.'" AND timeType="clock in" ORDER BY timeStamp ASC LIMIT 1';
		$data = $this->db->get_row($sql);
		
		$sql2 = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND staffID="'.$staffID.'" AND timeType="clock out" ORDER BY timeStamp DESC LIMIT 1';
		$data2 = $this->db->get_row($sql2);
	
		if($data){
			$start_date = new DateTime($data['timeStamp']);
			if($data2){
				$since_start = $start_date->diff(new DateTime($data2['timeStamp']));
			}else{
				$since_start = $start_date->diff(new DateTime(date('Y-m-d H:i:s')));
			}
			$minutes = $since_start->days * 24 * 60;
			$minutes += $since_start->h * 60;
			$minutes += $since_start->i;
			return $minutes;
		}else{
			return 0;
		}
		
	}
	
	function daily_clockin($staffID, $date){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND staffID="'.$staffID.'" AND timeType="clock in" ORDER BY timeStamp ASC LIMIT 1';
		$data = $this->db->get_row($sql);
		$clockIn = date('H:i', strtotime($data['timeStamp']));
		if($clockIn=="00:00"){
			return "";
		}else{
			return $clockIn;	
		}
		
	}
	
	function daily_clockout($staffID, $date){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND staffID="'.$staffID.'" AND timeType="clock out" ORDER BY timeStamp DESC LIMIT 1';
		$data = $this->db->get_row($sql);
		$clockOut = date('H:i', strtotime($data['timeStamp']));
		if($clockOut=="00:00"){
			return "";
		}else{
			return $clockOut;	
		}
		
	}
	
	public function staff_holiday_allowance(){
		
		$StaffMember = $WheeliamsStaff->find($staffID, true);
		
		return $StaffMember->holidayAllowance();
		
	}
	
	public function staff_log_owner($staffID, $id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE staffID="'.$staffID.'" AND wheeliams_staff_timeID="'.$id.'"';
		$data = $this->db->get_row($sql);
		
		if($data){
			return true;
		}else{
			return false;
		}
		
	}
	
	public function get_timestamp($id){
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE wheeliams_staff_timeID="'.$id.'"';
		$data = $this->db->get_row($sql);
		return $data;
		
	}
	
	public function update_timestamp($id, $timestamp){
		
		$timestamp = str_replace("T", " ", $timestamp);
		
		if(strlen($timestamp)==16){
			$timestamp = $timestamp.":00";
		}
		
		$sql = 'UPDATE perch3_wheeliams_staff_time SET timeStamp="'.$timestamp.'" WHERE wheeliams_staff_timeID="'.$id.'"';
		$data = $this->db->execute($sql);
		
	}
	
	public function delete_timestamp($id){
		
		$sql = 'DELETE FROM perch3_wheeliams_staff_time WHERE wheeliams_staff_timeID="'.$id.'"';
		$data = $this->db->execute($sql);
		
	}
	
	public function update_time($staffID, $type, $date, $time){
		
		$Time = new Wheeliams_Staff_Member_Times();
		$Breaks = new Wheeliams_Staff_Member_Breaks();
		$Holidays = new Wheeliams_Staff_Member_Holidays();
		
		if($type=='clockIn'){
			$t = 'clock in';
			$timestamp = "$date $time";
			
			if($time=='00:00'){
				$sql = 'DELETE FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND timeType="'.$t.'" AND staffID="'.$staffID.'"';
				$data = $this->db->execute($sql);
			}else{
				$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND timeType="'.$t.'" AND staffID="'.$staffID.'"';
				$data = $this->db->get_rows($sql);
				
				if(count($data)>0){
					$sql = 'UPDATE perch3_wheeliams_staff_time SET timeStamp="'.$timestamp.'" WHERE LEFT(timeStamp,10)="'.$date.'" AND timeType="'.$t.'" AND staffID="'.$staffID.'"';
					$data = $this->db->execute($sql);
				}else{
					$sql = 'INSERT INTO perch3_wheeliams_staff_time (timeStamp, timeType, staffID) VALUES ("'.$timestamp.'", "'.$t.'", "'.$staffID.'")';
					$data = $this->db->execute($sql);
				}
			}
			
			
		}elseif($type=='clockOut'){
			$t = 'clock out';
			$timestamp = "$date $time";
			
			if($time=='00:00'){
				$sql = 'DELETE FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND timeType="'.$t.'" AND staffID="'.$staffID.'"';
				$data = $this->db->execute($sql);
			}else{
				$sql = 'SELECT * FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND timeType="'.$t.'" AND staffID="'.$staffID.'"';
				$data = $this->db->get_rows($sql);
				
				if(count($data)>0){
					$sql = 'UPDATE perch3_wheeliams_staff_time SET timeStamp="'.$timestamp.'" WHERE LEFT(timeStamp,10)="'.$date.'" AND timeType="'.$t.'" AND staffID="'.$staffID.'"';
					$data = $this->db->execute($sql);
				}else{
					$sql = 'INSERT INTO perch3_wheeliams_staff_time (timeStamp, timeType, staffID) VALUES ("'.$timestamp.'", "'.$t.'", "'.$staffID.'")';
					$data = $this->db->execute($sql);
				}
			}
			
		}elseif($type=='break'){
			
			$break = $time;
			
			if($time=='0'){
				$sql = 'DELETE FROM perch3_wheeliams_staff_time WHERE LEFT(timeStamp,10)="'.$date.'" AND timeType="'.$t.'" AND staffID="'.$staffID.'"';
				$data = $this->db->execute($sql);
			}else{
				$sql = 'SELECT * FROM perch3_wheeliams_staff_breaks WHERE date="'.$date.'" AND staffID="'.$staffID.'"';
				$data = $this->db->get_rows($sql);
				
				if(count($data)>0){
					$sql = 'UPDATE perch3_wheeliams_staff_breaks SET breakLength="'.$break.'" WHERE date="'.$date.'" AND staffID="'.$staffID.'"';
					$data = $this->db->execute($sql);
				}else{
					$sql = 'INSERT INTO perch3_wheeliams_staff_breaks (date, breakLength, staffID) VALUES ("'.$date.'", "'.$break.'", "'.$staffID.'")';
					$data = $this->db->execute($sql);
				}
			}
		}
		
		$minutes = $Time->daily_minutes($staffID, $date);
		$breakMinutes = $Breaks->breaks($staffID, $date);
		
		$sql = 'SELECT * FROM perch3_wheeliams_staff WHERE memberID='.$staffID;
		$staffData = $this->db->get_row($sql);
		
		$holidays = $Holidays->getDate($staffData['wheeliams_staffID'], $date);
		if($holidays){
			if($holidays[0]['length']=='1.0'){
				if($staffData['wheeliams_staffID']==20){
					$holidayMinutes = 480;
				}else{
					$holidayMinutes = 600;
				}
			}
		}
		
		$h = floor((($minutes-$breakMinutes)+$holidayMinutes) / 60);
		$m = (($minutes-$breakMinutes)+$holidayMinutes) % 60;
		
		$time = sprintf('%02d:%02d', $h, $m);
		
		if($time !== "00:00"){
			echo $time;
		}else{
			echo "";
		}
		
	}
	
}