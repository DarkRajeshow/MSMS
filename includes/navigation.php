<?php
// includes/navigation.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/msms/config/database.php';


// Determine the current page to highlight active navigation item
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread notifications count
$database = new Database();
$db = $database->getConnection();
$notif_query = "SELECT COUNT(*) as count FROM notifications WHERE status = 'unread'";
$notif_result = $db->query($notif_query);
$unread_count = $notif_result->fetch_assoc()['count'];

// Get latest notifications
$notifications_query = "SELECT n.*, m.name as medicine_name 
                       FROM notifications n 
                       LEFT JOIN medicines m ON n.medicine_id = m.id 
                       ORDER BY n.created_at DESC 
                       LIMIT 5";
$notifications = $db->query($notifications_query);
?>

<nav class="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
    <div class="container mx-auto">
        <!-- Main Navigation -->
        <div class="flex justify-between items-center p-4">
            <div class="flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span class="text-2xl font-bold tracking-tight">Medical Shop</span>
            </div>

            <div class="hidden md:flex items-center space-x-1">
                <a href="/msms/index.php"
                    class="<?php echo ($current_page == 'index.php') ? 'bg-white text-blue-600' : 'hover:bg-blue-500'; ?> px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Home</span>
                </a>

                <a href="/msms/pages/dashboard.php"
                    class="<?php echo ($current_page == 'dashboard.php') ? 'bg-white text-blue-600' : 'hover:bg-blue-500'; ?> px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="/msms/pages/medicines.php"
                    class="<?php echo ($current_page == 'medicines.php') ? 'bg-white text-blue-600' : 'hover:bg-blue-500'; ?> px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                    <span>Medicines</span>
                </a>

                <a href="/msms/pages/purchases.php"
                    class="<?php echo ($current_page == 'purchases.php') ? 'bg-white text-blue-600' : 'hover:bg-blue-500'; ?> px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Purchases</span>
                </a>

                <a href="/msms/pages/sales.php"
                    class="<?php echo ($current_page == 'sales.php') ? 'bg-white text-blue-600' : 'hover:bg-blue-500'; ?> px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span>Sales</span>
                </a>

                <!-- Notifications Button -->
                <div class="relative" x-data="{ open: false }" x-cloak>
                    <button @click="open = !open" class="px-4 py-2 rounded-lg hover:bg-blue-500 transition-all duration-200 flex items-center space-x-2">
                        <div class="relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <?php if ($unread_count > 0): ?>
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">
                                    <?php echo $unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </button>

                    <!-- Notifications Dropdown -->
                    <div x-show="open" @click.away="open = false"
                        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl text-gray-700 z-50"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100">
                        <div class="p-4">
                            <h3 class="text-lg font-semibold mb-4">Notifications</h3>
                            <?php if ($notifications->num_rows > 0): ?>
                                <div class="space-y-4">
                                    <?php while ($notif = $notifications->fetch_assoc()): ?>
                                        <div class="flex items-start space-x-4 p-3 <?php echo $notif['status'] === 'unread' ? 'bg-blue-50' : ''; ?> rounded-lg">
                                            <div class="flex-shrink-0">
                                                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm"><?php echo htmlspecialchars($notif['message']); ?></p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <?php echo date('M d, H:i', strtotime($notif['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <a href="/msms/pages/notifications.php" class="block text-center text-blue-600 hover:text-blue-800 mt-4 text-sm">
                                    View All Notifications
                                </a>
                            <?php else: ?>
                                <p class="text-gray-500 text-center">No notifications</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Logout button -->
                <form action="/msms/pages/logout.php" method="post">
                    <button type="submit" class="bg-red-500 py-2 px-4 rounded-md">
                        Logout
                    </button>
                </form>

            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button class="mobile-menu-button p-2 rounded-lg hover:bg-blue-500 transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu hidden md:hidden p-4 space-y-2">
            <a href="/msms/index.php" class="block px-4 py-2 rounded-lg hover:bg-blue-500 transition-all duration-200">Home</a>
            <a href="/msms/pages/dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-blue-500 transition-all duration-200">Dashboard</a>
            <a href="/msms/pages/medicines.php" class="block px-4 py-2 rounded-lg hover:bg-blue-500 transition-all duration-200">Medicines</a>
            <a href="/msms/pages/purchases.php" class="block px-4 py-2 rounded-lg hover:bg-blue-500 transition-all duration-200">Purchases</a>
            <a href="/msms/pages/sales.php" class="block px-4 py-2 rounded-lg hover:bg-blue-500 transition-all duration-200">Sales</a>
            <a href="/msms/pages/notifications.php" class="block px-4 py-2 rounded-lg hover:bg-blue-500 transition-all duration-200">
                Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="ml-2 bg-red-500 text-white rounded-full px-2 py-1 text-xs">
                        <?php echo $unread_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</nav>

<!-- AlpineJS styles and script -->
<style>[x-cloak] { display: none !important; }</style>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<!-- Mobile Menu Toggle Script -->
<script>
    document.querySelector('.mobile-menu-button').addEventListener('click', function() {
        document.querySelector('.mobile-menu').classList.toggle('hidden');
    });


    const logoutUser = () => {
        console.log("sfds");

        logout()
    }
</script>