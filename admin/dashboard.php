<?php
// Include session
require_once '../includes/session.php';

// Get video statistics
$total_videos_query = "SELECT COUNT(*) as total FROM videos";
$youtube_videos_query = "SELECT COUNT(*) as total FROM videos WHERE source = 'youtube'";
$tiktok_videos_query = "SELECT COUNT(*) as total FROM videos WHERE source = 'tiktok'";
$facebook_videos_query = "SELECT COUNT(*) as total FROM videos WHERE source = 'facebook'";

$total_videos = $conn->query($total_videos_query)->fetch_assoc()['total'];
$youtube_videos = $conn->query($youtube_videos_query)->fetch_assoc()['total'];
$tiktok_videos = $conn->query($tiktok_videos_query)->fetch_assoc()['total'];
$facebook_videos = $conn->query($facebook_videos_query)->fetch_assoc()['total'];

// Get recent videos
$recent_videos_query = "SELECT v.*, c.name as category_name 
                       FROM videos v 
                       LEFT JOIN categories c ON v.category_id = c.id 
                       ORDER BY v.created_at DESC 
                       LIMIT 10";
$recent_videos = $conn->query($recent_videos_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <title>Admin Dashboard - OMGTube</title>
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
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .youtube-card {
            background: linear-gradient(to right, #FF0000, #FF5050);
            color: white;
        }
        .tiktok-card {
            background: linear-gradient(to right, #000000, #25F4EE);
            color: white;
        }
        .facebook-card {
            background: linear-gradient(to right, #3B5998, #8B9DC3);
            color: white;
        }
        .total-card {
            background: linear-gradient(to right, #4e73df, #6f8dfa);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <?php include '../includes/layout/admin-sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                
                <?php display_message(); ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card total-card h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs text-white-50 text-uppercase mb-1">Total Videos</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_videos; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-film fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card youtube-card h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs text-white-50 text-uppercase mb-1">YouTube Videos</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $youtube_videos; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fab fa-youtube fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card tiktok-card h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs text-white-50 text-uppercase mb-1">TikTok Videos</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $tiktok_videos; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fab fa-tiktok fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card facebook-card h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs text-white-50 text-uppercase mb-1">Facebook Videos</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $facebook_videos; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fab fa-facebook fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Videos -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Videos</h6>
                        <a href="videos.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Source</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Date Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_videos->num_rows > 0): ?>
                                        <?php while ($video = $recent_videos->fetch_assoc()): ?>
                                            <tr>
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
                                                    <a href="edit_video.php?id=<?php echo $video['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                                                    <a href="view_video.php?id=<?php echo $video['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-eye"></i></a>
                                                    <!-- <a href="delete_video.php?id=<?php echo $video['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this video?');"><i class="fas fa-trash"></i></a> -->
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No videos found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
