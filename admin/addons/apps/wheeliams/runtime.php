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

	include('Wheeliams.stock.class.php');
	include('Wheeliams.stock.item.class.php');

	include('Wheeliams.analysis.class.php');
	include('Wheeliams.analysis.item.class.php');

	include('Wheeliams.emailtemplates.class.php');
	include('Wheeliams.emailtemplate.item.class.php');

	include('Wheeliams.purchaseorders.class.php');
	include('Wheeliams.purchaseorder.item.class.php');

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
			$data['type'] = $_GET['type'];
			$data['cancel_link'] = "/settings/input-parameters/?type=".$_GET['type'];
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
			$data['type'] = $_GET['type'];
			$data['id'] = $_GET['id'];
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
		if($template == 'stock_manage.html'){
			$componentID = $_GET['component'] ?? null;
			if($componentID){
				$Stock = new Wheeliams_Stock($API);
				$component = $WheeliamsComponents->component($componentID);
				$dyn = json_decode($component['dynamicFields'], true) ?: array();
				$data['component']           = $componentID;
				$data['partCode']            = $component['partCode'];
				$data['part_description']    = $dyn['part_description'] ?? '';
				$data['unit_of_measure']     = $dyn['unit_of_measure'] ?? '';
				$data['maximum_stock_level'] = $dyn['maximum_stock_level'] ?? '';
				$data['reorder_quantity']    = $dyn['reorder_quantity'] ?? '';
				$data['current_level']       = wheeliams_num($Stock->level($componentID));
				$data['adjust']              = '0';
			}
		}
		if($template == 'email_template.html'){
			if(!empty($_GET['id'])){
				$Templates = new Wheeliams_Email_Templates($API);
				$t = $Templates->get($_GET['id']);
				if($t){
					$data = array_merge($data, $t);
					$data['id'] = $_GET['id'];
				}
			}
		}
		if($template == 'staff_access.html'){
			$staffID = $_GET['id'] ?? null;
			if($staffID){
				$staff = $WheeliamsStaff->staff($staffID);
				$data['staffID']      = $staffID;
				$data['memberID']     = $staff['memberID'];
				$data['name']         = $staff['name'];
				$data['access_level'] = wheeliams_member_access_tag($staff['memberID']) ?: 'none';
			}
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
						
						$has_length = !empty($data['length'])    && empty($data['no_length']);
						$has_head   = !empty($data['head_type']) && empty($data['no_head']);
						
						$partCode  = $data['thread_size'];
						$partCode .= $has_length ? 'x'.$data['length'] : '';
						$partCode .= '-';
						$partCode .= $has_head ? $data['head_type'].' ' : '';
						$partCode .= $data['fastener_type'];
						$partCode .= '-'.$data['fastener_grade'];
						$partCode .= '-'.$data['fastener_finish'];
						
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
				
				case 'staff_access':
					// Admin only; assign an MRP access-level tag to a member.
					if(!perch_member_has_tag('admin')) break;
					$staffID  = (int)($SubmittedForm->data['staffID'] ?? 0);
					$memberID = (int)($SubmittedForm->data['memberID'] ?? 0);
					$level    = $SubmittedForm->data['access_level'] ?? '';
					if($level === 'none') $level = ''; // sentinel -> remove all access
					$Session  = PerchMembers_Session::fetch();
					$status   = 'saved';
					if($memberID){
						// Don't let an admin remove their own admin access (self-lockout).
						if($memberID === (int)$Session->get('memberID') && $level !== 'admin'){
							$status = 'blocked';
						}else{
							wheeliams_set_member_access($memberID, $level);
						}
					}
					PerchUtil::redirect('/staff/staff-members/?id='.$staffID.'&access='.$status);
				break;

				case 'email_template_save':
					// Level 2/3 may manage order email templates.
					if(!wheeliams_can_order()) break;
					$Templates = new Wheeliams_Email_Templates($API);
					$Templates->save($SubmittedForm->data, (int)($SubmittedForm->data['id'] ?? 0));
					PerchUtil::redirect('/settings/email-templates/');
				break;

				case 'stock_manage':
					// Server-side gate: only Level 2/3 may change stock.
					if(!wheeliams_can_order()) break;
					$Session = PerchMembers_Session::fetch();
					$Stock = new Wheeliams_Stock($API);
					$componentID = (int)($SubmittedForm->data['component'] ?? 0);
					$memberID = $Session->get('memberID');
					if($componentID){
						$note = $SubmittedForm->data['note'] ?? '';
						// 1. Absolute correction, if the current-level field was set.
						if($SubmittedForm->data['current_level'] !== ''){
							$Stock->setAbsolute($componentID, $SubmittedForm->data['current_level'], 'manual_set', $memberID, $note);
						}
						// 2. Add/remove delta on top.
						if($SubmittedForm->data['adjust'] !== '' && (float)$SubmittedForm->data['adjust'] != 0){
							$Stock->adjust($componentID, $SubmittedForm->data['adjust'], 'manual_adjust', $memberID, $note);
						}
					}
					// Return to the manage form for the same part (shows the updated level).
					PerchUtil::redirect('/stock/?action=manage&component='.$componentID);
				break;

				case 'bom_manufactured_add':
					$Session = PerchMembers_Session::fetch();
					$WheeliamsBoms = new Wheeliams_Boms($API);
					// Each form submits only ONE quantity field; coalesce the others to '' so
					// an absent field isn't mistaken for a value ('' keeps a quantity of 0 valid).
					$rm = $SubmittedForm->data['quantity_add_rm'] ?? '';
					$c  = $SubmittedForm->data['quantity_c'] ?? '';
					$f  = $SubmittedForm->data['quantity_f'] ?? '';
					if($rm !== ''){
						$quantity = $rm;
					}elseif($c !== ''){
						$quantity = $c;
					}else{
						$quantity = $f;
					}
					$WheeliamsBoms->addToBom($SubmittedForm->data['type'],$_GET['id'],$SubmittedForm->data['component'],$quantity,$Session->get('memberID'));
					PerchUtil::redirect($_SERVER['REQUEST_URI']);
				break;
				
				case 'bom_edit':
					$Session = PerchMembers_Session::fetch();
					$WheeliamsBoms = new Wheeliams_Boms($API);
					if($SubmittedForm->data['quantity'] !== ''){
						$quantity = $SubmittedForm->data['quantity'];
					}
					$WheeliamsBoms->updateBom($SubmittedForm->data['wheeliams_bomID'],$quantity,$Session->get('memberID'));
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
			echo '<div class="table-container">';
			echo "<table>";
			echo "<tr class='first-line'><th>Staff Member</th><th>Start</th><th>End</th><th>Approve</th><th>Deny</th></tr>";
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
			echo '</div>';
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
					  ticks: { callback: v => '£' + v.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
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

	/* ---------------------------------------------------------------------
	 * Access control — three levels via Perch Member tags:
	 *   view (L1) < view_order (L2) < admin (L3)
	 * Existing admins keep the 'admin' tag and pass every check.
	 * ------------------------------------------------------------------- */

	function wheeliams_access_level(){
		if(!perch_member_logged_in()) return false;
		if(perch_member_has_tag('admin'))      return 'admin';
		if(perch_member_has_tag('view_order')) return 'view_order';
		if(perch_member_has_tag('view'))       return 'view';
		return false;
	}

	function wheeliams_access_rank($level){
		switch($level){
			case 'admin':      return 3;
			case 'view_order': return 2;
			case 'view':       return 1;
			default:           return 0;
		}
	}

	/* Redirect to the home page unless the member meets the minimum level. */
	function wheeliams_require_level($min){
		if(wheeliams_access_rank(wheeliams_access_level()) < wheeliams_access_rank($min)){
			header('location:/');
			exit;
		}
	}

	/* True for Level 2 (View & Order) and Level 3 (Admin). */
	function wheeliams_can_order(){
		return wheeliams_access_rank(wheeliams_access_level()) >= 2;
	}

	/* The highest access-level tag a given member holds ('' = none). */
	function wheeliams_member_access_tag($memberID){
		$memberID = (int)$memberID;
		if(!$memberID) return '';
		$db = PerchDB::fetch();
		$sql = 'SELECT t.tag FROM '.PERCH_DB_PREFIX.'members_member_tags mt, '.PERCH_DB_PREFIX.'members_tags t
				WHERE mt.tagID=t.tagID AND mt.memberID='.$db->pdb($memberID);
		$held = array();
		foreach((array)$db->get_rows($sql) as $r){
			$held[$r['tag']] = true;
		}
		if(isset($held['admin']))      return 'admin';
		if(isset($held['view_order'])) return 'view_order';
		if(isset($held['view']))       return 'view';
		return '';
	}

	/*
	 * Render the MRP access-level panel for a staff member.
	 * Always outputs the section; shows a message when the staff member has no
	 * linked Perch member (tags can only attach to a login account).
	 */
	function wheeliams_staff_access_panel($staffID){
		$WheeliamsStaff = new Wheeliams_Staff_Members();
		$staff = $WheeliamsStaff->staff($staffID);
		if(!$staff || empty($staff['memberID'])){
			echo '<section><header><h2>MRP Access Level</h2></header><article>';
			echo '<p>This staff member has no login account yet, so MRP access levels can’t be assigned. Create their login first.</p>';
			echo '</article></section>';
			return;
		}
		wheeliams_form('staff_access.html');
	}

	/* Set a member's MRP access level, replacing any existing level tag. */
	function wheeliams_set_member_access($memberID, $level){
		$levels = array('view', 'view_order', 'admin');
		$Tags = new PerchMembers_Tags();

		// Remove any access-level tag the member currently holds.
		foreach($levels as $slug){
			$Tag = $Tags->find_by_tag($slug);
			if($Tag){ $Tag->remove_from_member($memberID); }
		}

		// Grant the chosen level (empty = no access).
		if(in_array($level, $levels, true)){
			$Tag = $Tags->find_or_create($level);
			$Tag->add_to_member($memberID);
		}
	}

	/* ---------------------------------------------------------------------
	 * Stock control helpers (Phase 1)
	 * ------------------------------------------------------------------- */

	/* Format a stock number without trailing zeros: 12.500 -> 12.5, 12.000 -> 12 */
	function wheeliams_num($n){
		$s = number_format((float)$n, 3, '.', '');
		if(strpos($s, '.') !== false){
			$s = rtrim(rtrim($s, '0'), '.');
		}
		return $s;
	}

	/* Echo a <select> of components, grouped by type. Pass $only to limit types. */
	function wheeliams_component_dropdown($name, $selected=0, $autosubmit=false, $only=null){
		$Components = new Wheeliams_Components();
		$types = array(
			'manufactured'  => 'Manufactured & Purchased',
			'fasteners'     => 'Fasteners',
			'raw-materials' => 'Raw Materials',
		);
		if($only){
			$types = array_intersect_key($types, array_flip((array)$only));
		}
		echo '<select name="'.htmlspecialchars($name).'" id="'.htmlspecialchars($name).'" class="component-select"'.($autosubmit ? ' onchange="this.form.submit()"' : '').'>';
		echo '<option value="">Please Select</option>';
		foreach($types as $t => $label){
			$rows = $Components->existing($t);
			if(!$rows) continue;
			echo '<optgroup label="'.htmlspecialchars($label).'">';
			foreach($rows as $c){
				if(empty($c['partCode'])) continue;
				$sel = ((int)$c['perch3_wheeliams_componentID'] === (int)$selected) ? ' selected' : '';
				echo '<option value="'.(int)$c['perch3_wheeliams_componentID'].'"'.$sel.'>'.htmlspecialchars($c['partCode']).'</option>';
			}
			echo '</optgroup>';
		}
		echo '</select>';
	}

	/*
	 * Immediate parent part codes that use this component (one BOM level).
	 * NOTE: full multi-level roll-up to the top saleable product arrives with
	 * the BOM explosion engine (Foundation B) — this is the one-level version.
	 */
	function wheeliams_used_on($componentID){
		$Boms = new Wheeliams_Boms();
		$Components = new Wheeliams_Components();
		$rows = $Boms->byPartCode($componentID); // BOM rows where this component is a child
		$codes = array();
		foreach((array)$rows as $r){
			$parent = $Components->component($r['id']);
			if($parent && !empty($parent['partCode'])){
				$codes[$parent['partCode']] = true;
			}
		}
		return array_keys($codes);
	}

	/*
	 * A searchable table of all parts, each row linking to an action on the
	 * current stock page (manage or lookup). Uses the DataTables 'datatable'
	 * class from the header for search/sort/paging — far better than a huge
	 * <select> when there are many part codes.
	 */
	function wheeliams_stock_picker_table($action, $label){
		$Stock = new Wheeliams_Stock();
		$rows  = $Stock->report();
		if(!$rows){ echo '<p>No components found.</p>'; return; }
		echo '<div class="table-container compact"><table class="datatable">';
		echo '<thead class="first-row"><th>Part Code</th><th>Description</th><th>In Stock</th><th>UOM</th><th>Reorder Qty</th><th></th></thead><tbody>';
		foreach($rows as $r){
			if(empty($r['partCode'])) continue;
			$dyn = json_decode($r['dynamicFields'], true) ?: array();
			$id  = (int)$r['componentID'];
			echo '<tr>';
			echo '<td>'.htmlspecialchars($r['partCode']).'</td>';
			echo '<td>'.htmlspecialchars($dyn['part_description'] ?? '').'</td>';
			echo '<td>'.wheeliams_num($r['current_level']).'</td>';
			echo '<td>'.htmlspecialchars($dyn['unit_of_measure'] ?? '').'</td>';
			echo '<td>'.htmlspecialchars($dyn['reorder_quantity'] ?? '').'</td>';
			echo '<td><a href="?action='.htmlspecialchars($action).'&component='.$id.'">'.htmlspecialchars($label).'</a></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	/* Look-up: a table of parts (View link), or the detail panel for a selected part. */
	function wheeliams_stock_lookup(){
		if(empty($_GET['component'])){
			wheeliams_stock_picker_table('lookup', 'View');
			return;
		}

		$id = (int)$_GET['component'];
		$Stock = new Wheeliams_Stock();
		$component = component($id);
		if(!$component){ echo '<p>Part not found.</p>'; return; }
		$dyn = json_decode($component['dynamicFields'], true) ?: array();

		echo '<p><a href="?action=lookup" class="button back">&larr; Back to list</a></p>';
		echo '<dl class="stock-info">';
		echo '<dt>Part Code</dt><dd>'.htmlspecialchars($component['partCode']).'</dd>';
		echo '<dt>Description</dt><dd>'.htmlspecialchars($dyn['part_description'] ?? '').'</dd>';
		echo '<dt>Current Stock Level</dt><dd>'.wheeliams_num($Stock->level($id)).' '.htmlspecialchars($dyn['unit_of_measure'] ?? '').'</dd>';
		echo '<dt>Current Order Level (on order)</dt><dd>'.wheeliams_num($Stock->planned($id)).'</dd>';
		echo '<dt>Reorder Quantity</dt><dd>'.htmlspecialchars($dyn['reorder_quantity'] ?? '').'</dd>';
		echo '<dt>Maximum Stock Level</dt><dd>'.htmlspecialchars($dyn['maximum_stock_level'] ?? '').'</dd>';
		echo '</dl>';
	}

	/* Recent stock movements for a component. */
	function wheeliams_stock_movements_table($componentID){
		$Stock = new Wheeliams_Stock();
		$rows = $Stock->movements($componentID);
		if(!$rows) return;
		echo '<h2>Recent Movements</h2>';
		echo '<div class="table-container compact"><table class="">';
		echo '<tr class="first-row"><th>Date</th><th>Change</th><th>Reason</th><th>Note</th><th>User</th></tr>';
		foreach($rows as $m){
			$q = (float)$m['qty'];
			$user = $m['staff_name'] ?: ($m['memberEmail'] ?: '—');
			echo '<tr>';
			echo '<td>'.htmlspecialchars($m['created_at']).'</td>';
			echo '<td>'.($q > 0 ? '+' : '').wheeliams_num($q).'</td>';
			echo '<td>'.htmlspecialchars(str_replace('_', ' ', $m['reason'])).'</td>';
			echo '<td>'.htmlspecialchars($m['note']).'</td>';
			echo '<td>'.htmlspecialchars($user).'</td>';
			echo '</tr>';
		}
		echo '</table></div>';
	}

	/*
	 * Stock level report for all parts.
	 * Columns: Part Code, Description, Used On, In Stock, UOM, Reorder Qty.
	 * Sorted by used-on product code, then part code.
	 */
	function wheeliams_stock_report(){
		$Stock = new Wheeliams_Stock();
		$rows = $Stock->report();
		if(!$rows){ echo '<p>No components found.</p>'; return; }

		$data = array();
		foreach($rows as $r){
			if(empty($r['partCode'])) continue;
			$dyn = json_decode($r['dynamicFields'], true) ?: array();
			$usedOn = wheeliams_used_on($r['componentID']);
			$data[] = array(
				'partCode'    => $r['partCode'],
				'description' => $dyn['part_description'] ?? '',
				'usedOn'      => $usedOn,
				'usedOnSort'  => $usedOn ? $usedOn[0] : 'zzzz',
				'stock'       => (float)$r['current_level'],
				'uom'         => $dyn['unit_of_measure'] ?? '',
				'reorder'     => $dyn['reorder_quantity'] ?? '',
			);
		}
		usort($data, function($a, $b){
			$c = strcmp($a['usedOnSort'], $b['usedOnSort']);
			return $c !== 0 ? $c : strcmp($a['partCode'], $b['partCode']);
		});

		echo '<div class="table-container compact">';
		echo '<table class="datatable">';
		echo '<thead class="first-row"><th>Part Code</th><th>Description</th><th>Used On</th><th>In Stock</th><th>UOM</th><th>Reorder Qty</th></thead>';
		echo '<tbody>';
		foreach($data as $d){
			echo '<tr>';
			echo '<td>'.htmlspecialchars($d['partCode']).'</td>';
			echo '<td>'.htmlspecialchars($d['description']).'</td>';
			echo '<td>'.htmlspecialchars(implode(', ', $d['usedOn'])).'</td>';
			echo '<td>'.wheeliams_num($d['stock']).'</td>';
			echo '<td>'.htmlspecialchars($d['uom']).'</td>';
			echo '<td>'.htmlspecialchars($d['reorder']).'</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table></div>';
	}

	/* ---------------------------------------------------------------------
	 * BOM explosion viewer (Foundation B — Job Details, read-only)
	 * ------------------------------------------------------------------- */

	/*
	 * Render an indented multilevel BOM for a component (built for one unit).
	 * $editable adds Edit/Delete for this product's DIRECT BOM lines (level 1);
	 * $type is the BOM page type ('products'/'manufactured') for the edit links.
	 */
	function wheeliams_bom_explosion_table($componentID, $editable=false, $type=null, $showFiles=false){
		$Boms = new Wheeliams_Boms();
		$tree = $Boms->explode($componentID, 1);
		if(!$tree){ echo '<p>Component not found.</p>'; return; }
		if(empty($tree['children'])){ echo '<p>No BOM defined for this item.</p>'; return; }

		// Process + supplier/cost columns are only shown on the editable (admin) BOM.
		$canCost = $editable && wheeliams_can_order();

		echo '<div class="table-container compact"><table class="datatable bom-explosion">';
		echo '<thead class="first-row">';
		echo '<th>Part Code</th><th>Description</th><th>Qty</th><th>UOM</th><th>Material</th>';
		if($editable){ echo '<th>Process</th>'; }
		if($canCost){ echo '<th>Supplier</th><th>Unit Cost</th><th>Line Cost</th>'; }
		if($showFiles){ echo '<th class="no-print">Drawings</th>'; }
		if($editable){ echo '<th>Edit</th><th>Delete</th>'; }
		echo '</thead><tbody>';

		// Render the BOM contents only — the product itself is the page heading, not a row.
		foreach($tree['children'] as $child){
			wheeliams_bom_rows($child, $canCost, $editable, $type, $showFiles);
		}

		echo '</tbody>';
		if($canCost){
			$rollup = wheeliams_bom_rollup($tree);
			echo '<tfoot><tr><th colspan="8" style="text-align:right">Rolled-up material cost</th><th>£'.number_format($rollup, 2).'</th>';
			if($showFiles){ echo '<td class="no-print"></td>'; }
			if($editable){ echo '<td></td><td></td>'; }
			echo '</tr></tfoot>';
		}
		echo '</table></div>';
	}

	/* Recursively echo one BOM row per node, indented by depth (top-level BOM lines flush-left). */
	function wheeliams_bom_rows($node, $canCost, $editable=false, $type=null, $showFiles=false){
		$depth  = max(0, (int)$node['level'] - 1); // level 1 = direct BOM line = no indent
		$indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
		echo '<tr class="bom-level-'.$depth.'">';
		if($editable){
			$clink = '/components/?type='.htmlspecialchars((string)$node['type']).'&edit=1&id='.(int)$node['componentID'];
			echo '<td>'.$indent.'<a href="'.$clink.'">'.htmlspecialchars($node['partCode']).'</a></td>';
		}else{
			echo '<td>'.$indent.htmlspecialchars($node['partCode']).'</td>';
		}
		echo '<td>'.htmlspecialchars($node['description']).'</td>';
		echo '<td>'.wheeliams_num($node['extended_qty']).'</td>';
		echo '<td>'.htmlspecialchars($node['uom']).'</td>';
		echo '<td>'.htmlspecialchars($node['generic_material']).'</td>';
		if($editable){ echo '<td>'.htmlspecialchars($node['process_type']).'</td>'; }
		if($canCost){
			// Supplier name, hyperlinked to their site when we have one (the "buy" link).
			$sup = htmlspecialchars($node['supplierName']);
			if(!empty($node['supplierUrl'])){
				$sup = '<a href="'.htmlspecialchars($node['supplierUrl']).'" target="_blank" rel="noopener">'.$sup.'</a>';
			}
			echo '<td>'.$sup.'</td>';
			echo '<td>'.($node['unit_cost'] ? '£'.number_format($node['unit_cost'], 2) : '').'</td>';
			echo '<td>'.($node['line_cost'] ? '£'.number_format($node['line_cost'], 2) : '').'</td>';
		}
		if($showFiles){
			$dt = wheeliams_drive_type_for($node['partCode'], $node['type']);
			echo '<td class="no-print"><button type="button" class="button small" onclick="wheeliamsJobFiles(\''.htmlspecialchars($node['partCode'], ENT_QUOTES).'\',\''.htmlspecialchars($dt, ENT_QUOTES).'\')">Files</button></td>';
		}
		if($editable){
			// Edit/Delete apply only to this product's own (direct) BOM lines.
			if((int)$node['level'] === 1 && !empty($node['bom_id'])){
				echo '<td><a href="/boms/component/?type='.htmlspecialchars((string)$type).'&edit=1&id='.(int)$node['parent_id'].'&component='.(int)$node['bom_id'].'">Edit</a></td>';
				echo '<td>';
				PerchSystem::set_var('component', $node['bom_id']);
				wheeliams_form('bom_delete_row.html');
				echo '</td>';
			}else{
				echo '<td></td><td></td>';
			}
		}
		echo '</tr>';
		foreach($node['children'] as $child){
			wheeliams_bom_rows($child, $canCost, $editable, $type, $showFiles);
		}
	}

	/* Rolled-up material cost: leaves contribute their line cost; assemblies sum their children. */
	function wheeliams_bom_rollup($node){
		if(empty($node['children'])){
			return (float)$node['line_cost'];
		}
		$sum = 0;
		foreach($node['children'] as $child){
			$sum += wheeliams_bom_rollup($child);
		}
		return $sum;
	}

	/* ---------------------------------------------------------------------
	 * Order Analysis (Phase 3)
	 * ------------------------------------------------------------------- */

	/*
	 * Shopify GraphQL Admin API call. Delegates to the shared helper in
	 * /shopify-graphql.php, which handles the Dev Dashboard client-credentials
	 * token (fetched + cached, ~24h) and secrets.php config in one place.
	 */
	function wheeliams_shopify_graphql($query, $variables = array()){
		include_once $_SERVER['DOCUMENT_ROOT'].'/shopify-graphql.php';
		return wheeliams_shopify_gql($query, $variables);
	}

	/*
	 * Saleable products currently on order, from Shopify open orders.
	 * Matches each order line to a Perch component by partCode == line SKU.
	 * Returns [componentID => ['component'=>row, 'qty'=>fulfillable qty on order]].
	 * Uses the GraphQL Admin API (REST orders endpoint is deprecated); paginates
	 * open orders and reads each line's unfulfilledQuantity (≈ REST fulfillable_quantity).
	 */
	function wheeliams_demand_on_order(){
		$Components = new Wheeliams_Components();
		$demand = array();

		$query = <<<'GRAPHQL'
query($cursor: String) {
  orders(first: 15, after: $cursor, query: "status:open", sortKey: CREATED_AT) {
    pageInfo { hasNextPage endCursor }
    edges {
      node {
        lineItems(first: 50) {
          edges {
            node {
              sku
              unfulfilledQuantity
            }
          }
        }
      }
    }
  }
}
GRAPHQL;

		$cursor = null;
		$guard = 0; // hard stop against runaway pagination
		do{
			$res = wheeliams_shopify_graphql($query, array('cursor' => $cursor));
			$orders = $res['data']['orders'] ?? null;
			if(!$orders) break; // token/query error → return whatever we have

			foreach(($orders['edges'] ?? array()) as $edge){
				foreach(($edge['node']['lineItems']['edges'] ?? array()) as $liEdge){
					$item = $liEdge['node'];
					$qty = (float)($item['unfulfilledQuantity'] ?? 0);
					if($qty <= 0) continue;
					$sku = trim($item['sku'] ?? '');
					if($sku === '') continue;

					$c = $Components->bySku($sku); // match by SKU (first 8 chars = part-code base)
					if(!$c) continue;

					$id = (int)$c['perch3_wheeliams_componentID'];
					if(!isset($demand[$id])){
						$demand[$id] = array('component' => $c, 'qty' => 0);
					}
					$demand[$id]['qty'] += $qty;
				}
			}

			$hasNext = !empty($orders['pageInfo']['hasNextPage']);
			$cursor  = $orders['pageInfo']['endCursor'] ?? null;
		}while($hasNext && $cursor && ++$guard < 200);

		return $demand;
	}

	/*
	 * Round a required quantity up to the nearest batch-rounding multiple.
	 * Excel: IF((INT(QT/BRQ))*BRQ=QT, QT, (INT(QT/BRQ))*BRQ+BRQ)  ==  ceil(QT/BRQ)*BRQ
	 * A BRQ of 0 or 1 leaves the quantity unchanged.
	 */
	function wheeliams_batch_round($qt, $brq){
		$qt = (float)$qt;
		$brq = (float)$brq;
		if($brq <= 0) return $qt;
		return ceil($qt / $brq) * $brq;
	}

	/* Render the reorder list for a run, with editable Actual Order Quantity (AQ). */
	function wheeliams_reorder_list_table($runID){
		$Analysis = new Wheeliams_Analysis();
		$lines = $Analysis->lines($runID);
		if(!$lines){ echo '<p>No components were flagged for reorder in this run.</p>'; return; }
		echo '<div class="table-container compact">';
		echo '<section>';
		echo '<form method="post" action="/reorder/" class="flow">';
		echo '<input type="hidden" name="action" value="update_aq">';
		echo '<input type="hidden" name="run" value="'.(int)$runID.'">';
		echo '<div class="table-container"><table class="datatable">';
		echo '<thead class="first-row">';
		echo '<th>Supplier</th><th>Part Code</th><th>Description</th><th>Used On</th><th>Type</th><th>Process</th><th>Required</th><th>UOM</th><th>Order Qty (AQ)</th>';
		echo '</thead><tbody>';
		foreach($lines as $line){
			echo '<tr>';
			echo '<td>'.htmlspecialchars($line['supplierName'] ?: '—').'</td>';
			echo '<td>'.htmlspecialchars($line['partCode']).'</td>';
			echo '<td>'.htmlspecialchars($line['description']).'</td>';
			echo '<td>'.htmlspecialchars($line['used_on']).'</td>';
			echo '<td>'.htmlspecialchars($line['type']).'</td>';
			echo '<td>'.htmlspecialchars($line['process_type']).'</td>';
			echo '<td>'.wheeliams_num($line['total_qty']).'</td>';
			echo '<td>'.htmlspecialchars($line['uom']).'</td>';
			echo '<td><input type="text" name="aq['.(int)$line['perch3_wheeliams_reorder_lineID'].']" value="'.htmlspecialchars(wheeliams_num($line['actual_qty'])).'" size="8" inputmode="decimal"></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
		echo '<footer><button type="submit" class="button primary">Save order quantities</button></footer>';
		echo '</form>';
		echo '</section>';
		echo '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Order emails (Phase 3 — templates + preview, no sending yet)
	 * ------------------------------------------------------------------- */

	function wheeliams_email_templates(){
		$Templates = new Wheeliams_Email_Templates();
		return $Templates->all();
	}

	/* Replace {PLACEHOLDER} tokens in a string. */
	function wheeliams_render_placeholders($text, $vars){
		if($text === null) return '';
		foreach($vars as $k => $v){
			$text = str_replace('{'.$k.'}', $v, $text);
		}
		return $text;
	}

	/* Sequential PO / enquiry numbers (0001-9999, never reset). */
	function wheeliams_counters_install(){
		$db = PerchDB::fetch();
		$db->execute("CREATE TABLE IF NOT EXISTS perch3_wheeliams_counters (
			ckey VARCHAR(32) NOT NULL,
			cval INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (ckey)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}

	/* The next order number WITHOUT consuming it (preview only). */
	function wheeliams_peek_number($isPO){
		wheeliams_counters_install();
		$db  = PerchDB::fetch();
		$key = $isPO ? 'po' : 'enquiry';
		$row = $db->get_row('SELECT cval FROM perch3_wheeliams_counters WHERE ckey='.$db->pdb($key));
		$next = ($row ? (int)$row['cval'] : 0) + 1;
		return ($isPO ? 'PO' : 'ENQ').str_pad($next, 4, '0', STR_PAD_LEFT);
	}

	/* Consume and return the next order number (used when an order is saved). */
	function wheeliams_consume_number($isPO){
		wheeliams_counters_install();
		$db  = PerchDB::fetch();
		$key = $isPO ? 'po' : 'enquiry';
		$row = $db->get_row('SELECT cval FROM perch3_wheeliams_counters WHERE ckey='.$db->pdb($key));
		if($row){
			$next = (int)$row['cval'] + 1;
			$db->execute('UPDATE perch3_wheeliams_counters SET cval='.(int)$next.' WHERE ckey='.$db->pdb($key));
		}else{
			$next = 1;
			$db->insert('perch3_wheeliams_counters', array('ckey' => $key, 'cval' => 1));
		}
		return ($isPO ? 'PO' : 'ENQ').str_pad($next, 4, '0', STR_PAD_LEFT);
	}

	/* Distinct suppliers / component types / process types present in a run. */
	function wheeliams_run_suppliers($runID){
		// Suppliers in this run that have NOT yet had a PO/enquiry raised for it
		// (cascading exclusion — work through until the list is empty).
		$db = PerchDB::fetch();
		return $db->get_rows('SELECT DISTINCT rl.supplierID, rl.supplierName
			FROM perch3_wheeliams_reorder_lines rl
			WHERE rl.runID='.$db->pdb((int)$runID).'
			AND rl.supplierID NOT IN (
				SELECT po.supplierID FROM perch3_wheeliams_purchase_orders po WHERE po.runID='.$db->pdb((int)$runID).'
			)
			ORDER BY rl.supplierName ASC');
	}
	function wheeliams_run_types($runID){
		$db = PerchDB::fetch();
		return $db->get_rows('SELECT DISTINCT type FROM perch3_wheeliams_reorder_lines WHERE runID='.$db->pdb((int)$runID).' AND type<>"" ORDER BY type ASC');
	}
	function wheeliams_run_process_types($runID){
		$db = PerchDB::fetch();
		return $db->get_rows('SELECT DISTINCT process_type FROM perch3_wheeliams_reorder_lines WHERE runID='.$db->pdb((int)$runID).' AND process_type<>"" ORDER BY process_type ASC');
	}

	function wheeliams_reorder_lines_filtered($runID, $supplierID, $type, $process){
		$db  = PerchDB::fetch();
		$sql = 'SELECT * FROM perch3_wheeliams_reorder_lines WHERE runID='.$db->pdb((int)$runID);
		if($supplierID !== '' && $supplierID !== null){ $sql .= ' AND supplierID='.$db->pdb((int)$supplierID); }
		if($type    !== '' && $type    !== null){ $sql .= ' AND type='.$db->pdb($type); }
		if($process !== '' && $process !== null){ $sql .= ' AND process_type='.$db->pdb($process); }
		$sql .= ' ORDER BY partCode ASC';
		return $db->get_rows($sql);
	}

	/* Build the {ORDER_TABLE} HTML for a set of reorder lines. */
	function wheeliams_order_table_html($lines){
		$Components = new Wheeliams_Components();
		$html  = '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
		$html .= '<thead><tr><th>Part Code</th><th>Description</th><th>Order Qty</th><th>UOM</th><th>Supplier Note</th></tr></thead><tbody>';
		foreach($lines as $line){
			$c    = $Components->component($line['componentID']);
			$dyn  = $c ? (json_decode($c['dynamicFields'], true) ?: array()) : array();
			$note = $dyn['supplier_notes'] ?? '';
			$html .= '<tr>';
			$html .= '<td>'.htmlspecialchars($line['partCode']).'</td>';
			$html .= '<td>'.htmlspecialchars($line['description']).'</td>';
			$html .= '<td>'.wheeliams_num($line['actual_qty']).'</td>';
			$html .= '<td>'.htmlspecialchars($line['uom']).'</td>';
			$html .= '<td>'.htmlspecialchars($note).'</td>';
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';
		return $html;
	}

	/* Render a read-only preview of the order email for the current selection. */
	function wheeliams_order_email_preview($runID, $supplierID, $type, $process, $isPO, $templateID){
		$Templates = new Wheeliams_Email_Templates();
		$tpl = $Templates->get($templateID);
		if(!$tpl){ echo '<p class="alert warning">Choose an email template to preview.</p>'; return; }

		$lines = wheeliams_reorder_lines_filtered($runID, $supplierID, $type, $process);
		if(!$lines){ echo '<p>No matching lines for this selection.</p>'; return; }

		$supplierName  = $lines[0]['supplierName'] ?: 'Supplier';
		$supplierEmail = '';
		if($supplierID){
			$Suppliers = new Wheeliams_Suppliers();
			$s = $Suppliers->supplier($supplierID);
			$sdyn = $s ? (json_decode($s['dynamicFields'], true) ?: array()) : array();
			// Prefer the supplier's ORDERING contact (the address orders actually send to);
			// fall back to the general email field where no ORDERING contact exists.
			$ordering = $Suppliers->orderingContact($supplierID);
			$orderingEmail = $ordering ? trim($ordering['email']) : '';
			$supplierEmail = $orderingEmail !== '' ? $orderingEmail : ($sdyn['email'] ?? '');
		}

		$number    = wheeliams_peek_number($isPO);
		$orderType = $isPO ? 'Purchase Order' : 'Enquiry';
		$reference = $number.' '.$supplierName.' '.date('Y-m-d');

		$vars = array(
			'SUPPLIER_NAME'   => $supplierName,
			'SUPPLIER_EMAIL'  => $supplierEmail,
			'ORDER_TYPE'      => $orderType,
			'ORDER_NUMBER'    => $number,
			'ORDER_REFERENCE' => $reference,
			'DATE'            => date('d/m/Y'),
			'COMPONENT_TYPE'  => $type,
			'PROCESS_TYPE'    => $process,
			'ORDER_TABLE'     => wheeliams_order_table_html($lines),
		);

		$to      = wheeliams_render_placeholders($tpl['email_to'], $vars);
		$bcc     = wheeliams_render_placeholders($tpl['email_bcc'], $vars);
		$subject = wheeliams_render_placeholders($tpl['subject'], $vars);
		// nl2br the content text before {ORDER_TABLE} is substituted, so the table HTML isn't broken up.
		$content = wheeliams_render_placeholders(nl2br($tpl['content']), $vars);

		echo '<section class="email-preview flow">';
		echo '<header><h2>Email preview &mdash; '.htmlspecialchars($orderType).' '.htmlspecialchars($number).'</h2></header>';
		echo '<article class="flow">';
		echo '<dl class="stock-info">';
		echo '<dt>To:</dt><dd>'.htmlspecialchars($to ?: '—').'</dd>';
		echo '<dt>BCC:</dt><dd>'.htmlspecialchars($bcc ?: '—').'</dd>';
		echo '<dt>Subject:</dt><dd>'.htmlspecialchars($subject).'</dd>';
		echo '<dt>Reference:</dt><dd>'.htmlspecialchars($reference).'</dd>';
		echo '</dl>';
		echo '<div class="email-body">'.$content.'</div>';

		echo '<h3>Attachments (from Google Drive)</h3>';
		if(!empty($tpl['attachments'])){
			echo '<p>Template rule: <strong>'.htmlspecialchars($tpl['attachments']).'</strong></p>';
		}
		echo '<p>Drive files matching these part codes will be attached when the order is sent:</p><ul>';
		$seen = array();
		foreach($lines as $line){
			if(isset($seen[$line['partCode']])) continue;
			$seen[$line['partCode']] = true;
			echo '<li>'.htmlspecialchars($line['partCode']).'</li>';
		}
		echo '</ul>';

		// Save this selection as a Purchase Order / Enquiry (per supplier).
		if($supplierID){
			echo '<form method="post" action="/reorder/" class="flow no-print">';
			echo '<input type="hidden" name="action" value="save_po">';
			echo '<input type="hidden" name="run" value="'.(int)$runID.'">';
			echo '<input type="hidden" name="supplier" value="'.htmlspecialchars((string)$supplierID).'">';
			echo '<input type="hidden" name="ctype" value="'.htmlspecialchars((string)$type).'">';
			echo '<input type="hidden" name="ptype" value="'.htmlspecialchars((string)$process).'">';
			echo '<input type="hidden" name="otype" value="'.($isPO ? 'po' : 'enquiry').'">';
			echo '<input type="hidden" name="template" value="'.(int)$templateID.'">'; // chosen here, persisted onto the PO
			echo '<button type="submit" class="button primary">Save as '.htmlspecialchars($orderType).'</button>';
			echo '</form>';
		}else{
			echo '<p class="no-print"><em>Select a specific supplier above to save this as an order.</em></p>';
		}
		echo '</article></section>';
	}

	/* ---------------------------------------------------------------------
	 * Purchase Orders (Phase 3)
	 * ------------------------------------------------------------------- */

	/* Map a component type to the Drive folder type used by organise_drive_files.php. */
	function wheeliams_drive_type($componentType){
		switch($componentType){
			case 'fasteners':     return 'FASTENER';
			case 'raw-materials': return 'RAW MATERIALS';
			case 'products':      return 'KIT';
			default:              return 'COMPONENT';
		}
	}

	/* Drive folder type for a specific part — A06=KIT, A02=COMPONENT, else by type. */
	function wheeliams_drive_type_for($partCode, $componentType){
		if(strncmp((string)$partCode, 'A06', 3) === 0) return 'KIT';
		if(strncmp((string)$partCode, 'A02', 3) === 0) return 'COMPONENT';
		if($componentType === 'fasteners')     return 'FASTENER';
		if($componentType === 'raw-materials') return 'RAW MATERIALS';
		return 'COMPONENT';
	}

	/* Save a supplier's filtered reorder lines as a PO / enquiry. Returns PO id (0 if not saved). */
	function wheeliams_save_purchase_order($runID, $supplierID, $type, $process, $isPO, $memberID, $templateID = 0){
		// A PO is per-supplier — require a specific supplier, not "All".
		if($supplierID === '' || $supplierID === null) return 0;

		$lines = wheeliams_reorder_lines_filtered($runID, $supplierID, $type, $process);
		if(!$lines) return 0;

		$PO = new Wheeliams_Purchase_Orders();
		$PO->install(); // ensure the PO tables exist (the save may be the first use)

		$supplierName = $lines[0]['supplierName'] ?: 'Supplier';
		$number = wheeliams_consume_number($isPO); // consume only once we're sure we'll save
		return $PO->createFromLines($number, !$isPO, $supplierID, $supplierName, $type, $process, $runID, $lines, $memberID, $templateID);
	}

	/* Table of all purchase orders (optionally filtered by supplier). */
	function wheeliams_po_list_table($supplierFilter=null){
		$PO = new Wheeliams_Purchase_Orders();
		$rows = $PO->orders($supplierFilter);
		if(!$rows){ echo '<p>No purchase orders yet.</p>'; return; }
		echo '<section>';
		echo '<header>';
		echo '<h2>Items</h2>';
		echo '</header>';
		echo '<article>';
		echo '<div class="table-container compact"><table class="datatable">';
		echo '<thead class="first-row"><th>Number</th><th>Type</th><th>Supplier</th><th>Date</th><th>Status</th><th></th></thead><tbody>';
		foreach($rows as $r){
			$id = (int)$r['perch3_wheeliams_purchase_orderID'];
			echo '<tr>';
			echo '<td>'.htmlspecialchars($r['number']).'</td>';
			echo '<td>'.($r['is_enquiry'] ? 'Enquiry' : 'Purchase Order').'</td>';
			echo '<td>'.htmlspecialchars($r['supplierName'] ?: '—').'</td>';
			echo '<td>'.htmlspecialchars($r['created_at']).'</td>';
			echo '<td>'.htmlspecialchars(ucfirst(str_replace('-', ' ', $r['status']))).'</td>';
			echo '<td><a class="button small" href="?po='.$id.'">View</a></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
		echo '</article>';
		echo '</section>';
	}

	/* Full purchase order: header, lines, check-in form, print + drawing links. */
	function wheeliams_po_detail($poID){
		$PO = new Wheeliams_Purchase_Orders();
		$po = $PO->order($poID);
		if(!$po){ echo '<p>Purchase order not found.</p>'; return; }
		$lines = $PO->lines($poID);
		$typeLabel = $po['is_enquiry'] ? 'Enquiry' : 'Purchase Order';

		// Fully received = every line checked in. Once complete, re-sending and
		// checking in are redundant, so those controls are hidden below.
		$allReceived = !empty($lines);
		foreach($lines as $l){
			if(empty($l['checked_in'])){ $allReceived = false; break; }
		}

		echo '<section>';
		echo '<header>';
		echo $typeLabel;
		echo '</header>';
		echo '<article>';
		echo '<dl class="stock-info">';
		echo '<dt>'.$typeLabel.' number:</dt><dd>'.htmlspecialchars($po['number']).'</dd>';
		echo '<dt>Supplier:</dt><dd>'.htmlspecialchars($po['supplierName'] ?: '—').'</dd>';
		echo '<dt>Date:</dt><dd>'.htmlspecialchars($po['created_at']).'</dd>';
		echo '<dt>Reference:</dt><dd>'.htmlspecialchars($po['reference']).'</dd>';
		echo '<dt>Status:</dt><dd>'.htmlspecialchars(ucfirst(str_replace('-', ' ', $po['status']))).'</dd>';
		if(!empty($po['sent_at'])){
			echo '<dt>Sent:</dt><dd>'.htmlspecialchars($po['sent_at']).' to '.htmlspecialchars($po['sent_to'] ?: '—').'</dd>';
		}
		echo '</dl>';
		echo '</article>';
		//echo '<footer>';
		//echo '<button type="button" class="button" onclick="window.print()">Print</button>';
		//echo '</footer>';
		echo '</section>';
		
		echo '<a href="/purchase-orders/" class="button back">&larr; All orders</a>';

		

		// Send to supplier (Brevo). The template was chosen on the Reorder page and saved
		// onto this order; the parts' Drive files attach at send time.
		$templates = wheeliams_email_templates();
		echo '<section class="flow no-print email-preview"><header><h2>Send to Supplier</h2></header><article>';

		// The order email goes to the supplier's ORDERING contact — require one.
		$Suppliers = new Wheeliams_Suppliers();
		$ordering  = $po['supplierID'] ? $Suppliers->orderingContact($po['supplierID']) : null;

		$sendTemplateID = 0; // resolved below; drives the send form in the footer

		if(!$ordering || trim($ordering['email']) === ''){
			echo '<p class="alert warning">This supplier has no <strong>ORDERING</strong> contact with an email address. Add one before this order can be sent.</p>';
			if($po['supplierID'] && perch_member_has_tag('admin')){
				echo '<p><a class="button" href="/settings/suppliers/?edit=1&id='.(int)$po['supplierID'].'">Add ORDERING contact</a></p>';
			}else{
				echo '<p><em>Ask an administrator to add an ORDERING contact to '.htmlspecialchars($po['supplierName'] ?: 'this supplier').'.</em></p>';
			}
		}elseif(!$templates){
			echo '<p>No email templates yet. <a href="/settings/email-templates/?new=1">Create one</a> first.</p>';
		}else{
			// The template chosen on the Reorder page. Fall back to the first template for
			// orders saved before the template was stored on the PO.
			$tpl = null;
			foreach($templates as $t){
				if((int)$t['perch3_wheeliams_email_templateID'] === (int)$po['template_id']){ $tpl = $t; break; }
			}
			$isFallback = !$tpl;
			if(!$tpl){ $tpl = reset($templates); }
			$sendTemplateID = (int)$tpl['perch3_wheeliams_email_templateID'];

			// Full message preview, built by the same code that sends it.
			include_once $_SERVER['DOCUMENT_ROOT'].'/admin/addons/apps/wheeliams/Wheeliams.orderemail.class.php';
			$Mailer = new Wheeliams_Order_Email();
			$pv = $Mailer->preview($po, $lines, $tpl);
			echo '<dl class="stock-info">';
			echo '<dt>To:</dt><dd>'.htmlspecialchars($pv['to'] ?: '—').'</dd>';
			echo '<dt>BCC:</dt><dd>'.htmlspecialchars($pv['bcc'] ?: '—').'</dd>';
			echo '<dt>Subject:</dt><dd>'.htmlspecialchars($pv['subject'] ?: '—').'</dd>';
			echo '<dt>Reference:</dt><dd>'.htmlspecialchars($po['reference']).'</dd>';
			echo '<dt>Template:</dt><dd>'.htmlspecialchars($tpl['name']).'</dd>';
			echo '</dl>';
			echo '<div class="email-body">'.$pv['html'].'</div>';
		}
		echo '</article>';
		if($sendTemplateID && !$allReceived){
			echo '<footer>';
			echo '<form method="post" action="/purchase-orders/?po='.(int)$poID.'">';
			echo '<input type="hidden" name="action" value="send_email">';
			echo '<input type="hidden" name="po" value="'.(int)$poID.'">';
			echo '<input type="hidden" name="template" value="'.$sendTemplateID.'">';
			$label = !empty($po['sent_at']) ? 'Re-send to Supplier' : 'Send to Supplier';
			echo '<button type="submit" class="button primary" onclick="return confirm(\'Send this order to the supplier now?\')">'.$label.'</button>';
			echo '</form>';
			echo '</footer>';
		}
		echo '</section>';

		echo '<section>';
		echo '<header>Lines</header>';
		echo '<article>';
		echo '<form method="post" action="/purchase-orders/?po='.(int)$poID.'" class="flow">';
		echo '<input type="hidden" name="action" value="checkin">';
		echo '<input type="hidden" name="po" value="'.(int)$poID.'">';
		echo '<div class="table-container"><table class="datatable">';
		echo '<thead class="first-row"><th>Part Code</th><th>Description</th><th>Order Qty</th><th>UOM</th><th>Supplier Note</th><th>Received</th><th>Check In</th><th class="no-print">Drawing</th></thead><tbody>';
		foreach($lines as $line){
			$lid  = (int)$line['perch3_wheeliams_purchase_order_lineID'];
			$done = !empty($line['checked_in']);
			echo '<tr>';
			echo '<td>'.htmlspecialchars($line['partCode']).'</td>';
			echo '<td>'.htmlspecialchars($line['description']).'</td>';
			echo '<td>'.wheeliams_num($line['order_qty']).'</td>';
			echo '<td>'.htmlspecialchars($line['uom']).'</td>';
			echo '<td>'.htmlspecialchars($line['supplier_note']).'</td>';
			if($done){
				echo '<td>'.wheeliams_num($line['qty_received']).'</td>';
				echo '<td>Received</td>';
			}else{
				echo '<td><input type="text" name="received['.$lid.']" value="'.htmlspecialchars(wheeliams_num($line['order_qty'])).'" size="6" inputmode="decimal"></td>';
				echo '<td><input type="checkbox" name="checked['.$lid.']" value="1"></td>';
			}
			$dt = wheeliams_drive_type($line['type']);
			echo '<td class="no-print"><button type="button" class="button small" onclick="wheeliamsPoFiles(\''.htmlspecialchars($line['partCode'], ENT_QUOTES).'\',\''.htmlspecialchars($dt, ENT_QUOTES).'\')">Files</button></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';

		if(!$allReceived){
			echo '<footer class="no-print"><button type="submit" class="button primary">Check In Ticked Lines</button></footer>';
		}else{
			echo '<footer class="no-print"><p class="alert success">All lines received &mdash; this order is complete.</p></footer>';
		}
		echo '</form>';
		echo '</article>';
		echo '</section>';

		// Files modal — the "Files" button opens the parts' drawings in an overlay.
		// Loads on click only (the endpoint moves files into place as a side effect).
		echo <<<'JS'
<style>
.files-modal{ position:fixed; inset:0; z-index:2000; display:none; align-items:center; justify-content:center; }
.files-modal.open{ display:flex; }
.files-modal-backdrop{ position:absolute; inset:0; background:rgba(0,0,0,.55); }
.files-modal-box{ position:relative; background:#fff; max-width:600px; width:90%; max-height:80vh; overflow:auto; padding:1.5rem 1.75rem; border-radius:6px; box-shadow:0 12px 44px rgba(0,0,0,.3); }
.files-modal-close{ position:absolute; top:.35rem; right:.6rem; border:0; background:none; font-size:1.7rem; line-height:1; cursor:pointer; }
.files-modal-box h4{ margin:1rem 0 .25rem; }
</style>
<div id="poFilesModal" class="files-modal" aria-hidden="true">
	<div class="files-modal-backdrop" data-close></div>
	<div class="files-modal-box">
		<button type="button" class="files-modal-close" data-close aria-label="Close">&times;</button>
		<h3 id="poFilesModalTitle">Files</h3>
		<div id="poFilesModalBody">Loading&hellip;</div>
	</div>
</div>
<script>
function wheeliamsPoFilesClose(){ document.getElementById('poFilesModal').classList.remove('open'); }

function wheeliamsPoFiles(code, type){
	var modal = document.getElementById('poFilesModal');
	document.getElementById('poFilesModalTitle').textContent = code + ' — Files';
	var body = document.getElementById('poFilesModalBody');
	body.textContent = 'Loading…';
	modal.classList.add('open');
	fetch('/organise_drive_files.php?productCode=' + encodeURIComponent(code) + '&type=' + encodeURIComponent(type))
		.then(function(r){ return r.json(); })
		.then(function(d){
			if(d.error){ body.textContent = d.error; return; }
			if(!d.files || !d.files.length){ body.textContent = 'No files found.'; return; }
			var groups = {};
			d.files.forEach(function(f){ var cat = f.path || 'Files'; (groups[cat] = groups[cat] || []).push(f); });
			var html = '';
			Object.keys(groups).sort().forEach(function(cat){
				html += '<h4>' + cat + '</h4><ul>';
				groups[cat].forEach(function(f){ html += '<li><a href="' + f.webViewLink + '" target="_blank" rel="noopener">' + f.name + '</a></li>'; });
				html += '</ul>';
			});
			body.innerHTML = html;
		})
		.catch(function(){ body.textContent = 'Error loading files.'; });
}

document.addEventListener('click', function(e){ if(e.target.hasAttribute('data-close')) wheeliamsPoFilesClose(); });
document.addEventListener('keydown', function(e){ if(e.key === 'Escape') wheeliamsPoFilesClose(); });
</script>
JS;
	}

	/* ---------------------------------------------------------------------
	 * Phase 6 — automatic stock decrement on job completion
	 * ------------------------------------------------------------------- */

	/*
	 * When a job (a Shopify order line) is marked complete, remove the completed
	 * quantity of the saleable product AND its exploded BOM components from
	 * stock. $reverse=true restores them (job re-opened). Services are skipped.
	 * Matches the product by partCode == SKU. Movements are logged with reason
	 * 'completion', attributed to the member who marked it.
	 */
	function wheeliams_complete_job_stock($sku, $qty, $memberID, $reverse=false){
		$sku = trim((string)$sku);
		$qty = (float)$qty;
		if($sku === '' || $qty <= 0) return;

		$Components = new Wheeliams_Components();
		$product = $Components->bySku($sku); // match by SKU (first 8 chars = part-code base)
		if(!$product) return;
		$productID = (int)$product['perch3_wheeliams_componentID'];

		$Stock = new Wheeliams_Stock();
		$Boms  = new Wheeliams_Boms();

		$sign = $reverse ? 1 : -1; // complete removes stock, re-open restores it
		$note = 'Job '.($reverse ? 're-opened' : 'complete').' '.$sku;

		// The saleable product itself (unless it's a service).
		if($product['type'] !== 'services'){
			$Stock->adjust($productID, $sign * $qty, 'completion', $memberID, $note);
		}

		// Every component / fastener / material in the BOM, × completed qty.
		foreach($Boms->explodeFlat($productID, $qty) as $cid => $node){
			if(!empty($node['is_service'])) continue; // services carry no stock
			$Stock->adjust($cid, $sign * (float)$node['total_qty'], 'completion', $memberID, $note);
		}
	}

	/*
	 * The BOM page type for a part code. The BOM app categorises by prefix:
	 * A02 = manufactured component, A06 = kit/product. Others aren't BOM-able.
	 */
	function wheeliams_bom_type_for_partcode($partCode){
		if(strncmp((string)$partCode, 'A02', 3) === 0) return 'manufactured';
		if(strncmp((string)$partCode, 'A06', 3) === 0) return 'products';
		return null;
	}

	/* Job Details URL for a Shopify SKU (matched to a component by partCode), or null.
	   Points at the read-only /job/ view (L1+); admins get an "Edit BOM" link there. */
	function wheeliams_bom_link_for_sku($sku){
		$sku = trim((string)$sku);
		if($sku === '') return null;
		$Components = new Wheeliams_Components();
		$c = $Components->bySku($sku);
		if(!$c) return null;
		if(!wheeliams_bom_type_for_partcode($c['partCode'])) return null; // only A02/A06 have a BOM view
		return '/job/?id='.(int)$c['perch3_wheeliams_componentID'];
	}