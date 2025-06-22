<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - OMGTube</title>
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
        .about-content {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 2rem;
        }
        .feature-card {
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .team-member {
            text-align: center;
            margin-bottom: 2rem;
        }
        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 5px solid #f8f9fa;
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
                        <a class="nav-link active" href="/video_portal/about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/video_portal/disclaimer.php">Disclaimer</a>
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
        <!-- About Section -->
        <div class="row mb-5">
            <div class="col-lg-12">
                <div class="about-content">
                    <h1 class="mb-4">About OMGTube</h1>
                    <p class="lead">Your one-stop destination for videos from multiple platforms</p>
                    
                    <p>Welcome to OMGTube, a curated platform that brings together the best videos from YouTube, TikTok, and Facebook in one convenient location. Our mission is to provide users with a seamless video watching experience across multiple platforms without the need to switch between different apps or websites.</p>
                    
                    <p>Founded in 2023, OMGTube has grown to become a favorite destination for video enthusiasts looking for diverse content from various sources. We continuously update our collection with fresh, engaging videos across different categories to ensure there's something for everyone.</p>
                    
                    <div class="row mt-5">
                        <div class="col-md-4 mb-4">
                            <div class="card feature-card shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="feature-icon text-danger">
                                        <i class="fas fa-globe"></i>
                                    </div>
                                    <h4>Multi-Platform Integration</h4>
                                    <p>Access videos from YouTube, TikTok, and Facebook all in one place without switching between apps.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card feature-card shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="feature-icon text-primary">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h4>Smart Search</h4>
                                    <p>Find exactly what you're looking for with our powerful search and filtering capabilities.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card feature-card shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="feature-icon text-success">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <h4>Mobile Friendly</h4>
                                    <p>Enjoy a seamless video watching experience on any device, from desktop to smartphone.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Our Vision Section -->
        <div class="row mb-5">
            <div class="col-lg-12">
                <div class="about-content">
                    <h2 class="mb-4">Our Vision</h2>
                    <p>At OMGTube, we envision a world where accessing quality video content is simple, efficient, and enjoyable. We believe in breaking down the barriers between different video platforms to create a unified experience that respects user preferences and enhances content discovery.</p>
                    
                    <p>Our goal is to continue expanding our platform to include more video sources, enhance our recommendation algorithms, and build a community of video enthusiasts who share and discover content together.</p>
                    
                    <div class="text-center mt-4">
                        <a href="/video_portal/index.php" class="btn btn-primary btn-lg">Start Watching</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Section -->
        <div class="row">
            <div class="col-lg-12">
                <div class="about-content">
                    <h2 class="mb-4">Contact Us</h2>
                    <p>Have questions, suggestions, or feedback? We'd love to hear from you! Reach out to us using the information below:</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-envelope fa-2x text-primary me-3"></i>
                                <div>
                                    <h5 class="mb-0">Email</h5>
                                    <p class="mb-0">info@videoportal.com</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-phone fa-2x text-success me-3"></i>
                                <div>
                                    <h5 class="mb-0">Phone</h5>
                                    <p class="mb-0">+1 (555) 123-4567</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt fa-2x text-danger me-3"></i>
                                <div>
                                    <h5 class="mb-0">Address</h5>
                                    <p class="mb-0">123 Video Street, Digital City</p>
                                </div>
                            </div>
                        </div>
                    </div>
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
