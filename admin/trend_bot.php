<?php
// trend_bot.php - Admin page to manage YouTube Trend Bot
require_once '../includes/session.php';

// default values
$defaults = [
    'enabled'      => '0',
    'region'       => 'US',
    'max_videos'   => '10',
    'freq_minutes' => '60',
    'last_run'    => '',
    'last_added' => ''
];

// fetch saved
$settings = $defaults;
$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'trend_bot_%'");
while($row=$res->fetch_assoc()){
    $k = str_replace('trend_bot_','',$row['setting_key']);
    $settings[$k] = $row['setting_value'];
}

// handle run now
if(isset($_POST['run_now'])){
    // Build paths safely
    $scriptPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'youtube_trend_bot.php';
    $phpPath = (stripos(PHP_OS,'WIN')===0 ? PHP_BINDIR.DIRECTORY_SEPARATOR.'php.exe' : PHP_BINARY);
    if(!file_exists($phpPath)){
        // fallback to generic 'php' in PATH
        $phpPath = 'php';
    }
    // Wrap in quotes for spaces and append stderr
    $cmd = '"' . $phpPath . '" "' . $scriptPath . '" 2>&1';
    $output = shell_exec($cmd);
    if($output===null || strpos($output,'cannot find')!==false || trim($output)==''){
        // fallback include when command fails
        ob_start();
        include $scriptPath;
        $output = ob_get_clean();
    }
    set_message('Bot executed manually. Output:<br><pre>'.htmlspecialchars($output).'</pre>');
    header('Location: trend_bot.php');
    exit;
}


// handle post (save settings)
if($_SERVER['REQUEST_METHOD']==='POST'){
    $settings['enabled'] = isset($_POST['enabled']) ? '1':'0';
    $settings['region'] = strtoupper($_POST['region'] ?? 'US');
    $settings['max_videos'] = max(1,min(50, (int)($_POST['max_videos'] ?? 10)));
    $settings['freq_minutes'] = max(5, (int)($_POST['freq_minutes'] ?? 60));

    // save
    $stmt=$conn->prepare("REPLACE INTO settings (setting_key,setting_value) VALUES ( ?,? )");
    foreach($settings as $k=>$v){
        if($k==='last_run' || $k==='last_added') continue;
        $key='trend_bot_'.$k;
        $stmt->bind_param('ss',$key,$v);
        $stmt->execute();
    }
    $stmt->close();
    set_message('Trend Bot settings updated.');
    header('Location: trend_bot.php');
    exit;
}

$current = 'trend_bot.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Trend Bot Settings - OMGTube Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {background:#f8f9fa;}
        .sidebar{min-height:100vh;background-color:#4e73df;color:#fff;}
        .sidebar .nav-link{color:rgba(255,255,255,.8);margin-bottom:5px;}
        .sidebar .nav-link:hover,.sidebar .nav-link.active{color:#fff;background:rgba(255,255,255,.1);}    </style>
</head>
<body>
<div class="container-fluid">
 <div class="row">
  <div class="col-md-2 sidebar p-0">
    <?php include '../includes/layout/admin-sidebar.php'; ?>
  </div>
  <div class="col-md-10 p-4">
   <h2 class="mb-4"><i class="fas fa-robot me-2"></i> YouTube Trend Bot Settings</h2>
   <?php display_message(); ?>
   <form method="post" class="card p-4">
     <div class="form-check form-switch mb-3">
       <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1" <?php if($settings['enabled']) echo 'checked';?>>
       <label class="form-check-label" for="enabled">Enable Trend Bot</label>
     </div>
     <div class="mb-3">
       <label class="form-label" for="region">Region Code (ISO 3166-1 Alpha-2)</label>
       <input class="form-control" id="region" name="region" maxlength="2" value="<?php echo htmlspecialchars($settings['region']);?>" required>
     </div>
     <div class="row mb-3">
       <div class="col-md-6">
         <label class="form-label" for="max_videos">Max Videos per Run (1-50)</label>
         <input type="number" class="form-control" id="max_videos" name="max_videos" min="1" max="50" value="<?php echo (int)$settings['max_videos'];?>" required>
       </div>
       <div class="col-md-6">
         <label class="form-label" for="freq_minutes">Frequency Minutes (>=5)</label>
         <input type="number" class="form-control" id="freq_minutes" name="freq_minutes" min="1" value="<?php echo (int)$settings['freq_minutes'];?>" required>
       </div>
     </div>
     <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
   </form>
<form method="post" class="mt-3">
 <input type="hidden" name="run_now" value="1">
 <button type="submit" class="btn btn-success"><i class="fas fa-play"></i> Run Now</button>
</form>
   <?php if(!empty($settings['last_run'])): ?>
    <p class="mt-3 text-muted">Last run: <?php echo htmlspecialchars($settings['last_run']);?>, videos added: <?php echo isset($settings['last_added']) ? intval($settings['last_added']) : 0; ?></p>
    <?php endif; ?>
  </div>
 </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  }
  document.getElementById('runNowBtn').addEventListener('click',runBot);
  enabledSwitch.addEventListener('change',startTimer);
  startTimer();
})();
</script>
</body>
</html>
