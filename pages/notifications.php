<?php
// pages/notifications.php
require_once '../config/database.php';
require_once '../classes/ExpiryNotification.php';


session_start();

// Include the auth functions file
require_once '../auth/auth_functions.php'; 

// Protect the page
requireLogin();  // This function will redirect to login if not logged in



// Create database connection
$database = new Database();
$db = $database->getConnection();

// Initialize notification system
$notifier = new ExpiryNotification($db);

// Handle marking notifications as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notifier->markAsRead($_POST['notification_id']);
    header('Location: notifications.php');
    exit();
}

// Get unread notifications
$notifications = $notifier->getUnreadNotifications();

include '../includes/navigation.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Expiry Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/msms/assets/css/custom.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto pt-20 px-10 ">
        <h1 class="text-3xl font-bold mb-6">Expiry Notifications</h1>

        <!-- Notifications List -->
        <div class="bg-white rounded-lg shadow-md">
            <?php if (empty($notifications)): ?>
                <div class="p-4 text-center text-gray-500">
                    No unread notifications
                </div>
            <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($notifications as $notification): ?>
                        <?php 
                        $bgColor = $notification['notification_type'] === 'expired' ? 'bg-red-50' : 'bg-yellow-50';
                        $textColor = $notification['notification_type'] === 'expired' ? 'text-red-800' : 'text-yellow-800';
                        $buttonColor = $notification['notification_type'] === 'expired' ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-500 hover:bg-blue-600';
                        ?>
                        <div class="p-4 flex items-center justify-between <?php echo $bgColor; ?>">
                            <div>
                                <p class="text-lg font-medium <?php echo $textColor; ?>">
                                    <?php echo htmlspecialchars($notification['medicine_name']); ?>
                                </p>
                                <p class="<?php echo $textColor; ?>">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Notified: <?php echo date('F j, Y', strtotime($notification['created_at'])); ?>
                                </p>
                            </div>
                            <form method="POST" class="ml-4">
                                <input type="hidden" name="notification_id"
                                    value="<?php echo $notification['id']; ?>">
                                <button type="submit" name="mark_read"
                                    class="px-4 py-2 text-white rounded <?php echo $buttonColor; ?>">
                                    Mark as Read
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>