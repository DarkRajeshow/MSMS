<?php
// pages/bill_details.php
session_start();
require_once '../config/database.php';
require_once '../classes/Sale.php';


// Include the auth functions file
require_once '../auth/auth_functions.php'; 

// Protect the page
requireLogin();  // This function will redirect to login if not logged in


if (!isLoggedIn()) {
    header('Location: /msms/pages/login.php');
    exit();
}


$database = new Database();
$db = $database->getConnection();
$sale = new Sale($db);

$bill_id = isset($_GET['id']) ? $_GET['id'] : null;
$bill_details = null;
$error = null;

if ($bill_id) {
    $bill_details = $sale->getBillDetails($bill_id);
    if (!$bill_details) {
        $error = "Bill not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bill Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/msms/assets/css/custom.css" rel="stylesheet">
</head>

<body class="bg-gray-100 p-6">
    <div class="container mx-auto max-w-3xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Bill Details</h1>
            <a href="sales.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Back to Sales
            </a>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif ($bill_details): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="border-b pb-4 mb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600">Bill Number</p>
                            <p class="font-semibold">#<?php echo $bill_details['bill_id']; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Date</p>
                            <p class="font-semibold"><?php echo $bill_details['bill_date']; ?></p>
                        </div>
                    </div>
                </div>

                <table class="w-full mb-4">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Item</th>
                            <th class="text-right py-2">Quantity</th>
                            <th class="text-right py-2">Price</th>
                            <th class="text-right py-2">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bill_details['items'] as $item): ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                                <td class="text-right py-2"><?php echo $item['quantity']; ?></td>
                                <td class="text-right py-2">₹<?php echo number_format($item['price'], 2); ?></td>
                                <td class="text-right py-2">₹<?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td colspan="3" class="text-right py-4">Total Amount:</td>
                            <td class="text-right py-4">₹<?php echo number_format($bill_details['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="text-center mt-8">
                    <button onclick="window.print()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Print Bill
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>