<?php
require_once 'vendor/autoload.php';

$client = new Google\Client();
$client->setClientId('960403879616-j40u5v8ol5v0mq9gkdkt2ec2ilj3e8ca.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://dashboard.wheeliams.co.uk/oauth2callback.php');
$client->addScope(Google\Service\Drive::DRIVE);
$client->setAccessType('offline');
$client->setPrompt('consent');

if (!isset($_GET['code'])) {
	header('Location: ' . $client->createAuthUrl());
	exit;
}

$client->fetchAccessTokenWithAuthCode($_GET['code']);
$token = $client->getAccessToken();

// Print the refresh token — copy and save this
echo '<pre>' . print_r($token, true) . '</pre>';