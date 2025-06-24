<?php
// youtube_import.php - Admin page for importing videos from YouTube API
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch categories from DB for dropdown
$categories = [];
$res = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
while ($row = $res->fetch_assoc()) {
    $categories[] = $row;
}

// Fetch YouTube API key from settings table
$YOUTUBE_API_KEY = '';
$settings_res = $conn->query("SELECT setting_value FROM settings WHERE setting_key='youtube_api_key' LIMIT 1");
if ($settings_res && $settings_res->num_rows > 0) {
    $row = $settings_res->fetch_assoc();
    $YOUTUBE_API_KEY = $row['setting_value'];
} else {
    $YOUTUBE_API_KEY = '';
}
if (!$YOUTUBE_API_KEY) {
    echo '<div class="alert alert-danger m-3">YouTube API key not set. Please set it in <a href="settings.php">Settings</a>.</div>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Video Import - Admin</title>
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #4e73df; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: rgba(255,255,255,0.1); }
        .sidebar .nav-link i { margin-right: 10px; }
        .video-thumb { width: 120px; height: 68px; object-fit: cover; border-radius: 4px; }
        .yt-preview-table td { vertical-align: middle; }
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
        </div>
        <!-- Main Content -->
        <div class="col-md-10 p-4">

    <h2 class="mb-4"><i class="fab fa-youtube"></i> YouTube Video Import</h2>
    <form id="yt-import-form" class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="yt-keyword" class="form-label">YouTube Keyword</label>
            <input type="text" class="form-control" id="yt-keyword" name="keyword" placeholder="e.g. funny cats">
        </div>
        <div class="col-md-4">
            <label for="yt-category" class="form-label">Category</label>
            <select class="form-select" id="yt-category" name="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="yt-date-from" class="form-label">Date From</label>
            <input type="date" class="form-control" id="yt-date-from" name="date_from">
        </div>
        <div class="col-md-2">
            <label for="yt-date-to" class="form-label">Date To</label>
            <input type="date" class="form-control" id="yt-date-to" name="date_to">
        </div>
        <div class="col-md-2">
            <label for="yt-count" class="form-label">Video Count</label>
            <input type="number" class="form-control" id="yt-count" name="count" value="1" min="1" max="100">
            <!-- <select class="rm-select" id="yt-count" name="count"fo>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="30">30</option>
            </select> -->
        </div>
        <div class="col-md-2">
            <label for="yt-type" class="form-label">Video Type</label>
            <select class="form-select" id="yt-type" name="type">
                <option value="all">All</option>
                <option value="short">Shorts (&lt; 60s)</option>
                <option value="long">Long (&ge; 60s)</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="yt-channel" class="form-label">Channel ID/Username (optional)</label>
            <input type="text" class="form-control" id="yt-channel" name="channel" placeholder="e.g. UCBR8-60-B28hp2BmDPdntcQ" autocomplete="off">
        </div>
        <div class="col-12">
            <button type="button" class="btn btn-primary" id="fetch-videos">Fetch Videos</button>
        </div>
    </form>
    <div id="yt-preview-section" style="display:none;">
        <h5>Preview & Select Videos to Import</h5>
        <form id="yt-save-form">
            <table class="table table-bordered yt-preview-table align-middle">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Thumbnail</th>
                        <th>Title</th>
                        <th>Channel</th>
                        <th>Published At</th>
                    </tr>
                </thead>
                <tbody id="yt-preview-tbody">
                    <!-- Videos will be loaded here -->
                </tbody>
            </table>
            <button type="submit" class="btn btn-success">Add Selected Videos to Database</button>
        </form>
        <div id="yt-save-result" class="mt-3"></div>
    </div>
    <div id="yt-error" class="alert alert-danger mt-3" style="display:none;"></div>
</div>
<script>
const YT_API_KEY = '<?php echo $YOUTUBE_API_KEY; ?>';
document.getElementById('fetch-videos').onclick = function() {
    const keyword = document.getElementById('yt-keyword').value.trim();
    const category = document.getElementById('yt-category').value;
    const dateFrom = document.getElementById('yt-date-from').value;
    const dateTo = document.getElementById('yt-date-to').value;
    const count = document.getElementById('yt-count').value;
    const type = document.getElementById('yt-type').value;
    const channel = document.getElementById('yt-channel').value.trim();
    if (!category && !channel) {
        alert('Select a category or provide a Channel ID.');
        return;
    }
    document.getElementById('yt-error').style.display = 'none';
    fetch('youtube_import_fetch.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({keyword, dateFrom, dateTo, count, type, channel, category, apiKey: YT_API_KEY})
    })
    .then(r=>r.json())
    .then(data=>{
        if (data.error) {
            document.getElementById('yt-error').innerText = data.error;
            document.getElementById('yt-error').style.display = 'block';
            document.getElementById('yt-preview-section').style.display = 'none';
            return;
        }
        let html = '';
        data.videos.forEach((v,i)=>{
            html += `<tr>
                <td><input type='checkbox' name='videos[]' value='${i}' checked></td>
                <td><img src='${v.thumbnail}' class='video-thumb'></td>
                <td>${v.title}</td>
                <td>${v.channel}</td>
                <td>${v.published_at}</td>
                <input type='hidden' name='video_data[${i}]' value='${encodeURIComponent(JSON.stringify(v))}'>
            </tr>`;
        });
        document.getElementById('yt-preview-tbody').innerHTML = html;
        document.getElementById('yt-preview-section').style.display = 'block';
    })
    .catch(()=>{
        document.getElementById('yt-error').innerText = 'No data fetch from YouTube API';
        document.getElementById('yt-error').style.display = 'block';
    });
};
// Save selected videos to DB
if(document.getElementById('yt-save-form')){
    document.getElementById('yt-save-form').onsubmit = function(e){
        e.preventDefault();
        let selected = Array.from(document.querySelectorAll('input[name="videos[]"]:checked')).map(cb=>cb.value);
        if(selected.length == 0){
            alert('No video selected!');
            return;
        }
        let videoData = {};
        selected.forEach(i=>{
            let val = document.querySelector(`input[name='video_data[${i}]']`).value;
            videoData[i] = val;
        });
        const category = document.getElementById('yt-category').value;
        fetch('youtube_import_save.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({videoData, category})
        })
        .then(r=>r.json())
        .then(data=>{
            document.getElementById('yt-save-result').innerHTML = data.success ? '<div class="alert alert-success">'+data.success+'</div>' : '<div class="alert alert-danger">'+data.error+'</div>';
        })
        .catch(()=>{
            document.getElementById('yt-save-result').innerHTML = '<div class="alert alert-danger">DB me add karte waqt error aayi.</div>';
        });
    };
}
</script>
</body>
</html>
