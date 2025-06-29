<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Auth check
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Default values
$region      = get_setting('yt_region', 'US');
$max_results = get_setting('yt_max_results', '15');
$last_run    = get_setting('yt_last_run', 'N/A');
$total_added = get_setting('yt_total_added', '0');

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $region      = sanitize($_POST['region'] ?? 'US');
    $max_results = (int) ($_POST['max_results'] ?? 15);

    set_setting('yt_region', $region);
    set_setting('yt_max_results', $max_results);

    set_message('Settings updated successfully!');
    header('Location: bot_settings.php');
    exit;
}

// Country code list (short)
$regions = [
    'US' => 'United States',
    'GB' => 'United Kingdom',
    'IN' => 'India',
    'PK' => 'Pakistan',
    'CA' => 'Canada',
    'AU' => 'Australia'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trend Bot Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background-color:#f8f9fa; }
        .sidebar {
            min-height:100vh;
            background-color:#4e73df;
            color:#fff;
        }
        .sidebar .nav-link {
            color:rgba(255,255,255,0.8);
            margin-bottom:5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color:#fff;
            background-color:rgba(255,255,255,0.1);
        }
        .sidebar .nav-link i { margin-right:10px; }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 sidebar p-0">
        <?php include '../includes/layout/admin-sidebar.php'; ?>
    </div>
    <div class="col-md-10 p-4">
    <h3>YouTube Trend Bot Settings</h3>
    <?php display_message(); ?>

    <form method="post" class="card p-3 mb-4">
        <div class="mb-3">
            <label class="form-label">Region (Country Code)</label>
            <select name="region" class="form-select" required>
                <?php foreach ($regions as $code => $name): ?>
                    <option value="<?= $code ?>" <?= $region === $code ? 'selected' : '' ?>><?= $name ?> (<?= $code ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Max Videos Per Category (1-50)</label>
            <input type="number" name="max_results" class="form-control" min="1" max="50" value="<?= $max_results ?>" required>
        </div>
        <button class="btn btn-primary">Save Settings</button>
        <br>
        <button id="run-now" type="button" class="btn btn-success ms-2">Run Now</button>
    </form>

    <div class="card p-3">
        <h5 class="mb-2">Bot Status</h5>
        <p>Last Run: <strong id="last-run"><?= $last_run ?></strong></p>
        <p>Total Videos Added (since first run): <strong id="total-added"><?= $total_added ?></strong></p>
        <div id="run-output" class="bg-dark text-white p-2 small" style="display:none; white-space:pre-wrap;"></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('run-now').addEventListener('click', () => {
    const btn = document.getElementById('run-now');
    btn.disabled = true; btn.textContent = 'Running...';
    fetch('bot_settings_run.php')
        .then(r => r.json())
        .then(res => {
            document.getElementById('run-output').style.display = 'block';
            document.getElementById('run-output').textContent = res.output;
            document.getElementById('last-run').textContent = res.last_run;
            document.getElementById('total-added').textContent = res.total_added;
        })
        .catch(err => alert('Error: '+err))
        .finally(()=>{btn.disabled=false;btn.textContent='Run Now';});
});
</script>
    </div>
  </div>
</div>
</body>
</html>
