<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/secrets.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/shopify-graphql.php';

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

/*
 * Fetch a product's metafields via GraphQL and return them as a JSON string in
 * the same {"metafields":[...]} envelope the old REST call produced, so anything
 * reading this column keeps working. addslashes() for the raw INSERT below.
 */
function get_metafields($productid){
	$query = <<<'GRAPHQL'
query($id: ID!) {
  product(id: $id) {
    metafields(first: 100) {
      edges { node { id namespace key value type } }
    }
  }
}
GRAPHQL;

	$res = wheeliams_shopify_gql($query, array('id' => wheeliams_shopify_gid('Product', $productid)));
	if(isset($res['errors'])){
		echo 'Error: '.htmlspecialchars(json_encode($res['errors'])).'<br />';
	}

	$metafields = array();
	foreach(($res['data']['product']['metafields']['edges'] ?? array()) as $edge){
		$metafields[] = $edge['node'];
	}

	return addslashes(json_encode(array('metafields' => $metafields)));
}

//GET LIST OF PRODUCTS FROM DATABASE
$sql = "SELECT * FROM shopify_products ORDER BY productid ASC";
$result = $conn->query($sql);

//LOOP THROUGH PRODUCTS
while($row = mysqli_fetch_assoc($result)) {
	$metafields = get_metafields($row['productid']);
	$sql2 = "DELETE FROM metafields WHERE productid='".$conn->real_escape_string($row['productid'])."'";
	$result2 = $conn->query($sql2);
	$sql3 = "INSERT INTO metafields (productid, metafields) VALUES ('".$conn->real_escape_string($row['productid'])."', '".$metafields."')";
	$result3 = $conn->query($sql3);
}
