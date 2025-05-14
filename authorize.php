<?php
$config = require 'config.php';

if (!isset($_GET['code'])) {
    $url = $config['trakt_oauth_url'] . "/authorize?response_type=code&client_id={$config['trakt_client_id']}&redirect_uri={$config['trakt_redirect_uri']}";
    echo "Authorize Trakt: <a href='$url'>$url</a>";
    exit;
}

$code = $_GET['code'];

$data = [
    'code' => $code,
    'client_id' => $config['trakt_client_id'],
    'client_secret' => $config['trakt_client_secret'],
    'redirect_uri' => $config['trakt_redirect_uri'],
    'grant_type' => 'authorization_code',
];

$ch = curl_init($config['trakt_oauth_url'] . '/token');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

file_put_contents('tokens.json', $response);
echo "Access token saved!";
