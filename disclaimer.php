<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disclaimer - OMGTube</title>
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
        .disclaimer-content {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/video_portal/index.php">
                <i class="fas fa-video me-2"></i>
                OMGTube
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/video_portal/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/video_portal/about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/video_portal/disclaimer.php">Disclaimer</a>
                    </li>
                </ul>
                <form class="d-flex" action="/video_portal/index.php" method="get">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search videos...">
                    <button class="btn btn-outline-light" type="submit">Search</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="disclaimer-content">
                    <h1 class="mb-4">Disclaimer</h1>
                    
                    <h4 class="mt-4">Content Disclaimer</h4>
                    <p>OMGTube is a platform that embeds videos from third-party sources such as YouTube, TikTok, and Facebook. We do not host any videos on our servers. All videos displayed on this website are embedded from their original sources.</p>
                    
                    <h4 class="mt-4">No Ownership Claim</h4>
                    <p>We do not claim ownership of any videos displayed on this website. All videos remain the property of their respective owners and are subject to the terms and conditions of the platforms they are hosted on.</p>
                    
                    <h4 class="mt-4">Content Accuracy</h4>
                    <p>While we strive to ensure that all content displayed on our website is accurate and appropriate, we cannot guarantee the accuracy, completeness, or suitability of any video content. Users are advised to use their own discretion when viewing videos.</p>
                    
                    <h4 class="mt-4">Copyright Notice</h4>
                    <p>If you believe that any content on this website infringes upon your copyright, please contact us immediately with details of the alleged infringement. We will promptly remove any content that is found to be in violation of copyright laws.</p>
                    
                    <h4 class="mt-4">External Links</h4>
                    <p>This website contains links to external websites that are not provided or maintained by us. We do not guarantee the accuracy, relevance, timeliness, or completeness of any information on these external websites.</p>
                    
                    <h4 class="mt-4">Limitation of Liability</h4>
                    <p>In no event shall OMGTube be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on OMGTube's website, even if OMGTube or a OMGTube authorized representative has been notified orally or in writing of the possibility of such damage.</p>
                    
                    <h4 class="mt-4">Changes to Disclaimer</h4>
                    <p>OMGTube reserves the right to make changes to this disclaimer at any time without prior notice. By using this website, you are agreeing to be bound by the then-current version of this disclaimer.</p>
                    
                    <p class="mt-5 text-muted">Last updated: <?php echo date('F d, Y'); ?></p>
                </div>
            </div>
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
                        <li><a href="/video_portal/index.php" class="text-white-50 text-decoration-none">Home</a></li>
                        <li><a href="/video_portal/about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li><a href="/video_portal/disclaimer.php" class="text-white-50 text-decoration-none">Disclaimer</a></li>
                        <li><a href="/video_portal/admin/login.php" class="text-white-50 text-decoration-none">Admin Login</a></li>
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
</body>
</html>
