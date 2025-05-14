<?php
$config = require 'config.php';
require 'helper.php';

$logfile = __DIR__ . '/history.log';

function log_history($message) {
    global $logfile;
    file_put_contents($logfile, date('c') . " - $message\n", FILE_APPEND);
}

if (empty($_POST['payload'])) {
    exit;
}

$payload = json_decode($_POST['payload'], true);
if (!$payload || empty($payload['event']) || empty($payload['Metadata'])) {
    exit;
}

$event = $payload['event'];
$meta = $payload['Metadata'];

// Uncomment the following lines to restrict access to specific account and server
// $account = $payload['Account'] ?? [];
// $server = $payload['Server'] ?? [];

// if (($server['title'] ?? '') !== 'PlexServerName' || ($account['title'] ?? '') !== 'PlexUserName') {
//     http_response_code(403);
//     exit("Unauthorized");
// }

if ($event === 'media.play' || $event === 'media.resume') {
    $progress = 0.1;
} elseif ($event === 'media.pause' || $event === 'media.stop') {
    $progress = 50.0;
} elseif ($event === 'media.scrobble') {
    $progress = 100.0;
} else {
    exit;
}

$type = $meta['type'] ?? '';
$title = trim($meta['title'] ?? '');
$year = (int)($meta['year'] ?? 0);

// Extract guids
$ids = [];
if (!empty($meta['Guid']) && is_array($meta['Guid'])) {
    foreach ($meta['Guid'] as $guid) {
        if (!isset($guid['id'])) continue;

        if (str_starts_with($guid['id'], 'imdb://')) {
            $ids['imdb'] = substr($guid['id'], 7);
        } elseif (str_starts_with($guid['id'], 'tmdb://')) {
            $ids['tmdb'] = (int)substr($guid['id'], 7);
        } elseif (str_starts_with($guid['id'], 'tvdb://')) {
            $ids['tvdb'] = (int)substr($guid['id'], 7);
        }
    }
}

$payload = [];

if ($type === 'movie') {
    $payload = [
        'movie' => array_filter([
            'title' => $title,
            'year' => $year,
            'ids' => $ids ?: null,
        ]),
        'progress' => $progress,
    ];
} elseif ($type === 'episode') {
    $payload = [
        'episode' => [
            'season' => (int)($meta['parentIndex'] ?? 1),
            'number' => (int)($meta['index'] ?? 1),
        ],
        'show' => array_filter([
            'title' => trim($meta['grandparentTitle'] ?? 'Unknown'),
            'year' => $year,
            'ids' => $ids ?: null,
        ]),
        'progress' => $progress,
    ];
} else {
    exit;
}

if ($progress >= 100.0) {
    $endpoint = $config['trakt_api_url'] . '/scrobble/stop';
} elseif ($progress >= 50.0) {
    $endpoint = $config['trakt_api_url'] . '/scrobble/pause';
} else {
    $endpoint = $config['trakt_api_url'] . '/scrobble/start';
}

$response = trakt_api_post($endpoint, $payload, $config);

$logEvent = str_replace('media.', '', $event);
log_history("Synced $type: $title ($year) with progress $progress% — Event: $logEvent");

echo "Synced $type with Trakt: $event";
?>
