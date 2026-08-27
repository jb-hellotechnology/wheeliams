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

$conn->query('TRUNCATE TABLE shopify_products');

/*
 * Pull a value out of a GraphQL metafields connection by key (namespace-agnostic,
 * matching the old REST behaviour which matched on key alone). Returns null when
 * the key is absent so callers can tell "no metafield" from "empty value".
 */
function metafield_value($metafieldEdges, $key){
	foreach(($metafieldEdges ?? array()) as $edge){
		if(($edge['node']['key'] ?? '') === $key){
			return $edge['node']['value'];
		}
	}
	return null;
}

/* True when the product carries a show_on_dashboard metafield (present at all). */
function has_metafield($metafieldEdges, $key){
	foreach(($metafieldEdges ?? array()) as $edge){
		if(($edge['node']['key'] ?? '') === $key) return true;
	}
	return false;
}

/* ---------------------------------------------------------------------------
 * Pass 1 — products. Only products that carry a show_on_dashboard metafield are
 * put on the dashboard (unchanged from the old REST script). Insert one product
 * row each and remember the product (id → title + show flag) for the variant pass.
 * ------------------------------------------------------------------------- */
$productsQuery = <<<'GRAPHQL'
query($cursor: String) {
  products(first: 40, after: $cursor) {
    pageInfo { hasNextPage endCursor }
    edges {
      node {
        legacyResourceId
        title
        metafields(first: 20) { edges { node { key value } } }
      }
    }
  }
}
GRAPHQL;

$shownProducts = array(); // productId => ['title'=>..., 'show'=>0|1]
$cursor = null;
$guard = 0;
do{
	$res = wheeliams_shopify_gql($productsQuery, array('cursor' => $cursor));
	$products = $res['data']['products'] ?? null;
	if(!$products){
		echo '<p>Error loading products: '.htmlspecialchars(json_encode($res['errors'] ?? $res)).'</p>';
		break;
	}

	foreach(($products['edges'] ?? array()) as $edge){
		$node = $edge['node'];
		$mf   = $node['metafields']['edges'] ?? array();

		if(!has_metafield($mf, 'show_on_dashboard')) continue; // not a dashboard product

		$showVal = metafield_value($mf, 'show_on_dashboard');
		$show = ($showVal !== 0 && $showVal !== '0' && !empty($showVal)) ? 1 : 0;

		$dashboardtitle = metafield_value($mf, 'dashboard_name');
		if($dashboardtitle === null || $dashboardtitle === '') $dashboardtitle = $node['title'];

		$productid = $node['legacyResourceId'];
		$shownProducts[$productid] = array('title' => $node['title'], 'show' => $show);

		echo '<h2>'.htmlspecialchars($node['title']).'</h2>';
		echo '<h3>Dashboard Title: '.htmlspecialchars($dashboardtitle).' - '.htmlspecialchars($productid).'</h3>';

		$sql = "INSERT INTO shopify_products (productid, variantid, title, dashboardtitle, showproduct)
		VALUES ('".$conn->real_escape_string($productid)."', '".$conn->real_escape_string($productid)."', '".$conn->real_escape_string($node['title'])."', '".$conn->real_escape_string($dashboardtitle)."', '".$show."')";
		$conn->query($sql);
		echo "<hr />";
	}

	$hasNext = !empty($products['pageInfo']['hasNextPage']);
	$cursor  = $products['pageInfo']['endCursor'] ?? null;
}while($hasNext && $cursor && ++$guard < 500);

/* ---------------------------------------------------------------------------
 * Pass 2 — variants. Insert a variant row for every variant that carries a
 * dashboard_name metafield AND whose product is on the dashboard (same as the
 * old script, where the variant loop lived inside the shown-product block).
 * ------------------------------------------------------------------------- */
$variantsQuery = <<<'GRAPHQL'
query($cursor: String) {
  productVariants(first: 40, after: $cursor) {
    pageInfo { hasNextPage endCursor }
    edges {
      node {
        legacyResourceId
        title
        product { legacyResourceId }
        metafields(first: 15) { edges { node { key value } } }
      }
    }
  }
}
GRAPHQL;

$cursor = null;
$guard = 0;
do{
	$res = wheeliams_shopify_gql($variantsQuery, array('cursor' => $cursor));
	$variants = $res['data']['productVariants'] ?? null;
	if(!$variants){
		echo '<p>Error loading variants: '.htmlspecialchars(json_encode($res['errors'] ?? $res)).'</p>';
		break;
	}

	foreach(($variants['edges'] ?? array()) as $edge){
		$node      = $edge['node'];
		$productid = $node['product']['legacyResourceId'] ?? '';
		if(!isset($shownProducts[$productid])) continue; // product not on the dashboard

		$mf = $node['metafields']['edges'] ?? array();
		if(!has_metafield($mf, 'dashboard_name')) continue; // no variant dashboard name

		$dashboardtitle = metafield_value($mf, 'dashboard_name');
		if($dashboardtitle === null || $dashboardtitle === '') $dashboardtitle = $node['title'];

		$variantid = $node['legacyResourceId'];
		$show      = $shownProducts[$productid]['show'];
		$title     = $shownProducts[$productid]['title'];

		echo htmlspecialchars($dashboardtitle).' - '.htmlspecialchars($variantid).'<br />';

		$sql = "INSERT INTO shopify_products (productid, variantid, title, dashboardtitle, showproduct)
		VALUES ('".$conn->real_escape_string($productid)."', '".$conn->real_escape_string($variantid)."', '".$conn->real_escape_string($title)."', '".$conn->real_escape_string($dashboardtitle)."', '".$show."')";
		$conn->query($sql);
	}

	$hasNext = !empty($variants['pageInfo']['hasNextPage']);
	$cursor  = $variants['pageInfo']['endCursor'] ?? null;
}while($hasNext && $cursor && ++$guard < 500);

$conn->close();
?>
