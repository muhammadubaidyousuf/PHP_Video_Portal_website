<?php
// Include session
require_once '../includes/session.php';

// Handle video deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $video_id = (int)$_GET['id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get video details first (for thumbnail deletion)
        $query = "SELECT thumbnail FROM videos WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $video_id);
        $stmt->execute();
        $video = $stmt->get_result()->fetch_assoc();
        
        // Delete video tags first (foreign key constraint)
        $query = "DELETE FROM video_tags WHERE video_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $video_id);
        $stmt->execute();
        
        // Delete the video
        $query = "DELETE FROM videos WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $video_id);
        $stmt->execute();
        
        // Delete thumbnail if exists
        if ($video && !empty($video['thumbnail'])) {
            $thumbnail_path = "../uploads/thumbnails/" . $video['thumbnail'];
            if (file_exists($thumbnail_path)) {
                unlink($thumbnail_path);
            }
        }
        
        // Commit transaction
        $conn->commit();
        set_message('Video deleted successfully!');
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        set_message('Error deleting video: ' . $e->getMessage(), 'danger');
    }
    
    // Redirect back to videos page
    header('Location: videos.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter_source = isset($_GET['source']) ? sanitize($_GET['source']) : '';
$filter_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "v.title LIKE ?";
    $params[] = "%$search%";
    $param_types .= 's';
}

if (!empty($filter_source)) {
    $where_conditions[] = "v.source = ?";
    $params[] = $filter_source;
    $param_types .= 's';
}

if ($filter_category > 0) {
    $where_conditions[] = "v.category_id = ?";
    $params[] = $filter_category;
    $param_types .= 'i';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Count total records
$count_query = "SELECT COUNT(*) as total FROM videos v $where_clause";
$stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get videos
$query = "SELECT v.*, c.name as category_name 
          FROM videos v 
          LEFT JOIN categories c ON v.category_id = c.id 
          $where_clause
          ORDER BY v.created_at DESC 
          LIMIT ?, ?";

$stmt = $conn->prepare($query);
$param_types .= 'ii';
$params[] = $offset;
$params[] = $records_per_page;
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$videos = $stmt->get_result();

// Get categories for filter
$categories = get_categories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videos - OMGTube</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #4e73df;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .video-thumbnail {
            width: 120px;
            height: 68px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="d-flex flex-column p-3">
                    <a href="dashboard.php" class="d-flex align-items-center mb-3 text-decoration-none text-white">
                        <i class="fas fa-video me-2"></i>
                        <h4 class="mb-0">OMGTube</h4>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="videos.php" class="nav-link active">
                                <i class="fas fa-film"></i> Videos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="youtube_import.php" class="nav-link">
                                <i class="fab fa-youtube"></i> YouTube Import
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="add_video.php" class="nav-link">
                                <i class="fas fa-plus-circle"></i> Add Video
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="categories.php" class="nav-link">
                                <i class="fas fa-tags"></i> Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="profile.php" class="nav-link">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <?php include 'settings_menu_partial.php'; ?>
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2 fs-5"></i>
                            <strong><?php echo $_SESSION['full_name']; ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-film me-2"></i>Videos</h2>
                    <a href="add_video.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Add New Video
                    </a>
                </div>
                
                <?php display_message(); ?>
                
                <!-- Search and Filter -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search videos..." value="<?php echo $search; ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <select class="form-select" name="source">
                                    <option value="">All Sources</option>
                                    <option value="youtube" <?php echo $filter_source == 'youtube' ? 'selected' : ''; ?>>YouTube</option>
                                    <option value="tiktok" <?php echo $filter_source == 'tiktok' ? 'selected' : ''; ?>>TikTok</option>
                                    <option value="facebook" <?php echo $filter_source == 'facebook' ? 'selected' : ''; ?>>Facebook</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <select class="form-select" name="category">
                                    <option value="0">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $filter_category == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo $category['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Videos List -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Videos (<?php echo $total_records; ?>)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Thumbnail</th>
                                        <th>Title</th>
                                        <th>Source</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Date Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($videos->num_rows > 0): ?>
                                        <?php while ($video = $videos->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($video['source'] == 'youtube'): ?>
                                                        <img src="https://img.youtube.com/vi/<?php echo $video['video_id']; ?>/mqdefault.jpg" class="video-thumbnail" alt="<?php echo $video['title']; ?>">
                                                    <?php elseif ($video['source'] == 'tiktok' && !empty($video['thumbnail'])): ?>
                                                        <img src="../uploads/thumbnails/<?php echo $video['thumbnail']; ?>" class="video-thumbnail" alt="<?php echo $video['title']; ?>">
                                                    <?php elseif ($video['source'] == 'facebook'): ?>
                                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center video-thumbnail">
                                                            <i class="fab fa-facebook fa-2x"></i>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center video-thumbnail">
                                                            <i class="fas fa-video fa-2x"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $video['title']; ?></td>
                                                <td>
                                                    <?php if ($video['source'] == 'youtube'): ?>
                                                        <span class="badge bg-danger"><i class="fab fa-youtube"></i> YouTube</span>
                                                    <?php elseif ($video['source'] == 'tiktok'): ?>
                                                        <span class="badge bg-dark"><i class="fab fa-tiktok"></i> TikTok</span>
                                                    <?php elseif ($video['source'] == 'facebook'): ?>
                                                        <span class="badge bg-primary"><i class="fab fa-facebook"></i> Facebook</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $video['category_name'] ?? 'Uncategorized'; ?></td>
                                                <td>
                                                    <?php if ($video['status'] == 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($video['created_at'])); ?></td>
                                                <td>
                                                    <a href="edit_video.php?id=<?php echo $video['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="view_video.php?id=<?php echo $video['id']; ?>" class="btn btn-sm btn-success" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="videos.php?action=delete&id=<?php echo $video['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this video?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No videos found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mt-4">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>&source=<?php echo $filter_source; ?>&category=<?php echo $filter_category; ?>">
                                            Previous
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&source=<?php echo $filter_source; ?>&category=<?php echo $filter_category; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>&source=<?php echo $filter_source; ?>&category=<?php echo $filter_category; ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
