<?php
// pages/dashboard.php
require_once '../config/database.php';

session_start();

// Include the auth functions file
require_once '../auth/auth_functions.php'; 
require_once '../classes/ExpiryNotification.php'; 

// Protect the page
requireLogin();  // This function will redirect to login if not logged in


class Dashboard
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get Total Medicines
    public function getTotalMedicines()
    {
        $query = "SELECT COUNT(*) as total FROM medicines";
        $result = $this->conn->query($query);
        return $result->fetch_assoc()['total'];
    }

    // Get Monthly Sales Data
    public function getMonthlySales()
    {
        $query = "SELECT 
                    DATE_FORMAT(s.sale_date, '%Y-%m') as month,
                    COUNT(DISTINCT bs.bill_id) as total_bills,
                    SUM(s.quantity_sold) as total_quantity,
                    SUM(s.quantity_sold * s.sale_price) as total_revenue
                  FROM sales s
                  JOIN bill_sales bs ON s.id = bs.sale_id
                  WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m')
                  ORDER BY month ASC";
        $result = $this->conn->query($query);
        $data = array();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    // Get Total Purchase Value
    public function getTotalPurchaseValue()
    {
        $query = "SELECT SUM(total_cost) as total FROM purchases";
        $result = $this->conn->query($query);
        return $result->fetch_assoc()['total'];
    }

    // Get Total Sales Value
    public function getTotalSalesValue()
    {
        $query = "SELECT SUM(s.quantity_sold * s.sale_price) as total FROM sales s";
        $result = $this->conn->query($query);
        return $result->fetch_assoc()['total'];
    }

    // Get Actual Profit (considering all purchases and sales)
    public function getActualProfit()
    {
        // First get the total purchase cost of sold items
        $query = "SELECT 
                    SUM(
                        CASE 
                            WHEN p.purchase_price IS NOT NULL THEN s.quantity_sold * p.purchase_price
                            ELSE 0
                        END
                    ) as total_cost_price
                  FROM sales s
                  LEFT JOIN purchases p ON s.medicine_id = p.medicine_id";
        
        $result = $this->conn->query($query);
        $cost_data = $result->fetch_assoc();
        $total_cost_price = $cost_data['total_cost_price'] ?? 0;

        // Get total sales revenue
        $query = "SELECT COALESCE(SUM(quantity_sold * sale_price), 0) as total_revenue FROM sales";
        $result = $this->conn->query($query);
        $sales_data = $result->fetch_assoc();
        $total_revenue = $sales_data['total_revenue'];

        // Calculate actual profit
        return $total_revenue - $total_cost_price;
    }

    // Get Category-wise Sales
    public function getCategorySales()
    {
        $query = "SELECT 
                    m.`use` as category,
                    COUNT(DISTINCT s.id) as total_sales,
                    SUM(s.quantity_sold) as total_quantity,
                    SUM(s.quantity_sold * s.sale_price) as total_revenue
                  FROM sales s
                  JOIN medicines m ON s.medicine_id = m.id
                  WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  GROUP BY m.`use`
                  ORDER BY total_revenue DESC";
        $result = $this->conn->query($query);
        return $result;
    }

    // Get Previous Functions (keeping them as they are useful)
    public function getLowStockMedicines()
    {
        $query = "SELECT name, available_quantity, `use` 
                  FROM medicines 
                  WHERE available_quantity < 10 
                  ORDER BY available_quantity ASC";
        $result = $this->conn->query($query);
        return $result;
    }

    public function getTopSellingMedicines()
    {
        $query = "SELECT 
                    m.name, 
                    m.`use`,
                    SUM(s.quantity_sold) as total_quantity,
                    SUM(s.quantity_sold * s.sale_price) as total_revenue
                  FROM sales s
                  JOIN medicines m ON s.medicine_id = m.id
                  WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  GROUP BY m.id, m.name, m.`use`
                  ORDER BY total_quantity DESC
                  LIMIT 5";
        $result = $this->conn->query($query);
        return $result;
    }

    public function getExpiringMedicines()
    {
        $query = "SELECT 
                    m.name,
                    m.`use`,
                    m.expiry_date,
                    m.available_quantity as remaining_quantity,
                    DATEDIFF(m.expiry_date, CURDATE()) as days_until_expiry
                  FROM medicines m
                  WHERE m.expiry_date IS NOT NULL
                    AND m.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                    AND m.available_quantity > 0
                  ORDER BY m.expiry_date ASC";
        $result = $this->conn->query($query);
        return $result;
    }

    public function getRecentNotifications()
    {
        $query = "SELECT 
                    n.message,
                    n.created_at,
                    n.status,
                    m.name as medicine_name
                  FROM notifications n
                  JOIN medicines m ON n.medicine_id = m.id
                  WHERE n.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                  ORDER BY n.created_at DESC
                  LIMIT 5";
        $result = $this->conn->query($query);
        return $result;
    }

    // Get Disease-wise Sales
    public function getDiseaseSales()
    {
        $query = "SELECT 
                    d.name as disease_name,
                    COUNT(DISTINCT s.id) as total_sales,
                    SUM(s.quantity_sold) as total_quantity,
                    SUM(s.quantity_sold * s.sale_price) as total_revenue,
                    COUNT(DISTINCT m.id) as unique_medicines
                  FROM sales s
                  JOIN medicines m ON s.medicine_id = m.id
                  JOIN diseases d ON m.disease_id = d.id
                  WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  GROUP BY d.id, d.name
                  ORDER BY total_revenue DESC";
        $result = $this->conn->query($query);
        return $result;
    }
}

// Initialize Dashboard
$database = new Database();
$db = $database->getConnection();
$dashboard = new Dashboard($db);

// Initialize notification system and check for expired medicines
$notifier = new ExpiryNotification($db);
$notifier->checkMedicines();

// Fetch all required data
$total_medicines = $dashboard->getTotalMedicines();
$monthly_sales = $dashboard->getMonthlySales();
$total_purchase = $dashboard->getTotalPurchaseValue();
$total_sales = $dashboard->getTotalSalesValue();
$actual_profit = $dashboard->getActualProfit();
$disease_sales = $dashboard->getDiseaseSales();
$low_stock_medicines = $dashboard->getLowStockMedicines();
$top_selling_medicines = $dashboard->getTopSellingMedicines();
$expiring_medicines = $dashboard->getExpiringMedicines();
$recent_notifications = $dashboard->getRecentNotifications();

// Remove incorrect stock calculation
// $current_stock = $total_purchase - ($total_sales - $actual_profit);

// Calculate profit
// $total_profit = $total_sales - $total_purchase;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Shop Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="/msms/assets/css/custom.css" rel="stylesheet">

</head>

<body class="bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Medical Shop Analytics</h1>
            <div class="flex space-x-4">
                <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v2h2a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                    </svg>
                    Print Report
                </button>
            </div>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 mb-8">

            <!-- Total Profit -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200 col-span-full flex items-center justify-center">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Profit (From Sold Items)</p>
                        <p class="text-2xl font-semibold <?php echo $actual_profit > 0 ? 'text-green-600' : 'text-red-600' ?>">₹<?php echo number_format($actual_profit, 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Medicines -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Medicines</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($total_medicines); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Sales -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Sales</p>
                        <p class="text-2xl font-semibold text-gray-900">₹<?php echo number_format($total_sales, 2); ?></p>
                    </div>
                </div>
            </div>
            <!-- <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Current Stock</p>
                        <p class="text-2xl font-semibold text-gray-900">₹<?php echo number_format($current_stock, 2); ?></p>
                    </div>
                </div>
            </div> -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Investment</p>
                        <p class="text-2xl font-semibold text-gray-900">₹<?php echo number_format($total_purchase, 2); ?></p>
                    </div>
                </div>
            </div>


            <!-- Low Stock Alert -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Low Stock Items</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?php
                            $low_stock_count = 0;
                            while ($low_stock_medicines->fetch_assoc()) {
                                $low_stock_count++;
                            }
                            echo $low_stock_count;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Monthly Sales Chart -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-lg font-semibold mb-4">Monthly Sales & Revenue Trend</h2>
                <canvas id="monthlySalesChart"></canvas>
            </div>

            <!-- Disease Sales Chart -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-lg font-semibold mb-4">Disease-wise Sales Distribution</h2>
                <canvas id="diseaseSalesChart"></canvas>
            </div>
        </div>

        <!-- Tables Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Top Selling Medicines -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-lg font-semibold mb-4">Top Selling Medicines</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Use</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($medicine = $top_selling_medicines->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($medicine['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($medicine['use']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right"><?php echo number_format($medicine['total_quantity']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">₹<?php echo number_format($medicine['total_revenue'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Expiring Medicines -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-lg font-semibold mb-4">Expiring Medicines</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Days Left</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($medicine = $expiring_medicines->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($medicine['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <?php echo date('M d, Y', strtotime($medicine['expiry_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right 
                                        <?php echo $medicine['days_until_expiry'] < 30 ? 'text-red-600' : 'text-yellow-600'; ?>">
                                        <?php echo $medicine['days_until_expiry']; ?> days
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <?php echo $medicine['remaining_quantity']; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notifications Section -->
        <div class="mt-8 bg-white p-6 rounded-xl shadow-md">
            <h2 class="text-lg font-semibold mb-4">Recent Notifications</h2>
            <div class="space-y-4">
                <?php while ($notification = $recent_notifications->fetch_assoc()): ?>
                    <div class="flex items-start p-4 <?php echo $notification['status'] === 'unread' ? 'bg-blue-50' : 'bg-gray-50'; ?> rounded-lg">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($notification['medicine_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <p class="text-xs text-gray-400 mt-1"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Initialize Charts -->
    <script>
        // Monthly Sales Chart with enhanced styling
        const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
        new Chart(monthlySalesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_sales, 'month')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($monthly_sales, 'total_revenue')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                }, {
                    label: 'Number of Bills',
                    data: <?php echo json_encode(array_column($monthly_sales, 'total_bills')); ?>,
                    borderColor: 'rgb(234, 88, 12)',
                    backgroundColor: 'rgba(234, 88, 12, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Monthly Sales Performance'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.datasetIndex === 0) {
                                    label += '₹' + context.parsed.y.toLocaleString();
                                } else {
                                    label += context.parsed.y.toLocaleString() + ' bills';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Number of Bills'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        // Disease Sales Chart with enhanced styling
        const diseaseSalesCtx = document.getElementById('diseaseSalesChart').getContext('2d');
        const diseaseData = {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(199, 199, 199, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(199, 199, 199, 1)'
                ],
                borderWidth: 1
            }]
        };

        <?php
        while ($disease = $disease_sales->fetch_assoc()) {
            echo "diseaseData.labels.push('" . $disease['disease_name'] . "');\n";
            echo "diseaseData.datasets[0].data.push(" . $disease['total_revenue'] . ");\n";
        }
        ?>

        new Chart(diseaseSalesCtx, {
            type: 'doughnut',
            data: diseaseData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Disease-wise Sales Distribution',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = ((value * 100) / total).toFixed(1);
                                return `${label}: ₹${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%',
                radius: '90%'
            }
        });
    </script>
</body>

</html>