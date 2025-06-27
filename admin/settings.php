<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Only admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php'); exit;
}

// Load current settings
$settings = [
    'youtube_api_key' => '',
    'slug_mode' => 'default'
];
$sql = "SELECT setting_key, setting_value FROM settings";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $youtube_api_key = trim($_POST['youtube_api_key'] ?? '');
    $slug_mode = $_POST['slug_mode'] ?? 'default';
    // Update or insert settings
    $stmt = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->bind_param('ss', $k, $v);
    $k = 'youtube_api_key'; $v = $youtube_api_key; $stmt->execute();
    $k = 'slug_mode'; $v = $slug_mode; $stmt->execute();
    $stmt->close();
    header('Location: settings.php?success=1'); exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .sidebar .nav-link{color:rgba(255,255,255,0.8);margin-bottom:5px;}
        .sidebar .nav-link:hover,.sidebar .nav-link.active{color:#fff;background-color:rgba(255,255,255,0.1);}
        .sidebar .nav-link i{margin-right:10px;}
    </style>
</head>

<body style="background-color: #f8f9fa;">
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar p-0" style="min-height:100vh; background:#4e73df; color:white;">
            <?php include '../includes/layout/admin-sidebar.php'; ?>
        </div>
        <!-- Main Content -->
        <div class="col-md-10 py-5 px-4">
            <div class="card shadow rounded-4 mx-auto" style="max-width:700px;">
                <div class="card-body p-5">
                    <h2 class="mb-4 text-primary"><i class="fas fa-cogs me-2"></i>Settings</h2>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Settings updated successfully.</div>
                    <?php endif; ?>
                    <form method="post" class="row g-4">
                        <div class="col-12">
                            <label for="youtube_api_key" class="form-label">YouTube API Key</label>
                            <input type="text" class="form-control" id="youtube_api_key" name="youtube_api_key" value="<?php echo htmlspecialchars($settings['youtube_api_key']); ?>" placeholder="Enter your YouTube Data API v3 Key">
                        </div>
                        <div class="col-12">
                            <label for="slug_mode" class="form-label">Slug Mode</label>
                            <select class="form-select" id="slug_mode" name="slug_mode">
                                <option value="default" <?php if($settings['slug_mode']==='default') echo 'selected'; ?>>Default (hyphens)</option>
                                <option value="underscore" <?php if($settings['slug_mode']==='underscore') echo 'selected'; ?>>Underscore</option>
                                <option value="no_space" <?php if($settings['slug_mode']==='no_space') echo 'selected'; ?>>No Space</option>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary px-4">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</body>
</html>
