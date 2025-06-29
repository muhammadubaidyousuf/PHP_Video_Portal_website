<?php
/**
 * Cleanup Old Videos – OMGTube
 *
 * Yeh CLI script purani videos ko DB se delete karta hai.
 * Default: 7 din se purani videos.
 *
 * Adjust karne ke 3 tareeqe:
 *   1) Admin panel setting `cleanup_days` (e.g. 7, 30)
 *   2) CLI arg: `php cleanup_old_videos.php 14` → 14 din purani delete karega.
 *   3) Hard-coded DEFAULT_DAYS constant niche.
 *
 * Suggested cron (roz raat 2:15 AM):
 *   15 2 * * * /usr/bin/php /var/www/htdocs/cron/cleanup_old_videos.php >> /var/www/htdocs/logs/cleanup.log 2>&1
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

const DEFAULT_DAYS = 10;

// DB setting read
$days = (int) get_setting('cleanup_days', DEFAULT_DAYS);
// CLI arg override
if(isset($argv[1]) && is_numeric($argv[1])){
    $days = (int) $argv[1];
}
if($days < 1) $days = DEFAULT_DAYS;

$cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

// Count videos to delete
$countRes = $conn->prepare("SELECT COUNT(*) FROM videos WHERE created_at < ?");
$countRes->bind_param('s', $cutoff);
$countRes->execute();
$countRes->bind_result($toDelete);
$countRes->fetch();
$countRes->close();

if($toDelete == 0){
    echo "No videos older than {$days} days to delete.\n";
    exit(0);
}

// Delete
$del = $conn->prepare("DELETE FROM videos WHERE created_at < ?");
$del->bind_param('s', $cutoff);
$del->execute();
$affected = $del->affected_rows;
$del->close();

// Update stat setting (optional cumulative)
$totalDeleted = (int) get_setting('total_deleted_videos', 0) + $affected;
set_setting('total_deleted_videos', $totalDeleted);
set_setting('last_cleanup_run', date('Y-m-d H:i:s'));

echo "Deleted {$affected} videos older than {$days} days.\n";
$conn->close();
