<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php

session_start();

if(perch_member_logged_in()){

	$servername = "localhost";
	$username = "wheeliams_dashboard";
	$password = "EGalEh1x..r3";
	$dbname = "wheeliams_dashboard";
	
	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error) {
	  die("Connection failed: " . $conn->connect_error);
	}
	
	$order = $_POST['order'];
	$item = $_POST['item'];
	$sku  = $_POST['sku'] ?? '';
	$qty  = (float)($_POST['qty'] ?? 0);

	$Session  = PerchMembers_Session::fetch();
	$memberID = $Session->get('memberID');

	$sql = "SELECT * FROM order_items WHERE orderid='".$order."' AND item='".$item."'";
	$result = $conn->query($sql);
	if ($result->num_rows == 1) {
		// Was complete -> un-completing: restore the stock that was removed.
		$sql = "DELETE FROM order_items WHERE orderid='".$order."' AND item='".$item."'";
		$result = $conn->query($sql);
		wheeliams_complete_job_stock($sku, $qty, $memberID, true);
	}else{
		// Marking complete: remove the product and its BOM components from stock.
		$sql = "INSERT INTO order_items (orderid, item) VALUES ('".$order."', '".$item."')";
		$result = $conn->query($sql);
		wheeliams_complete_job_stock($sku, $qty, $memberID, false);
	}

}