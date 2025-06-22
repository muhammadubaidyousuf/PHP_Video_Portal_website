<?php
// youtube_import_save.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$data = json_decode(file_get_contents('php://input'), true);
$videoData = $data['videoData'] ?? [];
$category_id = (int)($data['category'] ?? 0);

if (empty($videoData) || !$category_id) {
    echo json_encode(['error' => 'Some data is missing']);
    exit;
}

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
    $stmt = $conn->prepare("INSERT INTO videos (title, slug, source, video_id, thumbnail, category_id, status, created_at, updated_at) VALUES (?,?,?,?,?,?, 'active', NOW(), NOW())");
    $stmt->bind_param('sssssi', $v['title'], $slug, $v['source'], $v['video_id'], $v['thumbnail'], $category_id);
    if ($stmt->execute()) $added++;
    $stmt->close();
}
echo json_encode(['success' => "$added video(s) added successfully.", 'error' => '']);
