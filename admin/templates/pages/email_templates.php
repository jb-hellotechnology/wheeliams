<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
// Order email template management — Access Level 2 (View & Order) and 3 (Admin).
if(!perch_member_logged_in()){
	header('location:/');
	exit;
}
wheeliams_require_level('view_order');

$Templates = new Wheeliams_Email_Templates();
$Templates->install();

if(!empty($_GET['delete']) && !empty($_GET['id']) && wheeliams_can_order()){
	$Templates->remove($_GET['id']);
	PerchUtil::redirect('/settings/email-templates/');
}

perch_layout('header');
?>
<main class="full">
	<h1>Order Email Templates</h1>
<?php
if(!empty($_GET['edit']) || isset($_GET['new'])){

	wheeliams_form('email_template.html');

	echo '<section class="flow"><header><h2>Placeholders</h2></header><article>';
	echo '<p>Use any of these in the To, BCC, Subject or Content fields:</p><ul>';
	foreach(array('{SUPPLIER_NAME}','{SUPPLIER_EMAIL}','{ORDER_TYPE}','{ORDER_NUMBER}','{ORDER_REFERENCE}','{DATE}','{COMPONENT_TYPE}','{PROCESS_TYPE}','{ORDER_TABLE}') as $ph){
		echo '<li><code>'.htmlspecialchars($ph).'</code></li>';
	}
	echo '</ul></article></section>';
	echo '<p><a href="/settings/email-templates/" class="button back">&larr; Back</a></p>';

}else{

	$all = $Templates->templates();
	if($all){
		echo '<div class="table-container compact"><table class="datatable">';
		echo '<thead class="first-row"><th>Name</th><th>Subject</th><th>Edit</th><th>Delete</th></thead><tbody>';
		foreach($all as $t){
			$id = (int)$t['perch3_wheeliams_email_templateID'];
			echo '<tr>';
			echo '<td>'.htmlspecialchars($t['name']).'</td>';
			echo '<td>'.htmlspecialchars($t['subject']).'</td>';
			echo '<td><a href="/settings/email-templates/?edit=1&id='.$id.'">Edit</a></td>';
			echo '<td><a href="/settings/email-templates/?delete=1&id='.$id.'" class="warning" onclick="return confirm(\'Are you sure?\');">Delete</a></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}else{
		echo '<p><em>No templates yet.</em></p>';
	}
	
	echo '<p><a href="/settings/email-templates/?new=1" class="button primary">New Template</a></p>';

}
?>
</main>
<?php
perch_layout('footer');
?>
