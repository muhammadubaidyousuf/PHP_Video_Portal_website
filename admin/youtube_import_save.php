<?php
// youtube_import_save.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$data = json_decode(file_get_contents('php://input'), true);
$videoData = $data['videoData'] ?? [];
$category_id_global = (int)($data['category'] ?? 0);

if (empty($videoData)) {
    echo json_encode(['error' => 'No videos provided']);
    exit;
}

// Fetch categories map (lowercase name=>id)
$catMap = [];
$catRes = $conn->query("SELECT id,name,slug FROM categories");
while($row=$catRes->fetch_assoc()){ $catMap[strtolower($row['name'])] = $row['id']; $catMap[strtolower($row['slug'])]=$row['id']; }

$added = 0;
foreach ($videoData as $v) {
    $v = json_decode(urldecode($v), true);
    if (!$v || empty($v['video_id']) || empty($v['title']) || empty($v['thumbnail'])) continue;
    // Check if already exists
    $stmt = $conn->prepare("SELECT id FROM videos WHERE video_id=? AND source='youtube'");
    $stmt->bind_param('s', $v['video_id']);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) continue; // skip duplicates
    $stmt->close();
    // Insert
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $v['title']));
        // Determine category
    $video_cat_id = $category_id_global;
    if ($video_cat_id === 0 && isset($v['tags']) && is_array($v['tags'])) {
        foreach($v['tags'] as $t){
            $key = strtolower($t);
            if(isset($catMap[$key])){ $video_cat_id = $catMap[$key]; break; }
        }
    }
    if($video_cat_id===0) $video_cat_id = null;
    $stmt = $conn->prepare("INSERT INTO videos (title, slug, source, video_id, thumbnail, category_id, status, created_at, updated_at) VALUES (?,?,?,?,?,?, 'active', NOW(), NOW())");
    $stmt->bind_param('ssssis', $v['title'], $slug, $v['source'], $v['video_id'], $v['thumbnail'], $video_cat_id);
    if ($stmt->execute()) {
        $video_insert_id = $conn->insert_id;
        $added++;
        // Handle tags
        if(isset($v['tags']) && is_array($v['tags'])){
            foreach($v['tags'] as $tag){
                $tag_slug = create_slug($tag);
                $tag_id = 0;
                $tagStmt = $conn->prepare("SELECT id FROM tags WHERE slug=? LIMIT 1");
                $tagStmt->bind_param('s',$tag_slug);
                $tagStmt->execute();
                $tagStmt->bind_result($tag_id);
                if(!$tagStmt->fetch()){
                    $tagStmt->close();
                    $insTag = $conn->prepare("INSERT INTO tags (name,slug,created_at) VALUES (?,?,NOW())");
                    $insTag->bind_param('ss',$tag,$tag_slug);
                    $insTag->execute();
                    $tag_id = $conn->insert_id;
                    $insTag->close();
                } else { $tagStmt->close(); }
                $conn->query("INSERT IGNORE INTO video_tags (video_id,tag_id) VALUES ($video_insert_id,$tag_id)");
            }
        }
    }
    $stmt->close();
}
echo json_encode(['success' => "$added video(s) added successfully.", 'error' => '']);
