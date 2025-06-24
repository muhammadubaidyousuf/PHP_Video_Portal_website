<?php
// youtube_import_fetch.php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$apiKey = $data['apiKey'] ?? '';
$keyword = $data['keyword'] ?? '';
$dateFrom = $data['dateFrom'] ?? '';
$dateTo = $data['dateTo'] ?? '';
$count = (int)($data['count'] ?? 10);
$type = $data['type'] ?? 'all';
$channel = trim($data['channel'] ?? '');

if (!$apiKey) {
    echo json_encode(['error' => 'API key is required']);
    exit;
}
// If no keyword provided, we will fetch most popular (viral) videos

if ($keyword === '' && $channel !== '') {
    // Fetch latest videos from specific channel
    $params = [
        'part' => 'snippet',
        'channelId' => $channel,
        'type' => 'video',
        'maxResults' => min(max($count,1),50),
        'order' => 'date',
        'key' => $apiKey
    ];
    if ($dateFrom) $params['publishedAfter'] = date('c', strtotime($dateFrom));
    if ($dateTo) $params['publishedBefore'] = date('c', strtotime($dateTo.' 23:59:59'));
    $endpoint = 'https://www.googleapis.com/youtube/v3/search';
} elseif ($keyword === '') {
    // Fetch viral (most popular) videos
    $params = [
        'part' => 'snippet',
        'chart' => 'mostPopular',
        'maxResults' => min(max($count,1),50),
        'regionCode' => 'US',
        'key' => $apiKey
    ];
    // option to filter by channel later if provided - not supported for mostPopular
} else {
    // Build search API parameters
    $params = [
        'part' => 'snippet',
        'q' => $keyword,
        'type' => 'video',
        'maxResults' => min(max($count,1),50),
        'key' => $apiKey,
        'order' => 'viewCount',
    ];
    if ($dateFrom) $params['publishedAfter'] = date('c', strtotime($dateFrom));
    if ($dateTo) $params['publishedBefore'] = date('c', strtotime($dateTo.' 23:59:59'));
}
if ($channel && !preg_match('~^(UC[\w-]{21}[AQgw])$~',$channel)) {
    // Try to resolve username to channel ID
    $username = $channel;
    $resolveUrl = 'https://www.googleapis.com/youtube/v3/channels?part=id&forUsername=' . urlencode($username) . '&key=' . $apiKey;
    $chResolve = curl_init($resolveUrl);
    curl_setopt($chResolve, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chResolve, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($chResolve);
    curl_close($chResolve);
    if($resp){
        $j = json_decode($resp,true);
        if(isset($j['items'][0]['id'])){
            $channel = $j['items'][0]['id'];
        }
    }
    // Fallback: if still not a UC... id, try search API to find channelId by custom URL/handle
    if (!preg_match('~^UC[\w-]{21}[AQgw]$~', $channel)) {
        $searchUrl = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=channel&maxResults=1&q=' . urlencode($username) . '&key=' . $apiKey;
        $chS = curl_init($searchUrl);
        curl_setopt($chS, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chS, CURLOPT_SSL_VERIFYPEER, false);
        $sResp = curl_exec($chS);
        curl_close($chS);
        if($sResp){
            $sj = json_decode($sResp,true);
            if(isset($sj['items'][0]['snippet']['channelId'])){
                $channel = $sj['items'][0]['snippet']['channelId'];
            }
        }
    }
    if(isset($params['channelId'])) $params['channelId'] = $channel;
    }

if ($keyword !== '' && $channel) {
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
    if ($channel_id) {
        $params['channelId'] = $channel_id;
    }
}

if (!isset($endpoint)) {
    $endpoint = ($keyword === '') ? 'https://www.googleapis.com/youtube/v3/videos' : 'https://www.googleapis.com/youtube/v3/search';
}
$url = $endpoint . '?' . http_build_query($params);

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
    // When using mostPopular endpoint the id is at root level
    $vid = isset($item['id']['videoId']) ? $item['id']['videoId'] : ($item['id'] ?? null);
    if (!$vid) continue;
    $videoIds[] = $vid;
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
    $detailsUrl = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails&id=' . $ids . '&key=' . $apiKey;
    $ch = curl_init($detailsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $detailsResp = curl_exec($ch);
    curl_close($ch);
    $details = json_decode($detailsResp, true);
    $durations = [];
    $tagsMap = [];
    if (isset($details['items'])) {
        foreach ($details['items'] as $d) {
            $durations[$d['id']] = $d['contentDetails']['duration'];
            $tagsMap[$d['id']] = $d['snippet']['tags'] ?? [];
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
        $v['tags'] = $tagsMap[$v['video_id']] ?? [];
        if ($type === 'short' && $v['duration'] !== null && $v['duration'] < 60) $filtered[] = $v;
        if ($type === 'long' && $v['duration'] !== null && $v['duration'] >= 60) $filtered[] = $v;
    }
    unset($v);
    $videos = $filtered;
}

echo json_encode(['videos' => $videos]);
