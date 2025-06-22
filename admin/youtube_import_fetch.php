<?php
// youtube_import_fetch.php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$apiKey = $data['apiKey'] ?? '';
$tag = $data['tag'] ?? '';
$dateFrom = $data['dateFrom'] ?? '';
$dateTo = $data['dateTo'] ?? '';
$count = (int)($data['count'] ?? 10);
$type = $data['type'] ?? 'all';
$channel = trim($data['channel'] ?? '');

if (!$apiKey || !$tag) {
    echo json_encode(['error' => 'API key or tag is required']);
    exit;
}

// Build YouTube API URL
$params = [
    'part' => 'snippet',
    'q' => $tag,
    'type' => 'video',
    'maxResults' => min(max($count,1),50),
    'key' => $apiKey,
    'order' => 'date',
];
if ($dateFrom) $params['publishedAfter'] = date('c', strtotime($dateFrom));
if ($dateTo) $params['publishedBefore'] = date('c', strtotime($dateTo.' 23:59:59'));
if ($channel) {
    // Try to extract channel ID from URL or input
    $channel_id = '';
    // If full URL
    if (preg_match('~(UC[\w-]{21}[AQgw])~', $channel, $m)) {
        $channel_id = $m[1];
    } elseif (preg_match('~youtube\.com/(?:channel/)?(UC[\w-]{21}[AQgw])~i', $channel, $m)) {
        $channel_id = $m[1];
    } elseif (preg_match('~^(UC[\w-]{21}[AQgw])$~', $channel, $m)) {
        $channel_id = $channel;
    }
    if (!$channel_id) {
        echo json_encode(['error' => 'Please enter a valid YouTube Channel ID (starts with UC...)']);
        exit;
    }
    $params['channelId'] = $channel_id;
}

$url = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query($params);

// Use cURL for better error handling in HTTPS
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Disable SSL verify peer if local dev does not have certificates
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
if ($response === false) {
    $err = curl_error($ch);
    curl_close($ch);
    echo json_encode(['error' => 'YouTube API response fetch failed: '.$err]);
    exit;
}
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($statusCode !== 200) {
    echo json_encode(['error' => 'YouTube API HTTP status: '.$statusCode]);
    exit;
}

$json = json_decode($response, true);
if (isset($json['error'])) {
    echo json_encode(['error' => 'YouTube API error: '.($json['error']['message'] ?? 'Unknown')]);
    exit;
}
$videos = [];
$videoIds = [];
foreach ($json['items'] as $item) {
    if (!isset($item['id']['videoId'])) continue;
    $videoIds[] = $item['id']['videoId'];
    $snippet = $item['snippet'];
    $videos[] = [
        'video_id' => $item['id']['videoId'],
        'title' => $snippet['title'],
        'thumbnail' => $snippet['thumbnails']['medium']['url'],
        'channel' => $snippet['channelTitle'],
        'published_at' => date('Y-m-d', strtotime($snippet['publishedAt'])),
        'source' => 'youtube',
        'duration' => null // will fill below if needed
    ];
}

// If video type filter is set, fetch video durations
if (($type === 'short' || $type === 'long') && count($videoIds) > 0) {
    $ids = implode(',', $videoIds);
    $detailsUrl = 'https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id=' . $ids . '&key=' . $apiKey;
    $ch = curl_init($detailsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $detailsResp = curl_exec($ch);
    curl_close($ch);
    $details = json_decode($detailsResp, true);
    $durations = [];
    if (isset($details['items'])) {
        foreach ($details['items'] as $d) {
            $durations[$d['id']] = $d['contentDetails']['duration'];
        }
    }
    // ISO 8601 duration to seconds
    function yt_duration_to_seconds($dur) {
        if (!preg_match('/PT((\d+)H)?((\d+)M)?((\d+)S)?/', $dur, $m)) return 0;
        return ((int)($m[2]??0))*3600 + ((int)($m[4]??0))*60 + ((int)($m[6]??0));
    }
    // Filter videos
    $filtered = [];
    foreach ($videos as &$v) {
        $v['duration'] = isset($durations[$v['video_id']]) ? yt_duration_to_seconds($durations[$v['video_id']]) : null;
        if ($type === 'short' && $v['duration'] !== null && $v['duration'] < 60) $filtered[] = $v;
        if ($type === 'long' && $v['duration'] !== null && $v['duration'] >= 60) $filtered[] = $v;
    }
    unset($v);
    $videos = $filtered;
}

echo json_encode(['videos' => $videos]);
