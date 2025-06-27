<?php
// Include session
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = sanitize($_POST['title']);
    $source = sanitize($_POST['source']);
    $video_link = sanitize($_POST['video_link']);
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $status = sanitize($_POST['status']);
    $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    $slug = create_slug($title);

    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($source)) {
        $errors[] = 'Video source is required';
    }
    
    if (empty($video_link)) {
        $errors[] = 'Video link is required';
    }
    
    // Extract video ID from URL
    $video_id = extract_video_id($video_link, $source);
    if (!$video_id) {
        $errors[] = 'Invalid video URL. Please enter a valid ' . ucfirst($source) . ' video URL';
    }
    
    // Handle thumbnail for TikTok videos
    $thumbnail = null;
    if ($source == 'tiktok' && isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $thumbnail = upload_thumbnail($_FILES['thumbnail']);
        if (!$thumbnail) {
            $errors[] = 'Error uploading thumbnail. Please try again.';
        }
    }
    
    // If no errors, insert video
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert video
            $query = "INSERT INTO videos (title, source, video_id, thumbnail, category_id, slug, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssss", $title, $source, $video_id, $thumbnail, $category_id, $slug, $status);
            $stmt->execute();
            
            // Get the last inserted video ID
            $video_id_db = $conn->insert_id;
            
            // Insert tags
            if (!empty($tags)) {
                foreach ($tags as $tag_id) {
                    $query = "INSERT INTO video_tags (video_id, tag_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ii", $video_id_db, $tag_id);
                    $stmt->execute();
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Set success message
            set_message('Video added successfully!');
            
            // Redirect to videos page
            redirect('videos.php');
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Get categories
$categories = get_categories();

// Get tags
$tags = get_tags();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <title>Add Video - OMGTube</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
                <h2 class="mb-4"><i class="fas fa-plus-circle me-2"></i>Add New Video</h2>
                
                <?php display_message(); ?>
                
                <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-body p-4">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Video Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="source" class="form-label">Video Source</label>
                                    <select class="form-select" id="source" name="source" required>
                                        <option value="">Select Source</option>
                                        <option value="youtube">YouTube</option>
                                        <option value="tiktok">TikTok</option>
                                        <option value="facebook">Facebook</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="video_link" class="form-label">Video Link</label>
                                <input type="url" class="form-control" id="video_link" name="video_link" placeholder="Paste video URL here" required>
                                <small class="text-muted">
                                    Example URLs:<br>
                                    YouTube: https://www.youtube.com/watch?v=XXXXXXXXXXX<br>
                                    TikTok: https://www.tiktok.com/@username/video/XXXXXXXXXXX<br>
                                    Facebook: https://www.facebook.com/watch/?v=XXXXXXXXXXX
                                </small>
                            </div>
                            
                            <div id="thumbnail-container" class="mb-3 d-none">
                                <label for="thumbnail" class="form-label">Upload Thumbnail (required for TikTok videos)</label>
                                <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                                <small class="text-muted">Recommended size: 1280x720px. Max size: 2MB</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="tags" class="form-label">Tags</label>
                                    <select class="form-select" id="tags" name="tags[]" multiple>
                                        <?php foreach ($tags as $tag): ?>
                                            <option value="<?php echo $tag['id']; ?>"><?php echo $tag['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Video
                                </button>
                                <a href="videos.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#tags').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select tags',
                allowClear: true
            });
            
            // Show/hide thumbnail upload based on video source
            $('#source').change(function() {
                if ($(this).val() === 'tiktok') {
                    $('#thumbnail-container').removeClass('d-none');
                } else {
                    $('#thumbnail-container').addClass('d-none');
                }
            });
        });
    </script>
</body>
</html>
