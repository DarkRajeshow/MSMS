<?php
// cron/check_expiry.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/ExpiryNotification.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Initialize notification system
$notifier = new ExpiryNotification($db);

// Check for expiring and expired medicines
$notifications = $notifier->checkMedicines();

// Log the results
$log_message = date('Y-m-d H:i:s') . " - Checked medicines expiry.\n";
$log_message .= "Created " . count($notifications) . " new notifications.\n";

if (!empty($notifications)) {
    foreach ($notifications as $notification) {
        $log_message .= sprintf(
            "- %s: %s\n",
            $notification['notification_type'],
            $notification['message']
        );
    }
}

// Write to log file
$log_file = __DIR__ . '/expiry_check.log';
file_put_contents($log_file, $log_message, FILE_APPEND);

// Output results if running from command line
if (php_sapi_name() === 'cli') {
    echo $log_message;
}
