<?php
require_once '../config/database.php';
require_once '../classes/Purchase.php';


session_start();

// Include the auth functions file
require_once '../auth/auth_functions.php'; 

// Protect the page
requireLogin();  // This function will redirect to login if not logged in



$database = new Database();
$db = $database->getConnection();
$purchase = new Purchase($db);

// Handle form submissions
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_purchase'])) {
        error_log("Received Data: " . print_r($_POST, true)); // Log all the data

        $purchase->medicine_id = $_POST['medicine_id'];
        $purchase->quantity = $_POST['quantity'];
        $purchase->purchase_price = $_POST['purchase_price'];
        $purchase->purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
        $purchase->expiry_date = $_POST['expiry_date'];  // Capture expiry date

        error_log("Expiry Date: " . $purchase->expiry_date); // Log the expiry date

        // Call the create function
        if ($purchase->create()) {
            $message = "Purchase recorded successfully.";
        } else {
            $error = "Unable to record purchase.";
        }
    }
}


// Pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$medicines = $purchase->getMedicines();
$purchases = $purchase->read($page, $limit, $date_from, $date_to);
$stats = $purchase->getPurchaseStats();
$recent_purchases = $purchase->getRecentPurchases();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    <link href="/msms/assets/css/custom.css" rel="stylesheet">

</head>

<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/navigation.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 animate__animated animate__fadeIn">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 animate__animated animate__fadeIn">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                <h3 class="text-gray-500 text-sm font-semibold">Total Purchases</h3>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_purchases']); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                <h3 class="text-gray-500 text-sm font-semibold">Total Spent</h3>
                <p class="text-3xl font-bold text-gray-800">₹<?php echo number_format($stats['total_spent'], 2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                <h3 class="text-gray-500 text-sm font-semibold">Average Purchase</h3>
                <p class="text-3xl font-bold text-gray-800">₹<?php echo number_format($stats['avg_purchase'], 2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                <h3 class="text-gray-500 text-sm font-semibold">Largest Purchase</h3>
                <p class="text-3xl font-bold text-gray-800">₹<?php echo number_format($stats['max_purchase'], 2); ?></p>
            </div>
        </div>

        <!-- Add Purchase Form -->
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-6">Record New Purchase</h2>
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Medicine</label>
                            <select name="medicine_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">Select Medicine</option>
                                <?php while ($medicine = $medicines->fetch_assoc()): ?>
                                    <option value="<?php echo $medicine['id']; ?>">
                                        <?php echo htmlspecialchars($medicine['name']); ?>
                                        (Stock: <?php echo $medicine['available_quantity']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                            <input type="number" name="quantity" min="1"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Price (per unit)</label>
                            <input type="number" step="0.01" name="purchase_price" min="0.01"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Date</label>
                            <input type="date" name="purchase_date"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo date('Y-m-d'); ?>"
                                required>
                        </div>

                        <!-- Expiry Date Field -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                            <input type="date" name="expiry_date"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" name="add_purchase"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform hover:scale-105 transition-transform duration-200">
                            Record Purchase
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <!-- Purchase History -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-6">Purchase History</h2>
                <div class="mb-4 flex gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From Date</label>
                        <input type="date" id="date_from" name="date_from" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            value="<?php echo $_GET['date_from'] ?? ''; ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">To Date</label>
                        <input type="date" id="date_to" name="date_to"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            value="<?php echo $_GET['date_to'] ?? ''; ?>">
                    </div>
                    <div class="flex items-end">
                        <button type="button" onclick="filterPurchases()" 
                            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                            Filter
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $purchases->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['quantity']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">₹<?php echo number_format($row['purchase_price'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">₹<?php echo number_format($row['total_cost'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M d, Y', strtotime($row['purchase_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Previous</a>
                        <?php endif; ?>

                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                            Page <?php echo $page; ?>
                        </span>

                        <a href="?page=<?php echo ($page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Next</a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Analytics Section -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-5">
            <h3 class="text-xl font-bold mb-4">Recent Activity</h3>
            <div class="space-y-4">
                <?php while ($recent = $recent_purchases->fetch_assoc()): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h4 class="font-semibold"><?php echo htmlspecialchars($recent['medicine_name']); ?></h4>
                            <p class="text-sm text-gray-600">
                                Quantity: <?php echo $recent['quantity']; ?> units
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold">₹<?php echo number_format($recent['total_cost'], 2); ?></p>
                            <p class="text-sm text-gray-600">
                                <?php echo date('M d, Y', strtotime($recent['purchase_date'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Initialize Chart -->
    <script>
        // Sample data for the chart - in production, you'd want to fetch this from your backend
        const ctx = document.getElementById('purchaseChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Monthly Purchase Trends',
                    data: [
                        <?php
                        // You could generate this data from your database
                        echo implode(',', [12000, 19000, 15000, 17000, 16000, 23000]);
                        ?>
                    ],
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const quantity = document.querySelector('input[name="quantity"]').value;
            const price = document.querySelector('input[name="purchase_price"]').value;

            if (quantity <= 0 || price <= 0) {
                e.preventDefault();
                alert('Please enter valid quantity and price values.');
            }
        });

        function filterPurchases() {
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
            
            if (dateFrom && dateTo && dateFrom > dateTo) {
                alert('From date cannot be greater than To date');
                return;
            }
            
            let url = new URL(window.location.href);
            let params = new URLSearchParams(url.search);
            
            // Update or add date parameters
            if (dateFrom) params.set('date_from', dateFrom);
            if (dateTo) params.set('date_to', dateTo);
            params.set('page', '1'); // Reset to first page when filtering
            
            // Redirect with new parameters
            window.location.href = `${url.pathname}?${params.toString()}`;
        }

        // Enhanced form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const quantity = parseInt(document.querySelector('input[name="quantity"]').value);
            const price = parseFloat(document.querySelector('input[name="purchase_price"]').value);
            const expiryDate = document.querySelector('input[name="expiry_date"]').value;
            
            let errors = [];
            
            if (!quantity || quantity <= 0) errors.push('Quantity must be greater than 0');
            if (!price || price <= 0) errors.push('Purchase price must be greater than 0');
            if (!expiryDate) errors.push('Expiry date is required');
            
            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    </script>
</body>

</html>