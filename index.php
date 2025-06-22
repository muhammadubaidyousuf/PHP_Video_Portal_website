<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get categories
$categories = get_categories();

// Build query
$where = [];
$params = [];
$types = '';

// Search filter
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = '%' . trim($_GET['search']) . '%';
    $where[] = '(v.title LIKE ? OR c.name LIKE ?)';
    $params[] = $search;
    $params[] = $search;
    $types .= 'ss';
}

// Category filter
if (isset($_GET['category']) && is_numeric($_GET['category'])) {
    $where[] = 'v.category_id = ?';
    $params[] = (int)$_GET['category'];
    $types .= 'i';
}

// Tag filter
if (isset($_GET['tag']) && is_numeric($_GET['tag'])) {
    $where[] = 'EXISTS (SELECT 1 FROM video_tags vt WHERE vt.video_id = v.id AND vt.tag_id = ?)';
    $params[] = (int)$_GET['tag'];
    $types .= 'i';
}

// Status filter - only show active videos
$where[] = "v.status = 'active'";
// Only show videos with thumbnail
$where[] = "v.thumbnail IS NOT NULL AND v.thumbnail != ''";

// Combine where clauses
$where_clause = '';
if (!empty($where)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where);
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM videos v 
                LEFT JOIN categories c ON v.category_id = c.id 
                $where_clause";

$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_videos = $count_stmt->get_result()->fetch_assoc()['total'];

// Pagination
$page = 1;
$per_page = 18;
$offset = 0;
$total_pages = ceil($total_videos / $per_page);

// Videos will be loaded via AJAX

// Get categories for filter
$categories = get_categories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <title>OMGTube - Watch Videos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand i {
            color: #ff0000;
        }
        .video-card {
            transition: transform 0.3s;
            height: 100%;
        }

        /* Loading Spinner */
        .loading-container {
            text-align: center;
            padding: 20px;
            display: none;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-radius: 50%;
            border-top: 5px solid #3498db;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .video-card:hover {
            transform: translateY(-5px);
        }
        .video-thumbnail {
            position: relative;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            background-color: #000;
            border-radius: 8px;
            overflow: hidden;
        }
        .video-thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .video-thumbnail .play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 3rem;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        .video-card:hover .play-icon {
            opacity: 1;
        }
        .video-source {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .video-title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 48px;
        }
        .category-badge {
            font-size: 0.8rem;
        }
        
        /* Video Lightbox Styles */
        .modal-backdrop.show {
            opacity: 0.9;
        }
        #videoLightbox .modal-dialog {
            max-width: 80%;
            margin: 0 auto;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #videoLightbox .modal-content {
            background-color: transparent;
            height: auto;
            max-height: 90vh;
            overflow: hidden;
            width: 100%;
        }
        #videoLightbox .modal-header {
            border-bottom: none;
            padding: 0.5rem 1rem;
            position: absolute;
            top: 0;
            right: 0;
            z-index: 10;
            background: rgba(0,0,0,0.3);
            border-radius: 0 0 0 8px;
        }
        /* Removed modal title styles */
        #videoLightbox .btn-close-white {
            filter: brightness(0) invert(1);
        }
        #videoLightbox .modal-body {
            padding: 0;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #videoContainer {
            position: relative;
            overflow: hidden;
            width: 100%;
            max-height: 85vh;
        }
        .video-responsive {
            position: relative;
            overflow: hidden;
            height: 0;
            width: 100%;
            max-width: 100%;
        }
        .video-responsive-16by9 {
            padding-bottom: 56.25%;
            max-height: 85vh;
            width: 100%;
        }
        .video-responsive-4by3 {
            padding-bottom: 75%;
            max-height: 85vh;
            width: 80%;
            margin: 0 auto;
        }
        .video-responsive-1by1 {
            padding-bottom: 100%;
            max-height: 85vh;
            width: 50%;
            margin: 0 auto;
        }
        /* Special handling for TikTok videos */
        .tiktok-container {
            width: auto;
            height: auto;
            max-width: 325px;
            max-height: 85vh;
            margin: 0 auto;
            aspect-ratio: 9/16;
        }
        .tiktok-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
        /* Special handling for Facebook videos */
        .facebook-container {
            width: auto;
            height: auto;
            max-width: 500px;
            max-height: 85vh;
            margin: 0 auto;
            aspect-ratio: 4/3;
        }
        .facebook-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
        .video-responsive iframe {
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
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-video me-2"></i>
                OMGTube
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <!-- <a class="nav-link active" href="index.php">Home</a> -->
                    </li>
                    <!-- <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown">
                            Categories
                        </a>
                        <ul class="dropdown-menu">
                            <!-- <?php foreach ($categories as $category): ?> -->
                                <li>
                                    <!-- <a class="dropdown-item" href="index.php?category=<?php echo $category['id']; ?>"> -->
                                        <!-- <?php echo $category['name']; ?> -->
                                    </a>
                                </li>
                            <!-- <?php endforeach; ?> -->
                        </ul>
                    </li> -->
                </ul>
                <form class="d-flex" id="search-form" onsubmit="return handleSearch(event)">
                    <div class="input-group">
                        <select name="category" id="category-select" class="form-select" style="max-width: 200px;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo $cat['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="search" id="search-input" class="form-control" placeholder="Search videos...">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row g-4">
            <!-- Ad Sidebar: Visible on md+ screens, above videos on mobile -->
            <div class="col-12 col-md-3 order-1 order-md-2 mb-4 mb-md-0">
                <!-- Google AdSense Placeholder Start -->
                <div class="card h-auto p-2 d-flex align-items-center justify-content-center">
                    <!-- Replace below with your actual Google AdSense code -->
                    <span class="text-muted">(Place your Google AdSense code here)</span>
                    <!-- Example AdSense code: -->
                    <!--
                    <ins class="adsbygoogle"
                        style="display:block"
                        data-ad-client="ca-pub-xxxxxxxxxxxxxxxx"
                        data-ad-slot="xxxxxxxxxx"
                        data-ad-format="auto"
                        data-full-width-responsive="true"></ins>
                    <script>
                        (adsbygoogle = window.adsbygoogle || []).push({});
                    </script>
                    -->
                </div>
                <!-- Google AdSense Placeholder End -->
            </div>
            <!-- Videos Section -->
            <div class="col-12 col-md-9 order-2 order-md-1">
                <div class="row g-4" id="videos-container">
                    <!-- Videos will be loaded here via JavaScript -->
                </div>
                <!-- Load More Button -->
                <div class="text-center my-4">
                    <button id="load-more-btn" class="btn btn-outline-dark" style="display:none;">Load More</button>
                </div>
                <!-- Loading Spinner -->
                <div class="loading-container" id="loading-spinner">
                    <div class="loading-spinner"></div>
                    <p class="mt-2 text-muted">Loading more videos...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Horizontal Ad (Above Footer) -->
    <div class="container my-4">
        <div class="card p-3 text-center d-flex align-items-center justify-content-center">
            <!-- Replace below with your actual Google AdSense code -->
            <span class="text-muted">(Place your horizontal Google AdSense code here)</span>
            <!-- Example AdSense code: -->
            <!--
            <ins class="adsbygoogle"
                style="display:block"
                data-ad-client="ca-pub-xxxxxxxxxxxxxxxx"
                data-ad-slot="xxxxxxxxxx"
                data-ad-format="horizontal"
                data-full-width-responsive="true"></ins>
            <script>
                (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
            -->
        </div>
    </div>
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-video me-2"></i>OMGTube</h5>
                    <p class="small">Watch and share amazing videos from YouTube, TikTok, and Facebook.</p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                        <li><a href="about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li><a href="disclaimer.php" class="text-white-50 text-decoration-none">Disclaimer</a></li>
                        <!-- <li><a href="admin/login.php" class="text-white-50 text-decoration-none">Admin Login</a></li> -->
                        <li><a href="sitemap.php" class="text-white-50 text-decoration-none">Sitemap</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Follow Us</h6>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white-50"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white-50"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white-50"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white-50"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-white-50 small">
                &copy; <?php echo date('Y'); ?> OMGTube. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Video Lightbox Modal -->
    <div class="modal fade" id="videoLightbox" tabindex="-1" aria-labelledby="videoLightboxLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="videoContainer" class="video-responsive video-responsive-16by9"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Infinite Scroll Script -->
    <script>
    let currentPage = 1;
    let loading = false;
    let hasMore = true;
    let currentSearch = '';
    let currentCategory = '';

    function createVideoCard(video) {
        return `
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="card video-card shadow-sm">
                    <a href="javascript:void(0);" class="text-decoration-none video-link" 
                       data-video-id="${video.video_id}" 
                       data-video-source="${video.source}" 
                       data-video-title="${video.title}">
                        <div class="video-thumbnail">
                            <div class="video-source">
                                ${video.source === 'youtube' ? '<span class="badge bg-danger"><i class="fab fa-youtube"></i></span>' : ''}
                                ${video.source === 'tiktok' ? '<span class="badge bg-dark"><i class="fab fa-tiktok"></i></span>' : ''}
                                ${video.source === 'facebook' ? '<span class="badge bg-primary"><i class="fab fa-facebook"></i></span>' : ''}
                            </div>
                            ${video.thumbnail ? 
                                `<img src="${video.thumbnail}" alt="${video.title}">` :
                                `<div class="bg-secondary h-100 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-video fa-3x text-white-50"></i>
                                </div>`
                            }
                            <div class="play-icon">
                                <i class="fas fa-play-circle"></i>
                            </div>
                        </div>
                    </a>
                    <div class="card-body">
                        <h6 class="card-title video-title">
                            <a href="javascript:void(0);" class="text-decoration-none text-dark video-link"
                               data-video-id="${video.video_id}" 
                               data-video-source="${video.source}" 
                               data-video-title="${video.title}">
                                ${video.title}
                            </a>
                        </h6>
                        ${video.category_name ? 
                            `<span class="badge bg-secondary category-badge">
                                ${video.category_name}
                            </span>` : ''
                        }
                        <div class="small text-muted mt-2">
                            <i class="far fa-clock me-1"></i>
                            ${video.created_at}
                        </div>
                    </div>
                </div>
            </div>`;
    }

    function handleSearch(event) {
        event.preventDefault();
        currentSearch = document.getElementById('search-input').value;
        currentCategory = document.getElementById('category-select').value;
        resetAndSearch();
        return false;
    }

    function resetAndSearch() {
        currentPage = 1;
        hasMore = true;
        document.getElementById('videos-container').innerHTML = '';
        loadMoreVideos();
    }

    function loadMoreVideos() {
        if (loading || !hasMore) return;
        
        loading = true;
        document.getElementById('loading-spinner').style.display = 'block';
        document.getElementById('load-more-btn').style.display = 'none';
        const formData = new FormData();
        formData.append('page', currentPage);
        formData.append('search', currentSearch);
        formData.append('category', currentCategory);

        fetch('load_more_videos.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // no video found
            if (data.videos.length === 0 && currentPage === 1) {
                document.getElementById('videos-container').innerHTML = '<div class="alert alert-warning text-center">(No video found on this keyword: ' + " " + currentSearch + ')</div>';
                hasMore = false;
            } else {
                data.videos.forEach(video => {
                    const card = createVideoCard(video);
                    document.getElementById('videos-container').insertAdjacentHTML('beforeend', card);
                });
                // Show/hide Load More button based on has_more
                if (data.has_more) {
                    document.getElementById('load-more-btn').style.display = 'inline-block';
                    hasMore = true;
                    currentPage++;
                } else {
                    document.getElementById('load-more-btn').style.display = 'none';
                    hasMore = false;
                }
            }
            loading = false;
            document.getElementById('loading-spinner').style.display = 'none';
        })
        .catch(error => {
            console.error('Error:', error);
            loading = false;
            document.getElementById('loading-spinner').style.display = 'none';
            document.getElementById('load-more-btn').style.display = 'none';
        });
    }

    // Initial load
    loadMoreVideos();

    // Load More button click
    document.getElementById('load-more-btn').onclick = function() {
        loadMoreVideos();
    };

    // Detect scroll and load more (optional, keep for infinite scroll)
    // window.addEventListener('scroll', () => {
    //     if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1000) {
    //         loadMoreVideos();
    //     }
    // });

    // Video Lightbox functionality
    document.addEventListener('click', function(e) {
        // Find closest video-link if clicked on or within a video link element
        const videoLink = e.target.closest('.video-link');
        if (videoLink) {
            e.preventDefault();
            const videoId = videoLink.getAttribute('data-video-id');
            const videoSource = videoLink.getAttribute('data-video-source');
            const videoTitle = videoLink.getAttribute('data-video-title');
            
            console.log('Opening video:', { videoId, videoSource, videoTitle });
            
            if (!videoId) {
                console.error('Missing video ID for', videoTitle);
                alert('Error: Video ID is missing. Please try another video.');
                return;
            }
            
            // Title display removed as requested
            
            // Generate the appropriate embed code based on the video source
            let embedHtml = '';
            
            // Handle different video sources with appropriate containers
            switch(videoSource) {
                case 'youtube':
                    // YouTube uses responsive 16:9 container
                    document.getElementById('videoContainer').className = 'video-responsive video-responsive-16by9';
                    embedHtml = `<iframe src='https://www.youtube.com/embed/${videoId}?autoplay=1' frameborder='0' 
                                allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' 
                                allowfullscreen></iframe>`;
                    break;
                    
                case 'tiktok':
                    // TikTok uses a special mobile-like container with 9:16 aspect ratio
                    document.getElementById('videoContainer').className = 'tiktok-container';
                    embedHtml = `<iframe src='https://www.tiktok.com/embed/v2/${videoId}' 
                                frameborder='0' allow='autoplay' allowfullscreen>
                                </iframe>`;
                    break;
                    
                case 'facebook':
                    // Facebook uses a special container with 4:3 aspect ratio
                    document.getElementById('videoContainer').className = 'facebook-container';
                    embedHtml = `<iframe src='https://www.facebook.com/plugins/video.php?href=https://www.facebook.com/watch/?v=${videoId}&show_text=false&width=476&height=476&appId' 
                                frameborder='0' allowfullscreen='true' 
                                allow='autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share'>
                                </iframe>`;
                    break;
                    
                default:
                    document.getElementById('videoContainer').className = 'video-responsive video-responsive-16by9';
                    embedHtml = `<div class='alert alert-warning'>Unsupported video source</div>`;
            }
            
            // Insert the embed code into the container
            document.getElementById('videoContainer').innerHTML = embedHtml;
            
            // Open the modal
            const videoModal = new bootstrap.Modal(document.getElementById('videoLightbox'));
            videoModal.show();
        }
    });

    // Reset video when modal is closed
    document.getElementById('videoLightbox').addEventListener('hidden.bs.modal', function () {
        document.getElementById('videoContainer').innerHTML = '';
    });
    </script>
</body>
</html>
