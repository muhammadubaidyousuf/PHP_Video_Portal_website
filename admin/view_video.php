<?php
// Include session
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Get video ID
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($video_id === 0) {
    set_message('Invalid video ID', 'danger');
    header('Location: videos.php');
    exit();
}

// Get video details
$query = "SELECT v.*, c.name as category_name 
          FROM videos v 
          LEFT JOIN categories c ON v.category_id = c.id 
          WHERE v.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $video_id);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();

if (!$video) {
    set_message('Video not found', 'danger');
    header('Location: videos.php');
    exit();
}

// Get video tags
$video_tags = get_video_tags($video_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Video - OMGTube</title>
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
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            max-width: 100%;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
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
                    <h2><i class="fas fa-film me-2"></i>View Video</h2>
                    <div>
                        <a href="edit_video.php?id=<?php echo $video['id']; ?>" class="btn btn-info">
                            <i class="fas fa-edit me-1"></i> Edit Video
                        </a>
                        <a href="videos.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
                
                <?php display_message(); ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><?php echo htmlspecialchars($video['title']); ?></h5>
                                <div class="video-container mb-3">
                                    <?php echo get_video_embed($video['source'], $video['video_id']); ?>
                                </div>
                                <?php if(isset($video['description']) && !empty($video['description'])): ?>
                                <div class="mb-3">
                                    <h6>Description:</h6>
                                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                                </div>
                                <?php else: ?>
                                <div class="mb-3">
                                    <h6>Description:</h6>
                                    <p class="text-muted"><em>No description available</em></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Video Details</h5>
                                <table class="table">
                                    <tr>
                                        <th>Category:</th>
                                        <td><?php echo htmlspecialchars($video['category_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Source:</th>
                                        <td><?php echo ucfirst(htmlspecialchars($video['source'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Preview URL:</th>
                                        <td>
                                            <?php if (!empty($video['slug'])): ?>
                                                <?php 
                                                    $video_preview = [
                                                        'id' => $video['id'],
                                                        'slug' => create_slug($video['slug']),
                                                        'category_slug' => create_slug($video['category_name'])
                                                    ];
                                                ?>
                                                <a href="<?php echo htmlspecialchars(get_video_url($video_preview)); ?>" target="_blank">
                                                    View on video
                                                    <i class="fas fa-external-link-alt ms-1"></i>
                                                </a>
                                            <?php else: ?>
                                                <em>No URL available</em>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Tags:</th>
                                        <td>
                                            <?php if (!empty($video_tags)): ?>
                                                <?php foreach ($video_tags as $tag): ?>
                                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($tag['name']); ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <em>No tags</em>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Added:</th>
                                        <td><?php echo date('M d, Y', strtotime($video['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Last Updated:</th>
                                        <td><?php echo date('M d, Y', strtotime($video['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <?php if ($video['source'] == 'tiktok' && !empty($video['thumbnail'])): ?>
                        <div class="card shadow">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Custom Thumbnail</h5>
                                <img src="../uploads/thumbnails/<?php echo htmlspecialchars($video['thumbnail']); ?>" class="img-fluid rounded" alt="Video thumbnail">
                            </div>
                        </div>
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
