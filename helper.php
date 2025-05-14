<?php

function get_tokens() {
    return json_decode(file_get_contents(__DIR__ . '/tokens.json'), true);
}

function save_tokens($tokens) {
    file_put_contents(__DIR__ . '/tokens.json', json_encode($tokens));
}

function refresh_token($config) {
    $tokens = get_tokens();
    $post = [
        'refresh_token' => $tokens['refresh_token'],
        'client_id' => $config['trakt_client_id'],
        'client_secret' => $config['trakt_client_secret'],
        'redirect_uri' => $config['trakt_redirect_uri'],
        'grant_type' => 'refresh_token',
    ];

    $ch = curl_init($config['trakt_oauth_url'] . '/token');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $new_tokens = json_decode($response, true);
    if (isset($new_tokens['access_token'])) {
        save_tokens($new_tokens);
        return $new_tokens;
    }
    return false;
}

function trakt_api_post($url, $payload, $config) {
    $tokens = get_tokens();
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $tokens['access_token'],
        "trakt-api-version: 2",
        "trakt-api-key: " . $config['trakt_client_id'],
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 401) {
        // Token expired, try refresh
        if ($new = refresh_token($config)) {
            return trakt_api_post($url, $payload, $config);
        }
    }

    return $response;
}
