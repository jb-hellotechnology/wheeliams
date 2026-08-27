<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/secrets.php'; if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php

if(!perch_member_logged_in()){
	header("location:/sign-in/");
}	

$isAdmin = perch_member_has_tag('admin');
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

// Track rows hidden from the Jobs List.
$conn->query("CREATE TABLE IF NOT EXISTS hidden_items (orderid VARCHAR(191), item VARCHAR(191))");

// ── Open orders via the Shopify GraphQL Admin API ──────────────────────────────
// (Shopify has deprecated the REST Admin API.) The access token comes from the
// shared helper, which mints it from the Dev Dashboard app's client-credentials
// grant (cached ~24h) — see /shopify-graphql.php.
include_once $_SERVER['DOCUMENT_ROOT'].'/shopify-graphql.php';
$shopifyStore   = defined('WHEELIAMS_SHOPIFY_STORE') ? WHEELIAMS_SHOPIFY_STORE : 'wheeliamsltd.myshopify.com';
$shopifyVersion = defined('WHEELIAMS_SHOPIFY_API_VERSION') ? WHEELIAMS_SHOPIFY_API_VERSION : '2025-01';
$shopifyError   = null;

function shopify_graphql($store, $version, $query, $variables = array()){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://'.$store.'/admin/api/'.$version.'/graphql.json');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('query' => $query, 'variables' => $variables)));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'X-Shopify-Access-Token: '.wheeliams_shopify_access_token(),
		'Content-Type: application/json',
	));
	$body = curl_exec($ch);
	curl_close($ch);
	return json_decode($body, true);
}

$ordersQuery = <<<'GQL'
query($cursor: String) {
  orders(first: 15, after: $cursor, query: "status:open", sortKey: CREATED_AT) {
    pageInfo { hasNextPage endCursor }
    edges {
      node {
        name
        createdAt
        lineItems(first: 50) {
          edges {
            node {
              id
              name
              sku
              quantity
              unfulfilledQuantity
              product { legacyResourceId }
              variant { legacyResourceId }
            }
          }
        }
      }
    }
  }
}
GQL;

$result = array();
$cursor = null;
do {
	$resp = shopify_graphql($shopifyStore, $shopifyVersion, $ordersQuery, array('cursor' => $cursor));
	if(!empty($resp['errors'])){ $shopifyError = $resp['errors']; break; }
	$ordersConn = $resp['data']['orders'] ?? null;
	if(!$ordersConn){ break; }

	foreach($ordersConn['edges'] as $edge){
		$o = $edge['node'];
		$lineItems = array();
		foreach(($o['lineItems']['edges'] ?? array()) as $le){
			$li  = $le['node'];
			$gid = $li['id'] ?? '';
			$lineItems[] = array(
				// Keep the numeric legacy id so existing order_items / hidden_items rows still match.
				'id'                   => $gid !== '' ? substr($gid, strrpos($gid, '/') + 1) : '',
				'title'                => $li['name'] ?? '',
				'sku'                  => $li['sku'] ?? '',
				'fulfillable_quantity' => (int)($li['unfulfilledQuantity'] ?? 0),
				'product_id'           => $li['product']['legacyResourceId'] ?? '',
				'variant_id'           => $li['variant']['legacyResourceId'] ?? '',
			);
		}
		$result[] = array(
			'name'       => $o['name'],
			'created_at' => $o['createdAt'],
			'line_items' => $lineItems,
		);
	}

	$hasNext = $ordersConn['pageInfo']['hasNextPage'] ?? false;
	$cursor  = $ordersConn['pageInfo']['endCursor'] ?? null;
} while($hasNext);

$count = count($result);
//print_r($result);

function getProductDB($conn,$id){
	
	if($id<>''){
		$sql = "SELECT * FROM shopify_products WHERE productid=".$id;
		$result = $conn->query($sql);
		if ($result->num_rows == 1) {
			// output data of each row
			while($row = $result->fetch_assoc()) {
				return $row;
			}
		}
	}
	
}

function getVariantDB($conn,$id){
	
	if($id<>''){
		$sql = "SELECT * FROM shopify_products WHERE variantid=".$id;
		$result = $conn->query($sql);
		if ($result->num_rows == 1) {
			// output data of each row
			while($row = $result->fetch_assoc()) {
				return $row;
			}
		}
	}
	
}

function getItemStatus($conn,$order,$id){
	$sql = "SELECT * FROM order_items WHERE orderid='".$order."' AND item='".$id."'";
	$result = $conn->query($sql);
	if ($result->num_rows == 1) {
		return true;
	}else{
		return false;
	}
}

function getItemHidden($conn,$order,$id){
	$sql = "SELECT * FROM hidden_items WHERE orderid='".$order."' AND item='".$id."'";
	$result = $conn->query($sql);
	return ($result->num_rows == 1);
}

?>
<?php
perch_layout('header');
?>
<main class="full">
	<h1>Dashboard</h1>
	<?php
	if($shopifyError){
		echo '<p class="alert warning">Unable to load orders from Shopify — check the Admin API token in secrets.php.';
		if($isAdmin){ echo ' <small>'.htmlspecialchars(substr(json_encode($shopifyError), 0, 300)).'</small>'; }
		echo '</p>';
	}
	?>
	<section>
		<header class="tabs">
			<ul>
				<li><button data-tab="jobs" class="active">Jobs List</button></li>
				<li><button data-tab="items">Items Required</button></li>
					<?php if($isAdmin){ echo '<li><button data-tab="hidden">Hidden</button></li>'; } ?>
			</ul>
		</header>
		<article>
			<div class="tab jobs">
			<div class="table-container">
                <table class="">
                  <thead>
                    <tr>
                      <th>Items</th>
                      <th>Quantity</th>
                      <th>Complete</th>
                      <?php if($isAdmin){ echo '<th>Hide</th>'; } ?>
                      <th>Order</th>
                      <th>Order Date</th>
                    </tr>
                  </thead>
                  <?php
										  // ── NEW: accumulator for the item totals summary ──
										  $itemTotals = [];
										  $itemSkus = []; // label => SKU, for BOM links
										  $hiddenRows = []; // rows hidden from the Jobs List
				  
										  foreach($result as $Order){
											  $show = false;
				  
											  foreach($Order['line_items'] as $item){
												  $productData = getProductDB($conn,$item['product_id']);
												  $variantData = getVariantDB($conn,$item['variant_id']);
												  if($productData['showproduct']!==0 OR $variantData['showproduct']!==0){
													  $show = true;
												  }
											  }
				  
											  if($show){
												  $dates = explode("T", $Order['created_at']);
												  $dates = explode("-", $dates[0]);
												  $date = "$dates[2]/$dates[1]/$dates[0]";
				  
												  if(count($Order['line_items'])>0){
													  $y = 0;
													  $i = 1;
													  foreach($Order['line_items'] as $item){
														  $productData = getProductDB($conn,$item['product_id']);
														  $variantData = getVariantDB($conn,$item['variant_id']);
				  
														  if($productData['showproduct']!=='0' AND $item['fulfillable_quantity']>0){
															  if($i>0){
				  
																  // ── Store status once, reuse below ──
																  $isComplete = getItemStatus($conn,$Order['name'],$item['id']);
				  
																  // ── Resolve the display label the same way the table does ──
																  if($variantData){
																	  $label = $variantData['dashboardtitle'];
																  } elseif($productData){
																	  $label = $productData['dashboardtitle'];
																  } else {
																	  $label = $item['title'];
																  }
				  
																  // Hidden rows: collect for the Hidden tab and skip both lists.
																  if(getItemHidden($conn,$Order['name'],$item['id'])){
																  	$hiddenRows[] = array('label'=>$label,'qty'=>$item['fulfillable_quantity'],'order'=>$Order['name'],'item'=>$item['id'],'sku'=>($item['sku'] ?? ''),'date'=>$date);
																  	continue;
																  }

																  // ── Only count items that are NOT struck through ──
																  if(!$isComplete){
																	  if(!isset($itemTotals[$label])){
																		  $itemTotals[$label] = 0;
																	  }
																	  $itemTotals[$label] += $item['fulfillable_quantity'];
																	  $itemSkus[$label] = $item['sku'] ?? '';
																  }
				  
																  echo '<tr class="';
																  if($isComplete){ echo 'strike'; }
																  if($y==0){ echo ' first-line'; }
																  echo '">
																	  <td>';
																		  $bomLink = wheeliams_bom_link_for_sku($item['sku'] ?? '');
																			  if($bomLink){
																				  echo '<p><a href="'.$bomLink.'">'.$label.'</a></p>';
																			  }else{
																				  echo '<p>'.$label.'</p>';
																			  }
																	  echo '</td>
																	  <td>
																		  <p>'.$item['fulfillable_quantity'].'</p>
																	  </td>
																	  <td>
																		  <input type="checkbox" class="complete"
																			  data-order-id="'.$Order['name'].'"
																			  data-item-id="'.$item['id'].'"
																			  data-sku="'.htmlspecialchars($item['sku'] ?? '').'"
																			  data-qty="'.($item['fulfillable_quantity'] ?? 0).'" ';
																		  if($isComplete){ echo 'CHECKED'; }
																	  echo ' />
																	  </td>
																	  '.($isAdmin ? '<td><a class="hide" href="javascript:;" data-order-id="'.$Order['name'].'" data-item-id="'.$item['id'].'">Hide</a></td>' : '').'
																	  <td>
																		  <div>'.$Order['name'].'</div>
																	  </td>
																	  <td>'.$date.'</td>
																  </tr>';
				  
																  $y++;
															  }
														  }
														  $i++;
													  }
												  }
											  }
										  }
										  ?>
									  </tbody>
								  </table>
							  </div>
			</div>
			<div class="tab items">

					  <div class="table-container">
						  <table>
							  <thead>
								  <tr>
									  <th>Items</th>
									  <th>Quantity Required</th>
								  </tr>
							  </thead>
							  <tbody>
								  <?php
								  // Optional: sort alphabetically so it's easy to scan
								  ksort($itemTotals);
								  foreach($itemTotals as $label => $qty):
									  $bomLink = wheeliams_bom_link_for_sku($itemSkus[$label] ?? ''); ?>
								  <tr>
									  <td><?php
										  if($bomLink){
											  echo '<a href="'.$bomLink.'">'.htmlspecialchars($label).'</a>';
										  }else{
											  echo htmlspecialchars($label);
										  }
									  ?></td>
									  <td><?php echo $qty; ?></td>
								  </tr>
								  <?php endforeach; ?>
							  </tbody>
						  </table>
					  </div>

			</div>
			<?php if($isAdmin): ?>
			<div class="tab hidden">
				<div class="table-container">
					<table class="">
						<thead>
							<tr>
								<th>Items</th>
								<th>Quantity</th>
								<th>Order</th>
								<th>Order Date</th>
								<th>Hidden</th>
							</tr>
						</thead>
						<tbody>
							<?php if($hiddenRows){ foreach($hiddenRows as $hr): $hbom = wheeliams_bom_link_for_sku($hr['sku']); ?>
							<tr>
								<td><?php if($hbom){ echo '<a href="'.$hbom.'">'.$hr['label'].'</a>'; }else{ echo $hr['label']; } ?></td>
								<td><?php echo $hr['qty']; ?></td>
								<td><?php echo $hr['order']; ?></td>
								<td><?php echo $hr['date']; ?></td>
								<td><a href="javascript:;" class="hide" data-order-id="<?php echo $hr['order']; ?>" data-item-id="<?php echo $hr['item']; ?>">Un-hide</a></td>
							</tr>
							<?php endforeach; }else{ ?>
							<tr><td colspan="5">No hidden items.</td></tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
						  <?php endif; ?>
						  </article>
	</section>
				  </main>
				  <script>
					  document.querySelectorAll('.tabs button').forEach(button => {
						  button.addEventListener('click', () => {
							  const target = button.dataset.tab;
					  
							  document.querySelectorAll('.tabs button').forEach(btn => btn.classList.remove('active'));
							  button.classList.add('active');
					  
							  document.querySelectorAll('.tab').forEach(tab => {
								  tab.style.display = tab.classList.contains(target) ? 'block' : 'none';
							  });
						  });
					  });
					  
					  // Show the first tab by default
					  document.querySelector('.tabs button').click();
				  </script>
				  
				  <?php perch_layout('footer'); ?>