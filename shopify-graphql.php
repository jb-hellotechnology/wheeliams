<?php
/*
 * Shared Shopify GraphQL Admin API helper for the standalone scripts and the
 * Shopify webhook (products.php, metafields/*, webhooks/product_update.php).
 * The Perch app itself uses wheeliams_shopify_graphql() in the app runtime.
 *
 * Store + token come from secrets.php (kept out of the repo). The REST Admin
 * API is deprecated; everything here talks to /admin/api/{version}/graphql.json.
 */

if(!function_exists('wheeliams_shopify_gql')){

	/* Ensure the Shopify config constants from secrets.php are loaded. */
	function wheeliams_shopify_config(){
		if(!defined('WHEELIAMS_SHOPIFY_STORE') && is_file($_SERVER['DOCUMENT_ROOT'].'/secrets.php')){
			include_once $_SERVER['DOCUMENT_ROOT'].'/secrets.php';
		}
		if(!defined('WHEELIAMS_SHOPIFY_STORE')) define('WHEELIAMS_SHOPIFY_STORE', 'wheeliamsltd.myshopify.com');
	}

	/*
	 * A valid Admin API access token for the store.
	 *
	 * Primary path is the Dev Dashboard app's CLIENT CREDENTIALS grant: POST the
	 * app's client id + secret to /admin/oauth/access_token and get a token that
	 * lasts ~24h (only works because app + store share one Shopify org). The token
	 * is cached in the system temp dir until ~5 min before expiry, so we're not
	 * minting one on every request. A legacy static WHEELIAMS_SHOPIFY_TOKEN, if
	 * set, is used as a fallback when client credentials aren't configured or fail.
	 */
	function wheeliams_shopify_access_token(){
		wheeliams_shopify_config();
		$store        = WHEELIAMS_SHOPIFY_STORE;
		$clientId     = defined('WHEELIAMS_SHOPIFY_CLIENT_ID')     ? WHEELIAMS_SHOPIFY_CLIENT_ID     : '';
		$clientSecret = defined('WHEELIAMS_SHOPIFY_CLIENT_SECRET') ? WHEELIAMS_SHOPIFY_CLIENT_SECRET : '';
		$static       = defined('WHEELIAMS_SHOPIFY_TOKEN')         ? WHEELIAMS_SHOPIFY_TOKEN         : '';

		if($clientId !== '' && $clientSecret !== ''){
			$cacheFile = sys_get_temp_dir().'/wheeliams_shopify_token_'.md5($store.'|'.$clientId).'.json';

			// Reuse the cached token while it still has >5 min of life.
			if(is_file($cacheFile)){
				$c = json_decode(@file_get_contents($cacheFile), true);
				if(is_array($c) && !empty($c['access_token']) && !empty($c['expires_at']) && ($c['expires_at'] - 300) > time()){
					return $c['access_token'];
				}
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://'.$store.'/admin/oauth/access_token');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
				'grant_type'    => 'client_credentials',
				'client_id'     => $clientId,
				'client_secret' => $clientSecret,
			)));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/x-www-form-urlencoded',
				'Accept: application/json',
			));
			$body = curl_exec($ch);
			curl_close($ch);
			$data = json_decode($body, true);

			if(is_array($data) && !empty($data['access_token'])){
				$expiresIn = isset($data['expires_in']) ? (int)$data['expires_in'] : 86399;
				@file_put_contents($cacheFile, json_encode(array(
					'access_token' => $data['access_token'],
					'expires_at'   => time() + $expiresIn,
				)), LOCK_EX);
				@chmod($cacheFile, 0600);
				return $data['access_token'];
			}
			// fell through — client credentials failed; try the static fallback below
		}

		return $static; // '' if unset → calls fail loudly
	}

	function wheeliams_shopify_gql($query, $variables = array()){
		wheeliams_shopify_config();
		$version = defined('WHEELIAMS_SHOPIFY_API_VERSION') ? WHEELIAMS_SHOPIFY_API_VERSION : '2025-01';
		$token   = wheeliams_shopify_access_token();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://'.WHEELIAMS_SHOPIFY_STORE.'/admin/api/'.$version.'/graphql.json');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		// Cast variables to an object so an empty set encodes as {} not [] (Shopify rejects []).
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('query' => $query, 'variables' => (object)$variables)));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'X-Shopify-Access-Token: '.$token,
		));
		$body = curl_exec($ch);
		if(curl_errno($ch)){
			$err = curl_error($ch);
			curl_close($ch);
			return array('errors' => array(array('message' => $err)));
		}
		curl_close($ch);
		return json_decode($body, true);
	}

	/* Numeric Shopify id → GraphQL global id, e.g. gid://shopify/Product/123. */
	function wheeliams_shopify_gid($type, $id){
		return 'gid://shopify/'.$type.'/'.$id;
	}
}
