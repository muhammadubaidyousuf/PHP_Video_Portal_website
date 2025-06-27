<?php
// admin/trend_bot_run.php - AJAX endpoint to execute YouTube Trend Bot without shell
require_once '../includes/session.php';
header('Content-Type: application/json');

// Only admins
if (!is_logged_in()) {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

// include bot script and capture output
ob_start();
include dirname(__DIR__).'/cron/youtube_trend_bot.php';
$output = ob_get_clean();

// get stats for response
$added=0; $lastRun='';
$res=$conn->query("SELECT setting_key,setting_value FROM settings WHERE setting_key IN ('trend_bot_last_added','trend_bot_last_run')");
while($r=$res->fetch_assoc()){
    if($r['setting_key']=='trend_bot_last_added') $added=$r['setting_value'];
    if($r['setting_key']=='trend_bot_last_run')  $lastRun=$r['setting_value'];
}

echo json_encode([
    'success'=>true,
    'message'=>$output,
    'added'=>(int)$added,
    'last_run'=>$lastRun
]);
