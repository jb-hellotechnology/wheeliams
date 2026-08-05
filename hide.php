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

	$conn->query("CREATE TABLE IF NOT EXISTS hidden_items (orderid VARCHAR(191), item VARCHAR(191))");

	$order = $_POST['order'];
	$item  = $_POST['item'];

	$sql = "SELECT * FROM hidden_items WHERE orderid='".$order."' AND item='".$item."'";
	$result = $conn->query($sql);
	if ($result->num_rows == 1) {
		$sql = "DELETE FROM hidden_items WHERE orderid='".$order."' AND item='".$item."'";
		$result = $conn->query($sql);
	}else{
		$sql = "INSERT INTO hidden_items (orderid, item) VALUES ('".$order."', '".$item."')";
		$result = $conn->query($sql);
	}

}
