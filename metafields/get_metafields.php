<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

function get_metafields($productid){
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, 'https://wheeliamsltd.myshopify.com/admin/api/2024-04/products/'.$productid.'/metafields.json');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	
	
	$headers = array();
	$headers[] = 'X-Shopify-Access-Token: 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';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	$data = curl_exec($ch);
	if (curl_errno($ch)) {
	    echo 'Error:' . curl_error($ch);
	}
	curl_close($ch);
	
	$metafields = $data;
	return addslashes($metafields);
}

//GET LIST OF PRODUCTS FROM DATABASE
$sql = "SELECT * FROM shopify_products ORDER BY productid ASC";
$result = $conn->query($sql);

//LOOP THROUGH PRODUCTS
while($row = mysqli_fetch_assoc($result)) {
	$metafields = get_metafields($row['productid']);
	$sql2 = "DELETE FROM metafields WHERE productid='".$row['productid']."'";
	$result2 = $conn->query($sql2);
	$sql3 = "INSERT INTO metafields (productid, metafields) VALUES ('".$row['productid']."', '".$metafields."')";
	$result3 = $conn->query($sql3);
}