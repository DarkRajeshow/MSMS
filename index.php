<?php
session_start();
require_once 'config/database.php';
require_once  './auth/auth_functions.php';

requireLogin();  // This function will redirect to login if not logged in



// Create a database object and get the connection
$database = new Database();
$conn = $database->getConnection();

// Check if $conn is null
if (!$conn) {
    die("Failed to establish a database connection.");
}

// Function to get total number of medicines
function getTotalMedicines($conn)
{
    $query = "SELECT COUNT(*) as total FROM medicines";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return 0; // Default to 0 if query fails
    }
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}

// Function to get daily transactions
function getDailyTransactions($conn)
{
    $query = "SELECT COUNT(*) as total FROM sales 
              WHERE DATE(sale_date) = CURDATE()";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}

// Function to get total customers (unique bills)
function getTotalCustomers($conn)
{
    $query = "SELECT COUNT(DISTINCT id) as total FROM bills";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}

// Function to calculate accuracy rate
function getAccuracyRate($conn)
{
    $query = "SELECT 
                (COUNT(*) - COUNT(CASE WHEN available_quantity < 0 THEN 1 END)) * 100.0 / NULLIF(COUNT(*), 0) as accuracy
              FROM medicines";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return 0; // Default accuracy to 0 if query fails
    }
    $row = mysqli_fetch_assoc($result);
    return round($row['accuracy'] ?? 0, 1);
}

// Get all stats
$stats = [
    'medicines' => getTotalMedicines($conn),
    'transactions' => getDailyTransactions($conn),
    'customers' => getTotalCustomers($conn),
    'accuracy' => getAccuracyRate($conn)
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Shop Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/msms/assets/css/custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include 'includes/navigation.php'; ?>

    <div class="container mx-auto mt-8 px-4 pb-12">
        <!-- Hero Section -->
        <div class="bg-white shadow-2xl rounded-2xl overflow-hidden mb-12">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-12 text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-white">
                    Medical Shop Management System
                </h1>
                <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                    Streamline your medical shop operations with our comprehensive, modern, and efficient management solution.
                </p>
                <div class="flex justify-center gap-4">
                    <a href="pages/dashboard.php"
                        class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition duration-300 flex items-center">
                        <i class="fas fa-chart-line mr-2"></i> View Dashboard
                    </a>
                    <a href="#features"
                        class="bg-blue-500 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-400 transition duration-300 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i> Learn More
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300">
                <div class="text-emerald-500 text-4xl mb-2">
                    <i class="fas fa-pills"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['medicines']); ?></h3>
                <p class="text-gray-600">Medicines Managed</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300">
                <div class="text-blue-500 text-4xl mb-2">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['transactions']); ?></h3>
                <p class="text-gray-600">Daily Transactions</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300">
                <div class="text-purple-500 text-4xl mb-2">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['customers']); ?></h3>
                <p class="text-gray-600">Total Customers</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300">
                <div class="text-red-500 text-4xl mb-2">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['accuracy']; ?>%</h3>
                <p class="text-gray-600">Inventory Accuracy</p>
            </div>
        </div>

        <!-- Features Section -->
        <div id="features" class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition duration-300 group">
                <div class="bg-blue-500 group-hover:bg-blue-600 transition duration-300 p-6">
                    <i class="fas fa-capsules text-4xl text-white"></i>
                </div>
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-3">Medicine Management</h2>
                    <p class="text-gray-600 mb-4">Efficiently manage your medicine inventory with advanced tracking and alerts.</p>
                    <a href="pages/medicines.php" class="text-blue-500 hover:text-blue-600 font-semibold flex items-center">
                        Access Now <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition duration-300 group">
                <div class="bg-green-500 group-hover:bg-green-600 transition duration-300 p-6">
                    <i class="fas fa-shopping-basket text-4xl text-white"></i>
                </div>
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-3">Purchase Management</h2>
                    <p class="text-gray-600 mb-4">Track and manage purchases with detailed reporting and analytics.</p>
                    <a href="pages/purchases.php" class="text-green-500 hover:text-green-600 font-semibold flex items-center">
                        Access Now <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition duration-300 group">
                <div class="bg-purple-500 group-hover:bg-purple-600 transition duration-300 p-6">
                    <i class="fas fa-cash-register text-4xl text-white"></i>
                </div>
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-3">Sales Management</h2>
                    <p class="text-gray-600 mb-4">Process sales quickly and generate professional invoices instantly.</p>
                    <a href="pages/sales.php" class="text-purple-500 hover:text-purple-600 font-semibold flex items-center">
                        Access Now <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p class="mb-2">© 2025 Medical Shop Management System. All rights reserved.</p>
                <div class="flex justify-center space-x-4">
                    <a href="#" class="hover:text-blue-400 transition"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="hover:text-blue-400 transition"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="hover:text-blue-400 transition"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>