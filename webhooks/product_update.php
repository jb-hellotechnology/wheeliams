<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/secrets.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/shopify-graphql.php';

$shared_secret = '7d5410a82702bcc867f0fbe27a6a6173f49148efb2e5a07b4d70a5075749ace8';

$hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
$data = file_get_contents('php://input');
$calculated_hmac = base64_encode(hash_hmac('sha256', $data, $shared_secret, true));

if ($hmac_header == $calculated_hmac) {
  // HMAC is valid, process the request
  $data = json_decode($data,true);
  createDescription($data['id']);
  //mail('jack@hellotechnology.co.uk', 'Webhook Triggered: Description Updated', $message);
  http_response_code(200);
} else {
  // HMAC is invalid, return a 401 response
  //mail('jack@hellotechnology.co.uk', 'Webhook Failed', $message);
  http_response_code(401);
}

function createDescription($id){

	$gid = wheeliams_shopify_gid('Product', $id);

	// Read the product's metafields via GraphQL, shaped like the old REST payload
	// ($details['metafields'] as a list of {key, value}) so the loop below is unchanged.
	$query = <<<'GRAPHQL'
query($id: ID!) {
  product(id: $id) {
    metafields(first: 100) { edges { node { key value } } }
  }
}
GRAPHQL;

	$res = wheeliams_shopify_gql($query, array('id' => $gid));

	$details = array('metafields' => array());
	foreach(($res['data']['product']['metafields']['edges'] ?? array()) as $edge){
		$details['metafields'][] = $edge['node'];
	}

	//print_r($details);
	
	$shortDescription = '';
	
	foreach($details['metafields'] as $metafield){
		if($metafield['key']=='short_product_description'){
			$data = json_decode($metafield['value'], true);
			foreach($data['children'] as $child){
				if($child['type']=='paragraph'){
					$shortDescription .= "<p>";	
					$i = 0;
					foreach($child['children'] as $item){
						if($item['bold']==1){
							$shortDescription .= "<strong>";	
						}
						if($item['type']=='text'){
							$shortDescription .= $item['value'];
						}
						if($item['type']=='link'){
							$shortDescription .= '<a href="'.$item['url'].'">'.$item['children'][0]['value'].'</a>';
						}
						$i++;
						if($item['bold']==1){
							$shortDescription .= "</strong>";	
						}
					}
					
					$shortDescription .= "</p>";
				}elseif($child['type']=='list'){
					$shortDescription .= "<ul>";
					foreach($child['children'] as $item){
						$shortDescription .= "<li>".$item['children'][0]['value']."</li>";
					}	
					$shortDescription .= "</ul>";
				}
			}
		}
		if($metafield['key']=='rich_text_vehicle_requirements'){
			$data = json_decode($metafield['value'], true);
			if($data['children']){
				$vehicleRequirements = "<h2>Vehicle Requirements</h2>";
			}
			foreach($data['children'] as $child){
				if($child['type']=='paragraph'){
					$fittingInstructions .= "<p>";
					$i = 0;
					foreach($child['children'] as $item){
						if($item['type']=='text'){
							$vehicleRequirements .= $item['value'];
						}
						if($item['type']=='link'){
							$vehicleRequirements .= '<a href="'.$item['url'].'">'.$item['children'][0]['value'].'</a>';
						}
						$i++;
					}
					$vehicleRequirements .= "</p>";
				}elseif($child['type']=='list'){
					$vehicleRequirements .= "<ul>";
					foreach($child['children'] as $item){
						$vehicleRequirements .= "<li>".$item['children'][0]['value']."</li>";
					}	
					$vehicleRequirements .= "</ul>";
				}
			}
		}
		if($metafield['key']=='rich_text_fitting_instructions'){
			$data = json_decode($metafield['value'], true);
			if($data['children']){
				$fittingInstructions = '<h2>Fitting Instructions</h2>';
			}
			foreach($data['children'] as $child){
				if($child['type']=='paragraph'){
					$fittingInstructions .= "<p>";	
					$i = 0;
					foreach($child['children'] as $item){
						if($item['bold']==1){
							$fittingInstructions .= "<strong>";	
						}
						if($item['type']=='text'){
							$fittingInstructions .= $item['value'];
						}
						if($item['type']=='link'){
							$fittingInstructions .= '<a href="'.$item['url'].'">'.$item['children'][0]['value'].'</a>';
						}
						$i++;
						if($item['bold']==1){
							$fittingDescription .= "</strong>";	
						}
					}
					
					$fittingInstructions .= "</p>";
				}elseif($child['type']=='list'){
					$fittingInstructions .= "<ul>";
					foreach($child['children'] as $item){
						$fittingInstructions .= "<li>".$item['children'][0]['value']."</li>";
					}	
					$fittingInstructions .= "</ul>";
				}
			}
		}
		if($metafield['key']=='rich_text_construction_details'){
			$data = json_decode($metafield['value'], true);
			if($data['children']){
				$constructionDetails .= '<h2>Construction</h2>';
			}
			foreach($data['children'] as $child){
				if($child['type']=='paragraph'){
					$fittingInstructions .= "<p>";
					$i = 0;
					foreach($child['children'] as $item){
						if($item['bold']==1){
							$constructionDetails .= "<strong>";	
						}
						if($item['type']=='text'){
							$constructionDetails .= $item['value'];
						}
						if($item['type']=='link'){
							$constructionDetails .= '<a href="'.$item['url'].'">'.$item['children'][0]['value'].'</a>';
						}
						$i++;
						if($item['bold']==1){
							$constructionDetails .= "</strong>";	
						}
					}
					
					$constructionDetails .= "</p>";
				}elseif($child['type']=='list'){
					$constructionDetails .= "<ul>";
					foreach($child['children'] as $item){
						$constructionDetails .= "<li>".$item['children'][0]['value']."</li>";
					}	
					$constructionDetails .= "</ul>";
				}
			}
		}
		if($metafield['key']=='rich_text_made_in_yorkshire'){
			$data = json_decode($metafield['value'], true);
			if($data['children']){
				$madeInYorkshire .= '<h2>Made in Yorkshire</h2>';
			}
			foreach($data['children'] as $child){
				if($child['type']=='paragraph'){
					$fittingInstructions .= "<p>";	
					$i = 0;
					foreach($child['children'] as $item){
						if($item['bold']==1){
							$madeInYorkshire .= "<strong>";	
						}
						if($item['type']=='text'){
							$madeInYorkshire .= $item['value'];
						}
						if($item['type']=='link'){
							$madeInYorkshire .= '<a href="'.$item['url'].'">'.$item['children'][0]['value'].'</a>';
						}
						$i++;
						if($item['bold']==1){
							$madeInYorkshire .= "</strong>";	
						}
					}
					
					$madeInYorkshire .= "</p>";
				}elseif($child['type']=='list'){
					$madeInYorkshire .= "<ul>";
					foreach($child['children'] as $item){
						$madeInYorkshire .= "<li>".$item['children'][0]['value']."</li>";
					}	
					$madeInYorkshire .= "</ul>";
				}
			}
		}
		if($metafield['key']=='lead_time'){
			$data = json_decode($metafield['value'], true);
			if($data['children']){
				$leadTime .= '<h2>Lead Time</h2>';
			}
			foreach($data['children'] as $child){
				if($child['type']=='paragraph'){
					$fittingInstructions .= "<p>";
					$i = 0;
					foreach($child['children'] as $item){
						if($item['bold']==1){
							$leadTime .= "<strong>";	
						}
						if($item['type']=='text'){
						$leadTime .= $item['value'];
						}
						if($item['type']=='link'){
							$leadTime .= '<a href="'.$item['url'].'">'.$item['children'][0]['value'].'</a>';
						}
						$i++;
						if($item['bold']==1){
							$leadTime .= "</strong>";	
						}
					}
					
					$leadTime .= "</p>";
				}elseif($child['type']=='list'){
					$leadTime .= "<ul>";
					foreach($child['children'] as $item){
						$leadTime .= "<li>".$item['children'][0]['value']."</li>";
					}	
					$leadTime .= "</ul>";
				}
			}
		}
	}
	
	// GraphQL variables handle JSON escaping, so no addslashes here (that would
	// inject literal backslashes into the stored HTML).
	$html = "$shortDescription $vehicleRequirements $fittingInstructions $constructionDetails $madeInYorkshire $leadTime";
	$html = str_replace(array("\r", "\n"), '', $html);

	// Write the combined description back onto the product (body_html == descriptionHtml).
	$mutation = <<<'GRAPHQL'
mutation($id: ID!, $html: String!) {
  productUpdate(input: {id: $id, descriptionHtml: $html}) {
    product { id }
    userErrors { field message }
  }
}
GRAPHQL;

	$data = wheeliams_shopify_gql($mutation, array('id' => $gid, 'html' => $html));

}