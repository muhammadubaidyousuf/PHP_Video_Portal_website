<?php
/**
 * YouTube Trend Bot – OMGTube
 *
 * Yeh CLI script har 6 ghante chalne ke liye design hai.
 * Kaam:
 * 1. Categories DB se uthaye.
 * 2. Har category ke liye YouTube Data API (v3) se trending / popular videos nikale.
 * 3. Naye videos ko videos table me insert kare (agar pehle se na hon).
 * 4. Title se tags nikaal-kar tags & video_tags tables me save kare.
 *
 * PHP 7.4+  –  CLI mode
 * Run example:
 *     php /absolute/path/cron/youtube_trend_bot.php
 */

// ==== CONFIGURATION =========================================================
// DB config include
require_once __DIR__ . '/../config/database.php';  // $conn mysqli object
require_once __DIR__ . '/../includes/functions.php';

// YouTube API key
$api_key = '';
$keyStmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key='youtube_api_key' LIMIT 1");
if ($keyStmt && $keyStmt->execute()) {
    $keyStmt->bind_result($api_key);
    $keyStmt->fetch();
    $keyStmt->close();
}
if (empty($api_key)) {
    echo "[Error] YouTube API key not found.\n";
    exit(1);
}

// ==== FUNCTIONS ============================================================
/**
 * YouTube search se popular/trending videos list karta hai.
 * @param string $query  – category ka naam
 * @param string $api_key
 * @param int    $maxResults
 * @return array<array>  – each item [id,title,thumbnail]
 */
function fetch_youtube_videos(string $query, string $api_key, int $maxResults = 10, string $region = 'US'): array {
    $url = sprintf(
        'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&maxResults=%d&regionCode=%s&order=viewCount&q=%s&key=%s',
        $maxResults,
        $region,
        urlencode($query . ' trending'),
        $api_key
    );

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (!isset($data['items'])) return [];

    $videos = [];
    foreach ($data['items'] as $item) {
        $videos[] = [
            'video_id'  => $item['id']['videoId'] ?? '',
            'title'     => $item['snippet']['title'] ?? '',
            'thumbnail' => $item['snippet']['thumbnails']['medium']['url'] ?? '',
        ];
    }
    return $videos;
}

// ==== MAIN ==================================================================
// runtime settings
$region      = get_setting('yt_region', 'US');
$maxResults  = (int) get_setting('yt_max_results', 15);
// CLI args can override if provided
if(isset($argv[1])) $region = $argv[1];
if(isset($argv[2])) $maxResults = (int)$argv[2];
if($maxResults<1||$maxResults>50){$maxResults=15;}
$insertedCount = 0;
$dateNow       = date('Y-m-d H:i:s');

// Categories fetch karo
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY id ASC");
if (!$catRes) {
    echo "DB error fetching categories\n";
    exit(1);
}

while ($cat = $catRes->fetch_assoc()) {
    $catId   = (int) $cat['id'];
    $catName = $cat['name'];
    echo "[*] Processing category: {$catName}\n";

    // YouTube se videos lao
    $ytVideos = fetch_youtube_videos($catName, $api_key, $maxResults, $region);
    foreach ($ytVideos as $v) {
        if (empty($v['video_id'])) continue;

        // Duplicate check
        $dupStmt = $conn->prepare("SELECT id FROM videos WHERE video_id = ? AND source = 'youtube' LIMIT 1");
        $dupStmt->bind_param('s', $v['video_id']);
        $dupStmt->execute();
        $dupStmt->store_result();
        if ($dupStmt->num_rows > 0) {
            $dupStmt->close();
            continue; // already exist
        }
        $dupStmt->close();

                // Prepare variables for bind_param (needs references)
        $title    = $v['title'];
        $src      = 'youtube';
        $videoId  = $v['video_id'];
        $thumb    = $v['thumbnail'];

        // Insert video
        $status     = 'active';
                $insertStmt = $conn->prepare("INSERT INTO videos (title, source, video_id, thumbnail, category_id, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)");
        $insertStmt->bind_param('ssssisss', $title, $src, $videoId, $thumb, $catId, $status, $dateNow, $dateNow);
        if ($insertStmt->execute()) {
            $videoDbId = $insertStmt->insert_id;
            $insertedCount++;
            echo "    [+] Added video: {$v['title']}\n";

            // Auto-tags (simple split by space, # remove) – optional
            $words = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}# ]+/u', '', $v['title'])));
            foreach ($words as $word) {
                $word = trim($word, "#\n\r\t ");
                if (mb_strlen($word) < 3) continue;
                $slug = create_slug($word);
                if (empty($slug)) continue;

                // tag exists?
                $tagId = 0;
                $tagSel = $conn->prepare("SELECT id FROM tags WHERE slug = ? LIMIT 1");
                $tagSel->bind_param('s', $slug);
                $tagSel->execute();
                $tagSel->bind_result($tagId);
                if (!$tagSel->fetch()) {
                    $tagSel->close();
                    // insert new tag
                    $tagIns = $conn->prepare("INSERT INTO tags (name, slug, created_at) VALUES (?,?,?)");
                    $tagIns->bind_param('sss', $word, $slug, $dateNow);
                    $tagIns->execute();
                    $tagId = $tagIns->insert_id;
                    $tagIns->close();
                } else {
                    $tagSel->close();
                }

                // link tag
                $link = $conn->prepare("INSERT IGNORE INTO video_tags (video_id, tag_id) VALUES (?,?)");
                $link->bind_param('ii', $videoDbId, $tagId);
                $link->execute();
                $link->close();
            }
        }
        $insertStmt->close();
    }
}

echo "\nTotal new videos added: {$insertedCount}\n";
$conn->close();
