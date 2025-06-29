<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
if (!is_logged_in()) {
    echo json_encode(['error'=> 'Unauthorised']);
    exit;
}

$region      = get_setting('yt_region', 'US');
$max_results = (int) get_setting('yt_max_results', 15);

// Build command to run bot with region & max (env vars)
$cmd = escapeshellcmd("php ../cron/youtube_trend_bot.php {$region} {$max_results}");
$output = shell_exec($cmd . ' 2>&1');

// update stats
set_setting('yt_last_run', date('Y-m-d H:i:s'));
$total = (int) get_setting('yt_total_added', 0);
if (preg_match('/Total new videos added: (\d+)/', $output, $m)) {
    $total += (int)$m[1];
    set_setting('yt_total_added', $total);
}

echo json_encode([
    'output'      => $output,
    'last_run'    => get_setting('yt_last_run'),
    'total_added' => get_setting('yt_total_added')
]);
