<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where = ["v.status = 'active'"];
$params = [];
$types = '';

// Search filter
if (isset($_POST['search']) && !empty(trim($_POST['search']))) {
    $search = '%' . trim($_POST['search']) . '%';
    $where[] = '(v.title LIKE ? OR c.name LIKE ?)';
    $params[] = $search;
    $params[] = $search;
    $types .= 'ss';
}

// Category filter
if (isset($_POST['category']) && is_numeric($_POST['category'])) {
    $where[] = 'v.category_id = ?';
    $params[] = (int)$_POST['category'];
    $types .= 'i';
}

// Combine where clauses
$where_clause = implode(' AND ', $where);

// Get videos
$query = "SELECT v.*, c.name as category_name,
          LOWER(REPLACE(c.name, ' ', '-')) as category_slug,
          LOWER(REPLACE(REPLACE(v.title, ' ', '-'), '.', '-')) as slug
          FROM videos v 
          LEFT JOIN categories c ON v.category_id = c.id 
          WHERE {$where_clause}
          ORDER BY v.created_at DESC 
          LIMIT ?, ?";

$stmt = $conn->prepare($query);

// Add pagination parameters
$params[] = $offset;
$params[] = $per_page;
$types .= 'ii';

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$videos = [];
while ($video = $result->fetch_assoc()) {
    // Generate thumbnail URL
    $thumbnail = '';
    if ($video['source'] == 'youtube') {
        $thumbnail = "https://img.youtube.com/vi/{$video['video_id']}/mqdefault.jpg";
    } elseif ($video['source'] == 'tiktok' && !empty($video['thumbnail'])) {
        $thumbnail = "uploads/thumbnails/{$video['thumbnail']}";
    }

    // Clean slugs using helper
    $category_slug = create_slug($video['category_name']);
    $video_slug    = create_slug($video['title']);

    $videos[] = [
        'id' => $video['id'],
        'title' => $video['title'],
        'category_name' => $video['category_name'],
        'category_slug' => $category_slug,
        'video_slug' => $video_slug,
        'friendly_url' => get_video_url(['id'=>$video['id'],'slug'=>$video_slug,'category_slug'=>$category_slug]),
        'source' => $video['source'],
        'video_id' => $video['video_id'],  // Add the video_id for embedding
        'thumbnail' => $thumbnail,
        'created_at' => date('M d, Y', strtotime($video['created_at']))
    ];
}

// Get total count with filters
$count_query = "SELECT COUNT(*) as total 
               FROM videos v 
               LEFT JOIN categories c ON v.category_id = c.id 
               WHERE {$where_clause}";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    // Remove pagination parameters
    array_pop($params);
    array_pop($params);
    $count_types = substr($types, 0, -2);
    if (!empty($count_types)) {
        $count_stmt->bind_param($count_types, ...$params);
    }
}
$count_stmt->execute();
$total_videos = $count_stmt->get_result()->fetch_assoc()['total'];
$has_more = ($page * $per_page) < $total_videos;

echo json_encode([
    'videos' => $videos,
    'has_more' => $has_more
]);
