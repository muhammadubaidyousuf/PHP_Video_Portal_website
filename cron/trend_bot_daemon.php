<?php
// cron/trend_bot_daemon.php
// -----------------------------------------------------
// Simple long-running watcher that periodically executes
// youtube_trend_bot.php without relying on OS schedulers.
//
// Usage (CLI):
//    php cron/trend_bot_daemon.php
//
// It checks the DB settings every minute and runs the bot
// when the configured interval (freq_minutes) has elapsed
// since the last run.
// -----------------------------------------------------

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

require_once __DIR__ . '/../config/database.php';

$scriptPath = __DIR__ . '/youtube_trend_bot.php';
if (!file_exists($scriptPath)) {
    error_log('trend_bot_daemon: bot script not found at '.$scriptPath);
    exit(1);
}

echo "[".date('Y-m-d H:i:s')."] Trend Bot daemon started.\n";

while (true) {
    // Read bot relevant settings
    $row = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('trend_bot_enabled','trend_bot_last_run','trend_bot_freq_minutes')")
             ->fetch_all(MYSQLI_ASSOC);
    $settings = [
        'enabled'      => '1',
        'last_run'     => null,
        'freq_minutes' => 1, // default 6 hours
    ];
    foreach ($row as $r) {
        $key = str_replace('trend_bot_','',$r['setting_key']);
        $settings[$key] = $r['setting_value'];
    }

    if ($settings['enabled'] === '1') {
        $freq = max(5, (int)$settings['freq_minutes']); // safety lower bound
        $last = $settings['last_run'] ? strtotime($settings['last_run']) : 0;
        if (time() - $last >= $freq * 60) {
            echo "[".date('Y-m-d H:i:s')."] Running Trend Bot...\n";
            // Execute the main bot script and capture output
            $phpPath = (stripos(PHP_OS,'WIN')===0 ? PHP_BINDIR.DIRECTORY_SEPARATOR.'php.exe' : PHP_BINARY);
            if(!file_exists($phpPath)){
                $phpPath = 'php'; // rely on PATH
            }
            $cmd = '"'.$phpPath.'" "'.$scriptPath.'" 2>&1';
            $output = shell_exec($cmd);
            if($output===null || strpos($output,'cannot find')!==false){
                // fallback to include within same process
                ob_start();
                include $scriptPath;
                $output = ob_get_clean();
            }
            echo $output;
            echo "[".date('Y-m-d H:i:s')."] Bot finished. Sleeping...\n";
        }
    } else {
        echo "[".date('Y-m-d H:i:s')."] Bot disabled in settings. Sleeping...\n";
    }

    // Sleep 60 seconds before next check
    sleep(60);
}
