<?php
// cron/youtube_trend_bot.php
// --------------------------------------------------
// This CLI script fetches YouTube trending videos and saves them
// Requires a cron / task-scheduler entry, e.g.
//    */30 * * * * php /path/to/cron/youtube_trend_bot.php
// --------------------------------------------------

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// 1. Read bot settings ------------------------------------------------------
$settings = [
    'enabled'      => true,
    'region'       => 'PK',
    'max_videos'   => 1,
    'last_run'     => null,
    'freq_minutes' => 1,
];
$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'trend_bot_%'");
while ($row = $res->fetch_assoc()) {
    $key = str_replace('trend_bot_', '', $row['setting_key']);
    $settings[$key] = $row['setting_value'];
}
if (!$settings['enabled']) {
    exit; // bot disabled
}

// Throttle : run only if freq_minutes passed
if ($settings['last_run']) {
    $next = strtotime($settings['last_run']) + ($settings['freq_minutes'] * 60);
    if (time() < $next) exit; // not yet time
}

// 2. Get YouTube API key -----------------------------------------------------
$apiKey = '';
$keyRes = $conn->query("SELECT setting_value FROM settings WHERE setting_key='youtube_api_key' LIMIT 1");
if ($keyRes && $keyRes->num_rows) {
    $apiKey = $keyRes->fetch_assoc()['setting_value'];
}
if (!$apiKey) {
    error_log('trend_bot: API key missing');
    exit;
}

    'howto & style'=>26,
    'education'=>27,
    'science & technology'=>28,
    'movies'=>30,
    'anime/animation'=>31,
    'action/adventure'=>32,
    'classics'=>33,
    'documentary'=>35,
];
$url = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query($params);
$ch  = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$resp = curl_exec($ch);
if ($resp === false) {
    error_log('trend_bot: curl error ' . curl_error($ch));
    curl_close($ch);
    exit;
}
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($http !== 200) {
    error_log('trend_bot: HTTP ' . $http);
    exit;
}
$json = json_decode($resp, true);
if (!isset($json['items'])) exit;

$added = 0;
foreach ($json['items'] as $item) {
    $vid  = $item['id'] ?? null;
    if (!$vid) continue;
    // duplicate check
    $stmt = $conn->prepare("SELECT id FROM videos WHERE video_id=? AND source='youtube' LIMIT 1");
    $stmt->bind_param('s', $vid);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows) { $stmt->close(); continue; }
    $stmt->close();

    $snippet = $item['snippet'];
    $title   = $snippet['title'];
    $thumb   = $snippet['thumbnails']['medium']['url'];
    $slug    = create_slug($title);

    // Attempt to map category via tags
    $video_cat_id = null;
    if (!empty($snippet['tags'])) {
        $tags = $snippet['tags'];
        // fetch category
        $catRes = $conn->query("SELECT id,name,slug FROM categories");
        $catMap = [];
        while($r=$catRes->fetch_assoc()) {
            $catMap[strtolower($r['name'])] = $r['id'];
            $catMap[strtolower($r['slug'])] = $r['id'];
        }
        foreach ($tags as $t) {
            $lk = strtolower($t);
            if (isset($catMap[$lk])) { $video_cat_id = $catMap[$lk]; break; }
        }
    }

    // Prepare insert statement with correct parameter types
    $stmt = $conn->prepare("INSERT INTO videos (title, slug, source, video_id, thumbnail, category_id, status, created_at, updated_at) VALUES (?,?,?,?,?,?, 'active', NOW(), NOW())");
    // title, slug, source, video_id, thumbnail = string, category_id = int
    $src = 'youtube';
    $stmt->bind_param('sssssi', $title, $slug, $src, $vid, $thumb, $video_cat_id);
    if (!$stmt->execute()) {
        // Log any DB error for debugging
        error_log('trend_bot DB insert error: '.$stmt->error);
    } else {
        $video_id_db = $conn->insert_id;
        $added++;
        // save tags
        if (!empty($snippet['tags'])) {
            foreach($snippet['tags'] as $tag){
                $tag_slug = create_slug($tag);
                $tag_id=0;
                $tStmt=$conn->prepare("SELECT id FROM tags WHERE slug=? LIMIT 1");
                $tStmt->bind_param('s',$tag_slug);
                $tStmt->execute();
                $tStmt->bind_result($tag_id);
                if(!$tStmt->fetch()){
                    $tStmt->close();
                    $ins=$conn->prepare("INSERT INTO tags (name,slug,created_at) VALUES (?,?,NOW())");
                    $ins->bind_param('ss',$tag,$tag_slug);
                    $ins->execute();
                    $tag_id=$conn->insert_id;
                    $ins->close();
                } else { $tStmt->close(); }
                $conn->query("INSERT IGNORE INTO video_tags (video_id,tag_id) VALUES ($video_id_db,$tag_id)");
            }
        }
    }
    $stmt->close();
}

// 4. Update last_run --------------------------------------------------------
$now = date('Y-m-d H:i:s');
$upd = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('trend_bot_last_run', ?) ");
$upd->bind_param('s', $now);
$upd->execute();
$upd->close();
// store last added count
$cntStmt = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('trend_bot_last_added', ?)");
$cntStmt->bind_param('s', $added);
$cntStmt->execute();
$cntStmt->close();

echo "trend_bot added $added videos at $now\n";
