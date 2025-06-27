<div class="d-flex flex-column p-3">
                    <a href="dashboard.php" class="d-flex align-items-center mb-3 text-decoration-none text-white">
                        <i class="fas fa-video me-2"></i>
                        <h4 class="mb-0">OMGTube</h4>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <?php $current = basename($_SERVER['PHP_SELF']); ?>
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link <?php if($current=='dashboard.php') echo 'active'; ?>">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="videos.php" class="nav-link <?php if($current=='videos.php') echo 'active'; ?>">
                                <i class="fas fa-film"></i> Videos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="youtube_import.php" class="nav-link <?php if($current=='youtube_import.php') echo 'active'; ?>">
                                <i class="fab fa-youtube"></i> YouTube Import
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="add_video.php" class="nav-link <?php if($current=='add_video.php') echo 'active'; ?>">
                                <i class="fas fa-plus-circle"></i> Add Video
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="categories.php" class="nav-link <?php if($current=='categories.php') echo 'active'; ?>">
                                <i class="fas fa-tags"></i> Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="profile.php" class="nav-link <?php if($current=='profile.php') echo 'active'; ?>">
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