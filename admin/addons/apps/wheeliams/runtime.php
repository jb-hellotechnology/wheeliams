<?php


	// ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ALL);

	
	include('Wheeliams.class.php');
	include('Wheeliamss.class.php');
	include('Wheeliams.staffmember.class.php');
	include('Wheeliams.staffmembers.class.php');
	include('Wheeliams.staffmember.holiday.class.php');
	include('Wheeliams.staffmember.holidays.class.php');
	include('Wheeliams.staffmember.time.class.php');
	include('Wheeliams.staffmember.times.class.php');
	include('Wheeliams.staffmember.break.class.php');
	include('Wheeliams.staffmember.breaks.class.php');
	
	include('Wheeliams.settings.input.class.php');
	include('Wheeliams.settings.inputs.class.php');
	include('Wheeliams.settings.changelog.class.php');
	include('Wheeliams.settings.changelogs.class.php');
	
	include('Wheeliams.component.class.php');
	include('Wheeliams.components.class.php');
	include('Wheeliams.components.changelog.class.php');
	include('Wheeliams.components.changelogs.class.php');
	
	include('Wheeliams.supplier.class.php');
	include('Wheeliams.suppliers.class.php');
	include('Wheeliams.suppliers.changelog.class.php');
	include('Wheeliams.suppliers.changelogs.class.php');
	
	include('Wheeliams.bom.class.php');
	include('Wheeliams.boms.class.php');
	
	// FORM HANDLERS
	
	function wheeliams_form($template, $return=false)
	{
		$API  = new PerchAPI(1.0, 'wheeliams');
		
		$WheeliamsStaff = new Wheeliams_Staff_Members($API);
		$WheeliamsSettingsInput = new Wheeliams_Settings_Inputs($API);
		$WheeliamsComponents = new Wheeliams_Components($API);
		$WheeliamsSuppliers = new Wheeliams_Suppliers($API);
		$WheeliamsBoms = new Wheeliams_Boms($API);
		
		$Template = $API->get('Template');
		$Template->set(PerchUtil::file_path('wheeliams/forms/'.$template), 'forms');
		
		$Session = PerchMembers_Session::fetch();
			
		$data['memberID'] = $Session->get('memberID');
		
		if($template == 'staff_profile_form.html'){
			$data = $WheeliamsStaff->staff($_GET['id']);
		}
		if(substr($template,0,9)=='settings_'){
			$data = $WheeliamsSettingsInput->setting($_GET['id']);
			$data['type'] = $_GET['type'];
			foreach(json_decode($data['dynamicFields'],true) AS $key=>$value){
				$data[$key] = $value;
			}
		}
		if(substr($template,0,10)=='component_'){
			$data = $WheeliamsComponents->component($_GET['id']);
			$dynamicFields = '<dl>';
			$dynamicFields .= '<dt>Type</dt><dd>'.$_GET['type'].'</dd>';
			foreach(json_decode($data['dynamicFields'],true) AS $key=>$value){
				if(is_array($value)){
					$data[$key] = $value;
				}else{
					$data[$key] = $value;
				}
				if($data[$key]){
					$dynamicFields .= '<dt>'.$key.'</dt><dd>'.$value.'</dd>';
				}
			}
			$dynamicFields .= '</dl>';
			
			$data['dynamicFields'] = $dynamicFields;
			
			$data['deletable'] = false;
			// check for us in BOMs
			$boms = $WheeliamsBoms->byPartCode($_GET['id']);
			if(!$boms){
				$data['deletable'] = true;
			}else{
				$data['usedInBoms'] = '<ul>';
				foreach($boms as $bom){
					$componentData = $WheeliamsComponents->component($bom['id']);
					$bomData = $WheeliamsBoms->bom($bom['id']);
					$data['usedInBoms'] .= '<li><a href="/boms/?type='.$bomData[0]['type'].'&edit=1&id='.$bom['id'].'">'.$componentData['partCode'].'</a></li>';
				}
				$data['usedInBoms'] .= '</ul>';
			}
			
			$data['values-component_type'] = $WheeliamsSettingsInput->setting_values('component-type-list', 'code', 'description');
			
			$data['values-part_no_prefix'] = $WheeliamsSettingsInput->setting_values('part-no-prefix', 'prefix', 'description');
			$data['values-part_issue_code'] = $WheeliamsSettingsInput->setting_values('part-issue-code', 'code', '');
			$data['values-part_status_code'] = $WheeliamsSettingsInput->setting_values('part-status-code', 'code', '');
			$data['values-product_group_description'] = $WheeliamsSettingsInput->setting_values('product-group-description', 'description', '');
			$data['values-vehicle'] = $WheeliamsSettingsInput->setting_values('vehicle-list', 'description', '');
			$data['values-process_type'] = $WheeliamsSettingsInput->setting_values('process-type', 'process_type', '');
			$data['values-generic_material'] = $WheeliamsSettingsInput->setting_values('generic-material', 'material', '');
			$data['values-unit_of_measure'] = $WheeliamsSettingsInput->setting_values('uom', 'unit', '');
			
			$data['values-fastener_type'] = $WheeliamsSettingsInput->setting_values('fastener-type', 'fastener', '');
			$data['values-head_type'] = $WheeliamsSettingsInput->setting_values('head-type', 'description', '');

			$data['values-fastener_material'] = $WheeliamsSettingsInput->setting_values('fastener-material', 'material', '');
			$data['values-fastener_finish'] = $WheeliamsSettingsInput->setting_values('fastener-finish', 'finish', '');
			$data['values-thread_size'] = $WheeliamsSettingsInput->setting_values('thread-size', 'size', 'standard');

			$data['values-fastener_grade'] = $WheeliamsSettingsInput->setting_values('fastener-grade', 'grade', '');
			
			$data['values-members'] = $WheeliamsSettingsInput->members_list();
			
			$data['type'] = $_GET['type'];
			if($_GET['type']=='manufactured'){
				$data['type_title'] = 'Manufactured & Purchased Components';
			}else{
				$data['type_title'] = ucwords($_GET['type']);
			}
			
			$data['cancel_link'] = "/components/?type=".$_GET['type'];
		}
		if($template == 'supplier.html'){
			$data = $WheeliamsSuppliers->supplier($_GET['id']);
			foreach(json_decode($data['dynamicFields'],true) AS $key=>$value){
				$data[$key] = $value;
			}
		}
		if($template == 'component_supplier_add.html'){
			$data = $WheeliamsSuppliers->existing();
			$suppliers = "Please Select";
			foreach($data AS $supplier){
				$suppliers .= ",".$supplier['name']."|".$supplier['wheeliams_supplierID'];
			}
			$data['suppliers'] = $suppliers;
		}
		if(substr($template,0,4)=='bom_'){
			$data['values-type'] = $_GET['type'];
			$data['itemID'] = $_GET['id'];
			
			$materials = $WheeliamsComponents->existing('raw-materials');
			$data['values-materials'] = ',';
			foreach($materials as $material){
				$json = json_decode($material['dynamicFields'], true);
				$data['values-materials'] .= $material['partCode'].' ('.$json['unit_of_measure'].')|'.$material['perch3_wheeliams_componentID'].',';
			}
			$data['values-materials'] = substr($data['values-materials'], 0, -1);
			
			$fasteners = $WheeliamsComponents->existing('fasteners');
			$data['values-fasteners'] = ',';
			foreach($fasteners as $fastener){
				$data['values-fasteners'] .= $fastener['partCode'].'|'.$fastener['perch3_wheeliams_componentID'].',';
			}
			$data['values-fasteners'] = substr($data['values-fasteners'], 0, -1);
			
			$components = $WheeliamsComponents->existing('manufactured');
			$data['values-components'] = ',';
			foreach($components as $component){
				$componentJson = json_decode($component['dynamicFields'], true);
				$data['values-components'] .= $component['partCode'].' ('.$componentJson['part_description'].')|'.$component['perch3_wheeliams_componentID'].',';
			}
			$data['values-components'] = substr($data['values-components'], 0, -1);
			
			$data['type'] = $_GET['type'];
			
		}
		if($template == 'supplier_contact_add.html' || $template == 'supplier_contact_edit.html' ){
			$types = $WheeliamsSettingsInput->setting_values('contact-types', 'contact-type', '');
			if($_GET['edit']==1 AND !empty($_GET['contact'])){
				$data = $WheeliamsSuppliers->contact($_GET['contact']);
				$data['wheeliams_supplierID'] = $_GET['supplier'];
			}else{
				$data['wheeliams_supplierID'] = $_GET['id'];
			}
			$data['contact_types'] = "Please Select|,".$types;
			
		}
		if($template == 'supplier_contact_delete.html'){
			$contact = $WheeliamsSuppliers->contact($_GET['contact']);
			$data = $contact;
			$data['supplierID'] = $_GET['supplier'];
		}
		if($template == 'bom_edit_row.html' || $template == 'bom_delete_row.html'){
			$data = $WheeliamsBoms->bomComponent($_GET['component']);
			$componentData = $WheeliamsComponents->component($data['partCode']);
			$jsonData = json_decode($componentData['dynamicFields'], true);
			$component = $WheeliamsComponents->component($_GET['id']);
			$partData = $WheeliamsComponents->byPartCode($component['partCode']);
			$data['bomName'] = $component['partCode'];
			$data['uom'] = $jsonData['unit_of_measure'];
			$data['bom'] = $_GET['id'];
			$data['type'] = $_GET['type'];
			$data['partDescription'] = $jsonData['part_description'];
			$data['partCode'] = $componentData['partCode'];
		}
		if($template == 'bom_notes.html'){
			$data = $WheeliamsBoms->notes($_GET['type'],$_GET['id']);
			$data['bomID'] = $_GET['id'];
			$data['type'] = $_GET['type'];
		}
		
		
		$html = $Template->render($data);
		$html = $Template->apply_runtime_post_processing($html, $data);
		
		if ($return) return $html;
		echo $html;
		
	}
	
	function wheeliams_form_handler($SubmittedForm) {
		if($SubmittedForm->validate()) {
			switch($SubmittedForm->formID) {
				case 'holiday_request':
					if($SubmittedForm->data['end']<$SubmittedForm->data['start']) {
						$SubmittedForm->throw_error('end');
					}else{
						$Holidays = new Wheeliams_Staff_Member_Holidays($API);
						$WheeliamsStaff = new Wheeliams_Staff_Members($API);
						$Session = PerchMembers_Session::fetch();
						$staff = $WheeliamsStaff->byMemberID($Session->get('memberID'));
						$Holidays->request_holiday($SubmittedForm, $staff['wheeliams_staffID']);
					}
				break;
				
				case 'staff_profile':
					$WheeliamsStaff = new Wheeliams_Staff_Members($API);
					$staff = $WheeliamsStaff->find($SubmittedForm->data['wheeliams_staffID']);
					$staff->update($SubmittedForm->data);
				break;
				
				case 'input_settings':
					$WheeliamsStaff = new Wheeliams_Staff_Members($API);
					$WheeliamsSettingsInput = new Wheeliams_Settings_Inputs($API);
					$WheeliamsChangelog = new Wheeliams_Settings_Changelogs($API);
					$Session = PerchMembers_Session::fetch();
					$data = $SubmittedForm->data;
					
					// Pull out the fixed columns
					$id   = $_GET['id'] ?? null;
					$type = $data['type'] ?? null;
					
					// Fields to convert to uppercase
					$uppercaseFields = ['prefix', 'description', 'code'];
					
					// Apply uppercase conversion to specified fields
					foreach ($uppercaseFields as $field) {
						if (isset($data[$field])) {
							$data[$field] = strtoupper($data[$field]);
						}
					}

					
					// Everything else goes into dynamicFields
					$excluded = ['wheeliams_settings_inputID', 'type'];
					$dynamicFields = array_diff_key($data, array_flip($excluded));
					
					// Build the record to save
					$record = [
						'type'            => $type,
						'userID' 		  => $Session->get('memberID'),
						'dynamicFields'   => json_encode($dynamicFields)
					];

					if ($id) {
						$setting = $WheeliamsSettingsInput->find($id);
						
						// Capture old values before update
						$oldValues = $setting->dynamicFields();
						
						$setting->update($record);
						
						// Log the change
						$WheeliamsChangelog->create([
							'settingID' => $id,
							'type'      => $type,
							'action'    => 'update',
							'oldValues' => $oldValues,
							'newValues' => json_encode($dynamicFields),
							'userID'    => $Session->get('memberID'),
							'timestamp' => date('Y-m-d H:i:s'),
						]);
					} else {
						$result = $WheeliamsSettingsInput->create($record);

						// Log the creation
						$WheeliamsChangelog->create([
							'settingID' => $result->perch3_wheeliams_settings_inputID(),
							'type'      => $type,
							'action'    => 'create',
							'oldValues' => null,
							'newValues' => json_encode($dynamicFields),
							'userID'    => $Session->get('memberID'),
							'timestamp' => date('Y-m-d H:i:s'),
						]);
					}
				break;
				
				case 'delete_input_setting':
					$WheeliamsSettingsInput = new Wheeliams_Settings_Inputs($API);

					$result = $WheeliamsSettingsInput->find($_GET['id']);
					$result->delete();
				break;
				
				case 'component':
					$WheeliamsStaff = new Wheeliams_Staff_Members($API);
					$WheeliamsComponents = new Wheeliams_Components($API);
					$WheeliamsChangelog = new Wheeliams_Components_Changelogs($API);
					$Session = PerchMembers_Session::fetch();
					$data = $SubmittedForm->data;
					
					// Pull out the fixed columns
					$id   = $_GET['id'] ?? null;
					$type = $data['type'] ?? null;
					
					// Everything else goes into dynamicFields
					$excluded = ['wheeliams_settings_inputID', 'type'];
					$dynamicFields = array_diff_key($data, array_flip($excluded));
									
					if($type=='manufactured'){
						$number = str_pad($id, 4, "0", STR_PAD_LEFT);
						$partCode = strtoupper($data['part_no_prefix'].$data['component_type'].'-'.$number.'-'.$data['part_issue_code']);
					}elseif($type=='fasteners'){
						
						$partCode = '';
						$partCode .= $data['thread_size'];
						if($data['length']){
							$partCode .= 'x'.$data['length'].'-';
						}
						if($data['head_type']){
							$partCode .= $data['head_type'].'-';
						}
						if(!$data['length'] && !$data['head_type']){
							$partCode .= '-';
						}
						if($data['fastener_grade']){
							$partCode .= $data['fastener_grade'].'-';
						}
						if($data['fastener_finish']){
							$partCode .= $data['fastener_finish'];
						}
					}elseif($type=='raw-materials'){
						$materials = explode("-", $data['generic-material']);
						if(count($materials)>0){
							$generic_material = $materials[1];
						}else{
							$generic_material = $data['generic-material'];
						}
						$partCode = strtoupper($data['section'].'-'.$data['size'].'-'.$generic_material.'-'.$data['stock-length']);
					}
					
					// Build the record to save
					$record = [
						'type'            => $type,
						'userID' 		  => $Session->get('memberID'),
						'dynamicFields'   => json_encode($dynamicFields),
						'partCode'		  => $partCode
					];
				
					if ($id) {
						$component = $WheeliamsComponents->find($id);
						
						// Capture old values before update
						$oldValues = $component->dynamicFields();
						
						$component->update($record);
						
						// Log the change
						$WheeliamsChangelog->create([
							'componentID' => $id,
							'type'      => $type,
							'action'    => 'update',
							'oldValues' => $oldValues,
							'newValues' => json_encode($dynamicFields),
							'userID'    => $Session->get('memberID'),
							'timestamp' => date('Y-m-d H:i:s'),
						]);
					} else {
						$result = $WheeliamsComponents->create($record);
						$component = $WheeliamsComponents->find($result->perch3_wheeliams_componentID());
						
						if($type=='manufactured'){
							$number = str_pad($result->perch3_wheeliams_componentID(), 4, "0", STR_PAD_LEFT);
							$partCode = strtoupper($data['part_no_prefix'].$data['component_type'].'-'.$number.'-'.$data['part_issue_code']);
							$record = [
								'partCode' => $partCode
							];
							$component->update($record);
						}
						
						// Log the creation
						$WheeliamsChangelog->create([
							'componentID' => $result->perch3_wheeliams_componentID(),
							'type'      => $type,
							'action'    => 'create',
							'oldValues' => null,
							'newValues' => json_encode($dynamicFields),
							'userID'    => $Session->get('memberID'),
							'timestamp' => date('Y-m-d H:i:s'),
						]);
					}
				break;
				
				case 'delete_component':
					$WheeliamsComponents = new Wheeliams_Components($API);
				
					$result = $WheeliamsComponents->find($_GET['id']);
					$result->delete();
				break;
				
				case 'duplicate_component':
					$WheeliamsComponents = new Wheeliams_Components($API);

					$db = PerchDB::fetch();
					$row = $db->get_row('SELECT * FROM perch3_wheeliams_components
										 WHERE perch3_wheeliams_componentID='.(int)$_GET['id']);
					
					if ($row) {
						unset($row['perch3_wheeliams_componentID']);
						$newID = $db->insert('perch3_wheeliams_components', $row);
						PerchUtil::redirect('/components/?type='.urlencode($_GET['type']).'&edit=1&id='.$newID);
					}
				break;
	
				case 'register':
				// submission came from <perch:form id="register" app="company_app"></perch:form>
				break;
				
				case 'supplier':
					$WheeliamsStaff = new Wheeliams_Staff_Members($API);
					$WheeliamsSuppliers = new Wheeliams_Suppliers($API);
					$WheeliamsChangelog = new Wheeliams_Suppliers_Changelogs($API);
					$Session = PerchMembers_Session::fetch();
					$data = $SubmittedForm->data;
					
					// Pull out the fixed columns
					$id   = $_GET['id'] ?? null;
					$name = $data['name'] ?? null;
					
					// Everything else goes into dynamicFields
					$excluded = ['wheeliams_supplierID', 'name'];
					$dynamicFields = array_diff_key($data, array_flip($excluded));
					
					// Build the record to save
					$record = [
						'name'            => $name,
						'dynamicFields'   => json_encode($dynamicFields)
					];
					
					if ($id) {
						$supplier = $WheeliamsSuppliers->find($id);
						
						// Capture old values before update
						$oldValues = $supplier->dynamicFields();
						
						$supplier->update($record);
						
						// Log the change
						$WheeliamsChangelog->create([
							'supplierID' => $id,
							'action'    => 'update',
							'oldValues' => $oldValues,
							'newValues' => json_encode($dynamicFields),
							'userID'    => $Session->get('memberID'),
							'timestamp' => date('Y-m-d H:i:s'),
						]);
					} else {
						$result = $WheeliamsSuppliers->create($record);
						
						//Log the creation
						$WheeliamsChangelog->create([
							'supplierID' => $result->wheeliams_supplierID(),
							'action'    => 'create',
							'oldValues' => null,
							'newValues' => json_encode($dynamicFields),
							'userID'    => $Session->get('memberID'),
							'timestamp' => date('Y-m-d H:i:s'),
						]);
					}
				break;
				
				case 'delete_supplier':
					$WheeliamsSuppliers = new Wheeliams_Suppliers($API);
				
					$result = $WheeliamsSuppliers->find($_GET['id']);
					$result->delete();
				break;
				
				case 'supplier_contact_add':
					$WheeliamsSuppliers = new Wheeliams_Suppliers($API);
					$WheeliamsSuppliers->assignContact($_GET['id'],$SubmittedForm->data);
				break;
				
				case 'supplier_contact_edit':
					$WheeliamsSuppliers = new Wheeliams_Suppliers($API);
					$WheeliamsSuppliers->updateContact($_GET['id'],$SubmittedForm->data);
				break;
				
				case 'supplier_contact_delete':
					$WheeliamsSuppliers = new Wheeliams_Suppliers($API);
					$WheeliamsSuppliers->deleteContact($_GET['contact']);
				break;
				
				case 'component_supplier_add':
					$WheeliamsComponents = new Wheeliams_Components($API);
					$WheeliamsComponents->assignSupplier($_GET['id'],$SubmittedForm->data['wheeliams_supplierID']);
				break;
				
				case 'component_supplier_price':
					$WheeliamsComponents = new Wheeliams_Components($API);
					$WheeliamsComponents->componentPrice($_GET['id'],$SubmittedForm->data['wheeliams_supplierID'],$SubmittedForm->data['price']);
				break;
				
				case 'component_supplier_remove':
					$WheeliamsComponents = new Wheeliams_Components($API);
					$WheeliamsComponents->removeSupplier($_GET['id'],$SubmittedForm->data['wheeliams_supplierID']);
				break;
				
				case 'component_supplier_current':
					$WheeliamsComponents = new Wheeliams_Components($API);
					$WheeliamsComponents->setCurrentSupplier($_GET['id'],$SubmittedForm->data['wheeliams_supplierID']);
				break;
				
				case 'bom_manufactured_add':
					$Session = PerchMembers_Session::fetch();
					$WheeliamsBoms = new Wheeliams_Boms($API);
					if($SubmittedForm->data['quantity_add_rm']){
						$quantity = $SubmittedForm->data['quantity_add_rm'];
					}elseif($SubmittedForm->data['quantity_c']){
						$quantity = $SubmittedForm->data['quantity_c'];
					}else{
						$quantity = $SubmittedForm->data['quantity_f'];
					}
					$WheeliamsBoms->addToBom($SubmittedForm->data['type'],$_GET['id'],$SubmittedForm->data['component'],$quantity,$Session->get('memberID'));
					PerchUtil::redirect($_SERVER['REQUEST_URI']);
				break;
				
				case 'bom_edit':
					$Session = PerchMembers_Session::fetch();
					$WheeliamsBoms = new Wheeliams_Boms($API);
					$WheeliamsBoms->updateBom($SubmittedForm->data['wheeliams_bomID'],$SubmittedForm->data['quantity'],$Session->get('memberID'));
				break;
				
				case 'bom_delete':
					$Session = PerchMembers_Session::fetch();
					$WheeliamsBoms = new Wheeliams_Boms($API);
					$bom = $WheeliamsBoms->find($SubmittedForm->data['component']);
					$bom->delete();
					PerchUtil::redirect($_SERVER['REQUEST_URI']);
				break;
				
				case 'bom_notes':
					$WheeliamsBoms = new Wheeliams_Boms($API);
					$WheeliamsBoms->saveNotes($SubmittedForm->data);
				break;
			}
			
			// access logged errors
			$Perch = Perch::fetch();
			$form_errors = $Perch->get_form_errors($SubmittedForm->formID);
		}
	}
	
	function all_staff(){
		
		$WheeliamsStaff = new Wheeliams_Staff_Members($API);
		
		$staff = $WheeliamsStaff->all_staff();
		return $staff;	
		
	}
	
	function list_staff(){
		
		$WheeliamsStaff = new Wheeliams_Staff_Members($API);
		
		$staffs = $WheeliamsStaff->all_staff();
		
		if($staffs){
			echo '<div class="table-container"><table>';
			echo '<tr class="first-line"><th>Name</th><th>Email</th><th>Manage</th></tr>';
			foreach($staffs as $staff){
				echo "<tr><td>".$staff['name']."</td><td>".$staff['email']."</td><td><a class='button small' href='/staff/staff-members?id=".$staff['wheeliams_staffID']."'>Manage</a></td></tr>";
			}
			echo "</table>";
		}else{
			echo '<p><em>None</em></p>';
		}	
		echo '</div>';
		
	}
	
	function all_holidays($staffID){
		
		$Holidays = new Wheeliams_Staff_Member_Holidays($API);
		
		$staff = $Holidays->all_holidays($staffID);
		return $staff;	
		
	}
	
	function staff_status(){
		
		$Time = new Wheeliams_Staff_Member_Times();
		
		$status = $Time->staff_status();
		
		if($status=='clock in'){
			return true;
		}else{
			return false;
		}
		
	}
	
	function staff_break($date, $breakLength){
		
		$Break = new Wheeliams_Staff_Member_Breaks();
		
		$Break->staff_break($date, $breakLength);
		
	}
	
	function get_break_length($date){
		
		$Break = new Wheeliams_Staff_Member_Breaks();
		
		$length = $Break->get_break_length($date);
		
		return $length;
		
	}
	
	function clock_in(){
		
		$Time = new Wheeliams_Staff_Member_Times();
		
		$Time->clock_in();
		
	}
	
	function clock_out(){
		
		$Time = new Wheeliams_Staff_Member_Times();
		
		$Time->clock_out();
		
	}
	
	function staff_hours($type, $date){
		
		$Time = new Wheeliams_Staff_Member_Times();
		
		$Session = PerchMembers_Session::fetch();
		$memberID = $Session->get('memberID');
		
		if($type=='daily'){
			$start = date('Y-m-d');
			$end = date('Y-m-d');
		}elseif($type=='weekly'){
			$start = $date->format('Y-m-d');
			$end = (clone $date)->modify('next sunday')->format('Y-m-d');
		}else{
			$dates = explode("-", $date);
			$year = $dates[0];
			$month = $dates[1];
			$day = $dates[0];
			
			$start = "$year-$month-25";
			$end = date("Y-m-d", mktime(0, 0, 0, $month+1, 24, $year));
		}
		
		$hours = $Time->hours($memberID, $type, $start, $end, 'all');
		return $hours;
		
	}
	
	function staff_wages($month, $year){
		
		$WheeliamsStaff = new Wheeliams_Staff_Members($API);
		$Time = new Wheeliams_Staff_Member_Times();
		
		$staff = $WheeliamsStaff->all_staff();

		$day = date('j');
		$start = "$year-$month-25";
		$end = date("Y-m-d", mktime(0, 0, 0, $month+1, 24, $year));
		
		$type = 'wage period';
		
		$html = '';
		
		foreach($staff as $staffMember){
			if($staffMember['name']<>'Jack Barber' AND $staffMember['name']<>'William Smith'){
				
				$totalHours = $Time->hours($staffMember['memberID'], $type, $start, $end, 'total');
				
				$html .= "<div>
							<section>
								<header>
									<h2 class='full'>".$staffMember['name']." <span>$totalHours</span></h2>
								</header>
								<article class='collapse'>";
								
								$hours = $Time->hours($staffMember['memberID'], $type, $start, $end, 'all');
								$html .= $hours;
									
					$html .= "</article>
					</section>
				</div>";
			}
		}
		
		return $html;
		
	}
	
	function staff_log($type){
		
		$Time = new Wheeliams_Staff_Member_Times();
		$log = $Time->staff_log();
		echo $log;
		
	}
	
	function staff_holiday_allowance(){
		
		$Time = new Wheeliams_Staff_Member_Times();
		$WheeliamsStaff = new Wheeliams_Staff_Members($API);
		
		$Session = PerchMembers_Session::fetch();
		$staffID = $Session->get('memberID');
		
		$StaffMember = $WheeliamsStaff->byMemberID($staffID);
		
		echo $StaffMember['holidayAllowance'];
		
	}
	
	function staff_holiday_remaining(){
		
		$Time = new Wheeliams_Staff_Member_Times();
		$Holidays = new Wheeliams_Staff_Member_Holidays($API);
		$WheeliamsStaff = new Wheeliams_Staff_Members($API);
		
		$Session = PerchMembers_Session::fetch();
		$staffID = $Session->get('memberID');
		
		$StaffMember = $WheeliamsStaff->byMemberID($staffID);
		$year = date('Y');
		$month = date('n');
		if($month<=3){
			$year--;
		}
		$start = $year.'-04-01';
		
		$year = $year+1;
		$end = $year.'-03-31';

		$taken = $Holidays->getListForYear($StaffMember['wheeliams_staffID'],$start,$end);
		
		$i = 0;
		foreach($taken as $holiday){
			$i = $i + $holiday['length'];
		}
		
		echo $StaffMember['holidayAllowance'] - $i;
		
	}
	
	function staff_holiday_taken(){
		
		$Time = new Wheeliams_Staff_Member_Times();
		$Holidays = new Wheeliams_Staff_Member_Holidays($API);
		$WheeliamsStaff = new Wheeliams_Staff_Members($API);
		
		$Session = PerchMembers_Session::fetch();
		$staffID = $Session->get('memberID');
		
		$StaffMember = $WheeliamsStaff->byMemberID($staffID);
		$year = date('Y');
		$month = date('n');
		if($month<=3){
			$year--;
		}
		$start = $year.'-04-01';
	
		$year = $year+1;
		$end = $year.'-03-31';

		$taken = $Holidays->getListForYear($StaffMember['wheeliams_staffID'],$start,$end);
		
		$holidays = false;
		
		echo "<ul>";
		foreach($taken as $holiday){
			$dates = explode("-", $holiday['date']);
			$date = "$dates[2]/$dates[1]/$dates[0]";
			echo "<li>$date ($holiday[length])</li>";
			$holidays == true;
		}
		echo "</ul>";
		
		if(!$holidays){
			echo '<p><em>None</em></p>';
		}
		
	}
	
	function check_log_owner($id){

		$Session = PerchMembers_Session::fetch();
		$staffID = $Session->get('memberID');
		
		$Time = new Wheeliams_Staff_Member_Times();
		$owner = $Time->staff_log_owner($staffID, $id);
		
		if($owner){
			return true;
		}else{
			return false;
		}
	}
	
	function get_timestamp($id){

		$Time = new Wheeliams_Staff_Member_Times();
		$timestamp = $Time->get_timestamp($id);
		return $timestamp['timeStamp'];
		
	}
	
	function update_timestamp($id, $timestamp){
		
		$Time = new Wheeliams_Staff_Member_Times();
		$Time->update_timestamp($id, $timestamp);
		
	}
	
	function delete_timestamp($id){
		
		$Time = new Wheeliams_Staff_Member_Times();
		$Time->delete_timestamp($id);
		
	}
	
	function staff_holiday_requests(){
		
		$Holidays = new Wheeliams_Staff_Member_Holidays($API);
		$Session = PerchMembers_Session::fetch();
		$staffID = $Session->get('memberID');
		
		$WheeliamsStaff = new Wheeliams_Staff_Members($API);
		$staff = $WheeliamsStaff->byMemberID($Session->get('memberID'));	
		$requests = $Holidays->staff_holiday_requests($staff['memberID']);

		if($requests){
			echo "<ul class='items'>";
			foreach($requests as $request){
				$dates = explode("-", $request['start']);
				$start = "$dates[2]/$dates[1]/$dates[0]";
				$dates = explode("-", $request['end']);
				$end = "$dates[2]/$dates[1]/$dates[0]";
				echo "<li>$start -> $end <button type='submit' class='delete-request button small danger' data-requestID='".$request['wheeliams_staff_holidays_requestID']."'>Delete</button></li>";
			}
			echo "</ul>";
		}else{
			echo '<p><em>None</em></p>';
		}
	}
	
	function holiday_requests(){
		
		$WheeliamsStaff = new Wheeliams_Staff_Members($API);
		$Holidays = new Wheeliams_Staff_Member_Holidays($API);
		$Session = PerchMembers_Session::fetch();

		$requests = $Holidays->staff_holiday_requests(NULL);

		if($requests){
			echo "<table>";
			echo "<tr><th>Staff Member</th><th>Start</th><th>End</th><th>Approve</th><th>Deny</th></tr>";
			foreach($requests as $request){
				$WheeliamsStaff = new Wheeliams_Staff_Members($API);
				$StaffMember = $WheeliamsStaff->find($request['staffID']);	
				$dates = explode("-", $request['start']);
				$start = "$dates[2]/$dates[1]/$dates[0]";
				$dates = explode("-", $request['end']);
				$end = "$dates[2]/$dates[1]/$dates[0]";
				echo "<tr><td>".$StaffMember->name()."</td><td>$start</td><td>$end</td><td><button type='submit' class='approve-request button small secondary' data-requestID='".$request['wheeliams_staff_holidays_requestID']."'>Approve</button></td><td><button type='submit' class='deny-request button small danger' data-requestID='".$request['wheeliams_staff_holidays_requestID']."'>Deny</button></td></tr>";
			}
			echo "</table>";
		}else{
			echo '<p><em>None</em></p>';
		}
		
	}
	
	function delete_holiday_request($id){
		
		$Holidays = new Wheeliams_Staff_Member_Holidays($API);
		$Holidays->delete_request($id);
		
	}
	
	function decline_holiday_request($id){
		
		$Holidays = new Wheeliams_Staff_Member_Holidays($API);
		$Holidays->delete_request($id);
		
	}
	
	function approve_holiday_request($id){
		
		$Holidays = new Wheeliams_Staff_Member_Holidays($API);
		$Holidays->approve_request($id);
		
	}
	
	function get_holiday($id){
		
		$Holidays = new Wheeliams_Staff_Member_Holidays($API);
		$holiday = $Holidays->get_holiday($id);
		return $holiday;
		
	}
	
	function delete_holiday($id, $type){
		
		$Holidays = new Wheeliams_Staff_Member_Holidays($API);
		$Holidays->delete_holiday($id, $type);
		
	}
	
	function update_time($staffID, $type, $date, $time){
		
		$Time = new Wheeliams_Staff_Member_Times();
		$time = $Time->update_time($staffID, $type, $date, $time);
		
		echo $time;
		
	}
	
	function wheeliams_settings_input_table($type){
		
		$Settings = new Wheeliams_Settings_Inputs();
		
		$existing = $Settings->existing($type);
		
		usort($existing, function($a, $b) {
			$dynamicA = json_decode($a['dynamicFields'], true);
			$dynamicB = json_decode($b['dynamicFields'], true);
			$firstA = strtolower(reset($dynamicA));
			$firstB = strtolower(reset($dynamicB));
			return strcmp($firstA, $firstB);
		});
		
		if($existing){
			
			$count = count(json_decode($existing[0]['dynamicFields'],true));
			$dynamic = json_decode($existing[0]['dynamicFields'],true);

			echo '<div class="table-container compact">';
			echo '<table class="">';
			echo '<tr class="first-row">';
			foreach($dynamic as $label=>$data){
				echo '<th>'.ucwords($label).'</th>';
			}
			echo '<th>Timestamp</th>';
		    echo '<th>Delete</th>';
			echo '</tr>';
			
			foreach($existing as $setting){
				$dynamic = json_decode($setting['dynamicFields'],true);
				echo '<tr>';
				$i = 0;
				foreach($dynamic as $label=>$data){
					if($i==0){
						echo '<td><a href="'.$_SERVER['REQUEST_URI'].'&edit=1&id='.$setting['perch3_wheeliams_settings_inputID'].'">'.$data.'</a></td>';
					}else{
						echo '<td>'.$data.'</td>';	
					}
					$i++;
				}
				echo '<td>'.$setting['timestamp'].'</td>';
				echo '<td><a href="'.$_SERVER['REQUEST_URI'].'&delete=1&id='.$setting['perch3_wheeliams_settings_inputID'].'" class="warning">Delete</a></td>';
				echo '</tr>';
			}
			echo '</table></div>';
		
		}	
	}
	
	function wheeliams_settings_changelog($type, $id){
		
		$Settings = new Wheeliams_Settings_Inputs();
		$Settings_Changelog = new Wheeliams_Settings_Changelogs();
		
		$Settings_Changelog = $Settings_Changelog->logs($type,$id);
		
		if($Settings_Changelog){
			echo '<h2>Version History</h2>';
		
			echo '<div class="table-container compact">';
			echo '<table class="">';
			echo '<tr class="first-row">';
			echo '<th>Action</th>';
			echo '<th>Old Values</th>';
			echo '<th>New Values</th>';
			echo '<th>User</th>';
			echo '<th>Timestamp</th>';
			echo '</tr>';
			
			foreach($Settings_Changelog as $log){
				echo '<tr>';
				echo '<td>'.$log['action'].'</td>';
				echo '<td>'.format_json_values($log['oldValues']).'</td>';
				echo '<td>'.format_json_values($log['newValues']).'</td>';
				echo '<td>'.$log['memberEmail'].'</td>';
				echo '<td>'.$log['timestamp'].'</td>';
				echo '</tr>';
			}
			echo '</table></div>';
		
		}	
		
	}
	
	function format_json_values($json) {
		$data = json_decode($json, true);
		if (!$data) return '—';
	
		$output = '<dl>';
		foreach ($data as $key => $value) {
			$label = ucwords(str_replace('_', ' ', $key));
			$output .= "<dt>{$label}</dt><dd>{$value}</dd>";
		}
		$output .= '</dl>';
		return $output;
	}
	
	function wheeliams_component_table($type){
		$Components = new Wheeliams_Components();
		
		$existing = $Components->existing($type);
		
		if($existing){
			
			$count = count(json_decode($existing[0]['dynamicFields'],true));
			$dynamic = json_decode($existing[0]['dynamicFields'],true);
			
			$show_fields = array('part_description');
		
			echo '<div class="table-container compact">';
			echo '<table class="datatable">';
			echo '<thead class="first-row">';
			echo '<th>#</th>';
			echo '<th>Part Code</th>';
			
			if($type=='manufactured'){
				echo '<th>Description</th>';	
			}
			
			echo '<th>Timestamp</th>';
			echo '<th>Delete</th>';
			echo '</thead>';
			echo '<tbody>';
			
			foreach($existing as $setting){
				$dynamic = json_decode($setting['dynamicFields'],true);
				
				preg_match('/-(\d{4})-/', $setting['partCode'], $matches);
				$fourDigitNumber = $matches[1];
				
				echo '<tr>';
				echo '<td><a href="'.$_SERVER['REQUEST_URI'].'&edit=1&id='.$setting['perch3_wheeliams_componentID'].'">'.$fourDigitNumber.'</a></td>';
				echo '<td><a href="'.$_SERVER['REQUEST_URI'].'&edit=1&id='.$setting['perch3_wheeliams_componentID'].'">'.$setting['partCode'].'</a></td>';
				if($type=='manufactured'){
					echo '<td>'.$dynamic['part_description'].'</td>';	
				}
				echo '<td>'.$setting['timestamp'].'</td>';
				echo '<td><a href="'.$_SERVER['REQUEST_URI'].'&delete=1&id='.$setting['perch3_wheeliams_componentID'].'" class="warning">Delete</a></td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table></div>';
		
		}
	}
	
	function wheeliams_component_changelog($type, $id){
		
		$Components = new Wheeliams_Components();
		$Components_Changelog = new Wheeliams_Components_Changelogs();
		
		$Component_Changelog = $Components_Changelog->logs($type,$id);
		
		if($Component_Changelog){
			echo '<h2>Version History</h2>';
		
			echo '<div class="table-container compact">';
			echo '<table class="">';
			echo '<tr class="first-row">';
			echo '<th>Action</th>';
			echo '<th>Old Values</th>';
			echo '<th>New Values</th>';
			echo '<th>User</th>';
			echo '<th>Timestamp</th>';
			echo '</tr>';
			
			foreach($Component_Changelog as $log){
				echo '<tr>';
				echo '<td>'.$log['action'].'</td>';
				echo '<td>'.format_json_values($log['oldValues']).'</td>';
				echo '<td>'.format_json_values($log['newValues']).'</td>';
				echo '<td>'.$log['memberEmail'].'</td>';
				echo '<td>'.$log['timestamp'].'</td>';
				echo '</tr>';
			}
			echo '</table></div>';
		
		}
	
	}
		
	function wheeliams_supplier_table($type){
		$Suppliers = new Wheeliams_Suppliers();
		
		$existing = $Suppliers->existing();
		
		if($existing){
			
			$count = count(json_decode($existing[0]['dynamicFields'],true));
			$dynamic = json_decode($existing[0]['dynamicFields'],true);
		
			echo '<div class="table-container compact">';
			echo '<table class="">';
			echo '<tr class="first-row">';
			echo '<th>Name</th>';
			echo '<th>Timestamp</th>';
			echo '<th>Delete</th>';
			echo '</tr>';
			
			foreach($existing as $setting){
				echo '<tr>';
				echo '<td><a href="'.$_SERVER['REQUEST_URI'].'?edit=1&id='.$setting['wheeliams_supplierID'].'">'.$setting['name'].'</a></td>';
				echo '<td>'.$setting['timestamp'].'</td>';
				echo '<td><a href="'.$_SERVER['REQUEST_URI'].'?delete=1&id='.$setting['wheeliams_supplierID'].'" class="warning">Delete</a></td>';
				echo '</tr>';
			}
			
			echo '</table></div>';
		
		}
	}
	
	function wheeliams_supplier_changelog($id){
		
		$Suppliers = new Wheeliams_suppliers();
		$Suppliers_Changelog = new Wheeliams_Suppliers_Changelogs();
		
		$Supplier_Changelog = $Suppliers_Changelog->logs($id);

		if($Supplier_Changelog){
			echo '<h2>Version History</h2>';
		
			echo '<div class="table-container compact">';
			echo '<table class="">';
			echo '<tr class="first-row">';
			echo '<th>Action</th>';
			echo '<th>Old Values</th>';
			echo '<th>New Values</th>';
			echo '<th>User</th>';
			echo '<th>Timestamp</th>';
			echo '</tr>';
			
			foreach($Supplier_Changelog as $log){
				echo '<tr>';
				echo '<td>'.$log['action'].'</td>';
				echo '<td>'.format_json_values($log['oldValues']).'</td>';
				echo '<td>'.format_json_values($log['newValues']).'</td>';
				echo '<td>'.$log['memberEmail'].'</td>';
				echo '<td>'.$log['timestamp'].'</td>';
				echo '</tr>';
			}
			echo '</table></div>';
		
		}
		
	}
	
	function wheeliams_component_supplier_table($id){
		
		$Components = new Wheeliams_Components();
		$Suppliers = new Wheeliams_suppliers();
		
		$suppliers = $Components->componentSuppliers($id);
		
		echo '<section>';
		echo '<header>';
		echo '<h2>Existing Suppliers</h2>';
		echo '</header>';
		echo '<article class="grid">';
		
		foreach($suppliers as $supplier){
			
			$supplierData = $Suppliers->find($supplier['wheeliams_supplierID']);
			$latestPrice = $Components->latestComponentPrice($id);
			echo '<div class="card">';
			echo '<h3><a href="/settings/suppliers/?edit=1&id='.$supplier['wheeliams_supplierID'].'">'.$supplierData->name(); if($supplier['current']==1){ echo '<small>CURRENT</small>';} echo '</a></h3>';
			echo '<div class="graph" style="position: relative; width: 100%; height: 200px;">
				<canvas id="priceChart_'.$supplier['wheeliams_supplierID'].'"
					role="img" aria-label="Line chart showing price over time">
				</canvas>
				</div>';
			echo "<script>
			  // ── Replace this with data fetched from your database ──────────────
			  const rows_".$supplier['wheeliams_supplierID']." = ["; echo $Components->componentPriceGraph($id,$supplier['wheeliams_supplierID']); echo "];
			  // ───────────────────────────────────────────────────────────────────
			
			  const chartData_".$supplier['wheeliams_supplierID']." = rows_".$supplier['wheeliams_supplierID'].".map(r => ({ x: r.timestamp, y: r.price }));
			
			  new Chart(document.getElementById('priceChart_".$supplier['wheeliams_supplierID']."'), {
				type: 'line',
				data: {
				  datasets: [{
					label: 'Price',
					data: chartData_".$supplier['wheeliams_supplierID'].",
					borderColor: '#185FA5',
					backgroundColor: 'rgba(24, 95, 165, 0.08)',
					borderWidth: 2,
					pointRadius: 4,
					fill: true,
					tension: 0.35,
				  }]
				},
				options: {
				  responsive: true,
				  maintainAspectRatio: false,
				  plugins: {
					tooltip: {
					  callbacks: {
						label: ctx => '£' + ctx.parsed.y.toFixed(2)
					  }
					}
				  },
				  scales: {
					x: {
					  type: 'time',
					  time: { unit: 'day', tooltipFormat: 'dd MMM yyyy' }
					},
					y: {
					  ticks: { callback: v => '£' + v }
					}
				  }
				}
			  });
			</script>";
			PerchSystem::set_var('wheeliams_supplierID', $supplier['wheeliams_supplierID']);
			PerchSystem::set_var('latestPrice', $latestPrice['price']);
			PerchSystem::set_var('price', $Components->getComponentPrice($id,$supplier['wheeliams_supplierID']));
			wheeliams_form('component_supplier_price.html');
			echo '<footer>';
			wheeliams_form('component_supplier_current.html');
			wheeliams_form('component_supplier_remove.html');
			echo '</footer>';
			echo '</div>';
		}
		
		echo '</article>';
		echo '</section>';
		
	}
	
	function component($id){
		$WheeliamsComponents = new Wheeliams_Components();
		$data = $WheeliamsComponents->component($id);	
		return $data;
	}
	
	function componentByPartCode($partCode){
		$WheeliamsComponents = new Wheeliams_Components();
		$data = $WheeliamsComponents->byPartCode($partCode);	
		return $data;
	}
	
	function bom($id){
		$WheeliamsBoms = new Wheeliams_Boms();
		$data = $WheeliamsBoms->bom($id);	
		return $data;
	}
	
	function wheeliams_bom_table($type){
		$WheeliamsBoms = new Wheeliams_Boms();
		
		$existing = $WheeliamsBoms->existing($type);
		
		if($existing){
			
			$count = count(json_decode($existing[0]['dynamicFields'],true));
			$dynamic = json_decode($existing[0]['dynamicFields'],true);
			
			$show_fields = array('part_description');
		
			echo '<div class="table-container compact">';
			echo '<table class="datatable">';
			echo '<thead class="first-row">';
			echo '<th>Part Code</th>';
			echo '<th>Description</th>';	
			echo '<th>Timestamp</th>';
			echo '</thead>';
			echo '<tbody>';
			
			foreach($existing as $setting){
				$dynamic = json_decode($setting['dynamicFields'],true);
				echo '<tr>';
				echo '<td><a href="'.$_SERVER['REQUEST_URI'].'&edit=1&id='.$setting['perch3_wheeliams_componentID'].'">'.$setting['partCode'].'</a></td>';
				echo '<td>'.$dynamic['part_description'].'</td>';
				echo '<td>'.$setting['timestamp'].'</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table></div>';
		
		}
	}
	
	function wheeliams_component_bom_table($partCode){
		
		$WheeliamsBoms = new Wheeliams_Boms();
		$WheeliamsComponents = new Wheeliams_Components();
		$existing = $WheeliamsBoms->byPartCode($partCode);
		
		if($existing){
		
			echo '<section>';
			echo '<header>BOMs</header>';
			echo '<article>';
			echo '<table class="datatable">';
			echo '<thead class="first-row">';
			echo '<th>Part Code</th>';
			echo '<th>Description</th>';	
			echo '</thead>';
			echo '<tbody>';
			
			foreach($existing as $setting){
				$dynamic = json_decode($setting['dynamicFields'],true);
				$product = $WheeliamsComponents->component($setting['id']);
				$productJson = json_decode($product['dynamicFields'], true);
				echo '<tr>';
				echo '<td><a href="?type='.$product['type'].'&edit=1&id='.$product['perch3_wheeliams_componentID'].'">'.$product['partCode'].'</a></td>';
				echo '<td>'.$productJson['part_description'].'</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table></article></section>';
		
		}
		
	}
	
	function wheeliams_component_material_table($partCode){
		
		$WheeliamsBoms = new Wheeliams_Boms();
		$WheeliamsComponents = new Wheeliams_Components();
		
		$existing = $WheeliamsBoms->bom($partCode);
		
		if($existing){
		
			echo '<section>';
			echo '<header>Materials</header>';
			echo '<article>';
			echo '<table class="datatable">';
			echo '<thead class="first-row">';
			echo '<th>Part Code</th>';
			echo '</thead>';
			echo '<tbody>';
			
			foreach($existing as $setting){
				$dynamic = json_decode($setting['dynamicFields'],true);
				$product = $WheeliamsComponents->byPartCode($setting['partCode']);
				$productJson = json_decode($product['dynamicFields'], true);
				echo '<tr>';
				echo '<td><a href="?type='.$product['type'].'&edit=1&id='.$product['perch3_wheeliams_componentID'].'">'.$setting['partCode'].'</a></td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table></article></section>';
		
		}
		
	}
	
	function wheeliams_supplier_contact_table($id){
		
		$WheeliamsSuppliers = new Wheeliams_Suppliers($API);
		
		$existing = $WheeliamsSuppliers->contacts($id);
		
		if($existing){
		
			echo '<section>';
			echo '<header>Contacts</header>';
			echo '<article class="table-container">';
			echo '<table>';
			echo '<thead class="first-row">';
			echo '<th>Name</th><th>Contact</th><th>Type</th><th>Edit</th><th>Delete</th>';
			echo '</thead>';
			echo '<tbody>';
			
			foreach($existing as $setting){
				echo '<tr>';
				echo '<td>'.$setting['first_name'].' '.$setting['last_name'].'</td>';
				echo '<td>'.$setting['email'].'<br />'.$setting['phone'].'</td>';
				echo '<td>'.$setting['contact_type'].'</td>';
				echo '<td><a href="/settings/suppliers/contact/?edit=1&supplier='.$id.'&contact='.$setting['contactID'].'">Edit</a></td>';
				echo '<td><a href="/settings/suppliers/contact/?delete=1&supplier='.$id.'&contact='.$setting['contactID'].'">Delete</a></td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table></article></section>';
		
		}
		
	}