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

function get_metafields($productid){

	$gid = wheeliams_shopify_gid('Product', $productid);

	// Read the product's body_html (descriptionHtml in GraphQL).
	$query = <<<'GRAPHQL'
query($id: ID!) {
  product(id: $id) { descriptionHtml }
}
GRAPHQL;

	$res = wheeliams_shopify_gql($query, array('id' => $gid));
	$body_html = $res['data']['product']['descriptionHtml'] ?? '';

	print_r($body_html);

	$html = json_encode(addslashes($body_html));

	// Write it into the short_product_description metafield via metafieldsSet.
	$mutation = <<<'GRAPHQL'
mutation($metafields: [MetafieldsSetInput!]!) {
  metafieldsSet(metafields: $metafields) {
    metafields { id namespace key }
    userErrors { field message }
  }
}
GRAPHQL;

	$variables = array('metafields' => array(array(
		'ownerId'   => $gid,
		'namespace' => 'product',
		'key'       => 'short_product_description',
		'type'      => 'rich_text_field',
		'value'     => $html,
	)));

	$res = wheeliams_shopify_gql($mutation, $variables);

	print_r($res);
}

//GET LIST OF PRODUCTS FROM DATABASE
$sql = "SELECT * FROM shopify_products ORDER BY productid ASC LIMIT 5";
$result = $conn->query($sql);

//LOOP THROUGH PRODUCTS
while($row = mysqli_fetch_assoc($result)) {
	$metafields = get_metafields($row['productid']);
}
