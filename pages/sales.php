<?php
// pages/sales.php
require_once '../config/database.php';
require_once '../classes/Sale.php';


session_start();

// Include the auth functions file
require_once '../auth/auth_functions.php'; 

// Protect the page
requireLogin();  // This function will redirect to login if not logged in



$database = new Database();
$db = $database->getConnection();
$sale = new Sale($db);

$sale_result = null;
$error = null;
$bill_details = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_sale'])) {
        $sales_items = [];
        $customer_name = $_POST['customer_name'] ?? '';

        foreach ($_POST['medicine_id'] as $index => $medicine_id) {
            if (!empty($medicine_id) && $_POST['quantity'][$index] > 0) {
                $sales_items[] = [
                    'medicine_id' => $medicine_id,
                    'quantity' => $_POST['quantity'][$index]
                ];
            }
        }
        if (!empty($sales_items)) {
            $sale_result = $sale->create($sales_items, $customer_name);
            if ($sale_result['success']) {
                $bill_details = $sale->getBillDetails($sale_result['bill_id']);
            } else {
                $error = $sale_result['error'];
            }
        } else {
            $error = "Please add at least one item to the sale.";
        }
    }
}

// At the top of your sales page PHP file, get the filter parameters
$filters = [];
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// Then pass these filters to your getSalesHistory method
// $sales = $salesManager->getSalesHistory($filters);

// Get medicines and sales history
$medicines = $sale->getMedicinesForSale();
$sales_history = $sale->getSalesHistory($filters);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management - Medical Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="/msms/assets/css/custom.css" rel="stylesheet">

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            .print-break-inside-avoid {
                break-inside: avoid;
            }
        }

        .print-only {
            display: none;
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stock-low {
            @apply bg-red-100 text-red-800;
        }

        .stock-medium {
            @apply bg-yellow-100 text-yellow-800;
        }

        .stock-high {
            @apply bg-green-100 text-green-800;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/navigation.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="no-print">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Sales Management</h1>
                <div class="flex space-x-4">
                    <button onclick="document.getElementById('newSaleForm').scrollIntoView({behavior: 'smooth'})"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow-sm hover:bg-blue-700 
                                   transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>New Sale
                    </button>
                </div>
            </div>

            <?php if ($sale_result && $sale_result['success']): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-8 rounded-r-lg animate-fade-in">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-green-800">Sale Completed Successfully</h3>
                            <div class="mt-2 text-green-700">
                                <p>Bill #<?php echo $sale_result['bill_id']; ?></p>
                                <p>Total Amount: ₹<?php echo number_format($sale_result['total_amount'], 2); ?></p>
                            </div>
                            <div class="mt-4">
                                <button onclick="viewBill(<?php echo $sale_result['bill_id']; ?>)"
                                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 
                                               transition-colors duration-200">
                                    View Bill
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg animate-fade-in">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-red-800">Error</h3>
                            <div class="mt-2 text-red-700">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- New Sale Form -->
            <form id="newSaleForm" method="POST" class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-6">New Sale</h2>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                    <input type="text"
                        name="customer_name"
                        class="block w-full bg-zinc-50 px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div id="sale-items" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="relative h-40">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                            <select name="medicine_id[]"
                                class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required
                                onchange="updatePrice(this)">
                                <option value="">Select Medicine</option>
                                <?php
                                mysqli_data_seek($medicines, 0);
                                while ($medicine = $medicines->fetch_assoc()):
                                    // Debug: Print the expiry_date value
                                    echo "<!-- Debug: Expiry Date for " . $medicine['name'] . ": " . $medicine['expiry_date'] . " -->";

                                    $stock_class = '';
                                    $disabled = ''; // Variable to handle disabled state
                                    $current_date = date('Y-m-d'); // Get the current date
                                    if ($medicine['available_quantity'] <= 10) {
                                        $stock_class = 'stock-low';
                                    } elseif ($medicine['available_quantity'] <= 30) {
                                        $stock_class = 'stock-medium';
                                    }

                                    // Check if expiry_date is available and valid
                                    if (!empty($medicine['expiry_date'])) {
                                        // Convert expiry date from "YYYY-MM-DD" format to "YYYY-MM-DD"
                                        $expiry_date = $medicine['expiry_date']; // No need for parsing since it's already in "YYYY-MM-DD"

                                        // Check if the medicine is expired
                                        if (strtotime($expiry_date) < strtotime($current_date)) {
                                            $disabled = 'disabled'; // Disable the option if expired
                                        }
                                    } else {
                                        // If expiry_date is empty, skip or set disabled flag (optional)
                                        $disabled = 'disabled';
                                    }
                                ?>
                                    <option value="<?php echo $medicine['id']; ?>"
                                        data-price="<?php echo $medicine['selling_price']; ?>"
                                        data-stock="<?php echo $medicine['available_quantity']; ?>"
                                        class="<?php echo $stock_class; ?>"
                                        <?php echo $disabled; ?>>
                                        <?php echo htmlspecialchars($medicine['name']); ?>
                                        (Stock: <?php echo $medicine['available_quantity']; ?>)
                                        <?php if ($disabled): ?> - Expired<?php endif; ?> <!-- Optional: Add "Expired" text -->
                                    </option>
                                <?php endwhile; ?>
                            </select>

                            <div class="price-display text-sm text-gray-600 mt-2"></div>
                            <div class="stock-warning hidden mt-2 text-sm text-red-600"></div>
                        </div>

                        <div class="h-40">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <input type="number"
                                name="quantity[]"
                                min="1"
                                class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required
                                onchange="validateQuantity(this)">
                        </div>

                        <div class="flex h-40 items-start pt-7">
                            <button type="button"
                                onclick="addSaleItem()"
                                class="w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i>Add Item
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Total Items: <span id="totalItems">1</span>
                    </div>
                    <button type="submit"
                        name="create_sale"
                        class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        <i class="fas fa-check mr-2"></i>Complete Sale
                    </button>
                </div>
            </form>

            <!-- Sales History -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Sales History</h2>
                    <div class="flex space-x-4">
                        <div>
                            <label for="dateFrom">From Date:</label>
                            <input type="date"
                                id="dateFrom"
                                value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>"
                                class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="dateTo">To Date:</label>
                            <input type="date"
                                id="dateTo"
                                value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>"
                                class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <button onclick="filterSales()"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                            Apply Filter
                        </button>
                        <button onclick="clearFilters()"
                            class="bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 transition-colors duration-200">
                            Clear Filters
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 text-left">
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Bill #</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Name</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php
                            $current_bill = null;
                            $bill_items = [];
                            while ($row = $sales_history->fetch_assoc()):
                                if ($current_bill !== $row['bill_id']):
                                    if ($current_bill !== null):
                            ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                #<?php echo $current_bill; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($customer_name); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($bill_date)); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo count($bill_items); ?> items
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                                ₹<?php echo number_format($bill_total, 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <button onclick="viewBill(<?php echo $current_bill; ?>)"
                                                    class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 transition-colors duration-200">
                                                    View Bill
                                                </button>
                                            </td>
                                        </tr>
                                <?php
                                    endif;
                                    $current_bill = $row['bill_id'];
                                    $bill_date = $row['sale_date'];
                                    $customer_name = $row['customer_name'];
                                    $bill_total = $row['bill_total'];
                                    $bill_items = [];
                                endif;
                                $bill_items[] = $row;
                            endwhile;

                            // Display the last bill
                            if ($current_bill !== null):
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        #<?php echo $current_bill; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($customer_name); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($bill_date)); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo count($bill_items); ?> items
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        ₹<?php echo number_format($bill_total, 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button onclick="viewBill(<?php echo $current_bill; ?>)"
                                            class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 transition-colors duration-200">
                                            View Bill
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Enhanced Bill Modal -->
        <div id="billModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
            <div class="container mx-auto h-full flex items-center justify-center p-4">
                <div class="bg-white rounded-xl shadow-2xl p-8 max-w-2xl w-full">
                    <div class="flex justify-between items-center mb-6 no-print">
                        <h2 class="text-2xl font-bold text-gray-900">Bill Details</h2>
                        <div class="flex space-x-4">
                            <button onclick="printBill()"
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                            <button onclick="closeBillModal()"
                                class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors duration-200">
                                Close
                            </button>
                        </div>
                    </div>
                    <div id="billModalContent" class="print-break-inside-avoid"></div>
                </div>
            </div>
        </div>


    </div>

    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    <script>
        // Enhanced medicine selection validation
        function filterSales() {
            const dateFrom = document.getElementById("dateFrom").value;
            const dateTo = document.getElementById("dateTo").value;

            // Validate dates
            if (dateFrom && dateTo && dateFrom > dateTo) {
                alert("'From Date' cannot be later than 'To Date'");
                return;
            }

            // Build the query string
            const params = new URLSearchParams();

            if (dateFrom) {
                params.append('date_from', dateFrom);
            }
            if (dateTo) {
                params.append('date_to', dateTo);
            }

            // Preserve other existing query parameters if needed
            const currentParams = new URLSearchParams(window.location.search);
            for (const [key, value] of currentParams) {
                if (key !== 'date_from' && key !== 'date_to') {
                    params.append(key, value);
                }
            }

            // Redirect with the new query string
            const newUrl = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
            window.location.href = newUrl;
        }

        function clearFilters() {
            document.getElementById("dateFrom").value = '';
            document.getElementById("dateTo").value = '';

            // Remove only the date filters from the URL
            const params = new URLSearchParams(window.location.search);
            params.delete('date_from');
            params.delete('date_to');

            const newUrl = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
            window.location.href = newUrl;
        }


        function addSaleItem() {
            const container = document.getElementById('sale-items');
            const template = document.querySelector('#medicine-template').content.cloneNode(true);

            // Reset values
            template.querySelectorAll('select, input').forEach(el => el.value = '');
            template.querySelector('.price-display').textContent = '';
            template.querySelector('.stock-warning').classList.add('hidden');

            container.appendChild(template);
            updateTotalItems();
        }

        function removeSaleItem(button) {
            const itemContainer = button.closest('.grid');
            // Only remove if there's more than one item
            if (document.querySelectorAll('#sale-items > .grid').length > 1) {
                itemContainer.remove();
                updateTotalItems();
            }
        }

        function updateTotalItems() {
            const totalItems = document.querySelectorAll('#sale-items > .grid').length;
            document.getElementById('totalItems').textContent = totalItems;
        }


        async function viewBill(billId) {
            const modal = document.getElementById('billModal');
            const modalContent = document.getElementById('billModalContent');
            modal.classList.remove('hidden');
            modalContent.innerHTML = '<div class="flex justify-center"><div class="loader">Loading...</div></div>';
            try {
                const response = await fetch(`get_bill.php?id=${billId}`);
                const billDetails = await response.json();
                modalContent.innerHTML = `
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Medical Store</h2>
                <p class="text-gray-600">Bill #${billDetails.bill_id}</p>
                <p class="text-gray-600">${billDetails.formatted_date}</p>
                <p class="text-gray-600">Customer: ${billDetails.customer_name || 'N/A'}</p>
            </div>
            
            <table class="w-full mb-6">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-3">Item</th>
                        <th class="text-right py-3">Qty</th>
                        <th class="text-right py-3">Price</th>
                        <th class="text-right py-3">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    ${billDetails.items.map(item => `
                        <tr>
                            <td class="py-3">
                                <div class="font-medium">${item.medicine_name}</div>
                                <div class="text-sm text-gray-500">${item.medicine_use || ''}</div>
                            </td>
                            <td class="text-right py-3">${item.quantity}</td>
                            <td class="text-right py-3">₹${parseFloat(item.price).toFixed(2)}</td>
                            <td class="text-right py-3">₹${(item.quantity * item.price).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 font-bold">
                        <td colspan="3" class="text-right py-4">Total Amount:</td>
                        <td class="text-right py-4">₹${parseFloat(billDetails.total_amount).toFixed(2)}</td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="text-center text-gray-500 text-sm">
                Thank you for your purchase!
            </div>
        `;
            } catch (error) {
                modalContent.innerHTML = '<div class="text-red-500 text-center">Error loading bill details</div>';
            }
        }

        function closeBillModal() {
            document.getElementById('billModal').classList.add('hidden');
        }

        function printBill() {
            window.print();
        }

        function toggleFilters() {
            const filterSection = document.querySelector('.sales-filters');
            filterSection.classList.toggle('hidden');
        }

        // Enhanced price calculation
        function calculateSubtotal(row) {
            const quantity = parseInt(row.querySelector('input[type="number"]').value) || 0;
            const select = row.querySelector('select');
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.value ? parseFloat(selectedOption.dataset.price) : 0;

            return quantity * price;
        }

        // Real-time total calculation
        function updateTotalAmount() {
            const rows = document.querySelectorAll('#sale-items > div');
            let total = 0;

            rows.forEach(row => {
                total += calculateSubtotal(row);
            });

            const totalDisplay = document.getElementById('totalAmount');
            if (totalDisplay) {
                totalDisplay.textContent = `₹${total.toFixed(2)}`;
            }
        }



        function validateMedicineSelection(selectElement) {
            const container = selectElement.closest('div');
            const stockWarning = container.querySelector('.stock-warning');
            const selectedOption = selectElement.options[selectElement.selectedIndex];

            if (selectedOption.value) {
                const stock = parseInt(selectedOption.dataset.stock);
                const expiryDate = selectedOption.dataset.expiry;
                const today = new Date().toISOString().split('T')[0];

                // Check expiry
                if (expiryDate && expiryDate <= today) {
                    stockWarning.textContent = `❌ EXPIRED: Medicine expired on ${expiryDate}`;
                    stockWarning.classList.remove('hidden');
                    stockWarning.classList.add('text-red-600');
                    selectedOption.disabled = true;
                    selectElement.value = ''; // Reset selection
                    return false;
                }

                // Check stock
                if (stock <= 10) {
                    stockWarning.textContent = `⚠️ Low stock: Only ${stock} units remaining`;
                    stockWarning.classList.remove('hidden');
                    stockWarning.classList.add('text-yellow-600');
                }
            }
            return true;
        }

        // Update medicine dropdown to include expiry information
        function updateMedicineDropdowns() {
            const selects = document.querySelectorAll('select[name="medicine_id[]"]');
            selects.forEach(select => {
                Array.from(select.options).forEach(option => {
                    if (option.dataset.expiry && option.value) {
                        const today = new Date().toISOString().split('T')[0];
                        if (option.dataset.expiry <= today) {
                            option.classList.add('text-red-600');
                            option.text += ' (EXPIRED)';
                        }
                    }
                });
            });
        }

        // Enhanced form submission validation
        function validateSaleForm(event) {
            const rows = document.querySelectorAll('#sale-items > div');
            let isValid = true;
            let errorMessages = [];

            rows.forEach((row, index) => {
                const select = row.querySelector('select');
                const quantityInput = row.querySelector('input[type="number"]');
                const selectedOption = select.options[select.selectedIndex];

                // Check medicine selection
                if (!select.value) {
                    errorMessages.push(`Please select a medicine for item ${index + 1}`);
                    isValid = false;
                }

                // Check expiry date
                if (selectedOption.value) {
                    const expiryDate = selectedOption.dataset.expiry;
                    const today = new Date().toISOString().split('T')[0];

                    if (expiryDate && expiryDate <= today) {
                        errorMessages.push(`Medicine ${selectedOption.text} has expired and cannot be sold`);
                        isValid = false;
                    }
                }

                // Quantity validation
                const quantity = parseInt(quantityInput.value) || 0;
                const availableStock = parseInt(selectedOption.dataset.stock) || 0;

                if (quantity <= 0) {
                    errorMessages.push(`Please enter a valid quantity for item ${index + 1}`);
                    isValid = false;
                }

                if (quantity > availableStock) {
                    errorMessages.push(`Insufficient stock for ${selectedOption.text}. Available: ${availableStock}`);
                    isValid = false;
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert(errorMessages.join('\n'));
            }

            return isValid;
        }

        // Event listeners and initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Add form validation on submission
            const form = document.getElementById('newSaleForm');
            form.addEventListener('submit', validateSaleForm);

            // Update medicine dropdowns with expiry info
            updateMedicineDropdowns();

            // Add event listener for medicine selection
            document.addEventListener('change', function(e) {
                if (e.target.matches('select[name="medicine_id[]"]')) {
                    validateMedicineSelection(e.target);
                }
            });
        });
    </script>

    <!-- Template for new sale items -->
    <template id="medicine-template">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="relative">
                <!-- Medicine select field remains the same -->
                <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                <select name="medicine_id[]"
                    class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    required
                    onchange="updatePrice(this)">
                    <option value="">Select Medicine</option>
                    <?php
                    mysqli_data_seek($medicines, 0);
                    while ($medicine = $medicines->fetch_assoc()):
                        // Debug: Print the expiry_date value
                        echo "<!-- Debug: Expiry Date for " . $medicine['name'] . ": " . $medicine['expiry_date'] . " -->";

                        $stock_class = '';
                        $disabled = ''; // Variable to handle disabled state
                        $current_date = date('Y-m-d'); // Get the current date
                        if ($medicine['available_quantity'] <= 10) {
                            $stock_class = 'stock-low';
                        } elseif ($medicine['available_quantity'] <= 30) {
                            $stock_class = 'stock-medium';
                        }

                        // Check if expiry_date is available and valid
                        if (!empty($medicine['expiry_date'])) {
                            // Convert expiry date from "YYYY-MM-DD" format to "YYYY-MM-DD"
                            $expiry_date = $medicine['expiry_date']; // No need for parsing since it's already in "YYYY-MM-DD"

                            // Check if the medicine is expired
                            if (strtotime($expiry_date) < strtotime($current_date)) {
                                $disabled = 'disabled'; // Disable the option if expired
                            }
                        } else {
                            // If expiry_date is empty, skip or set disabled flag (optional)
                            $disabled = 'disabled';
                        }
                    ?>
                        <option value="<?php echo $medicine['id']; ?>"
                            data-price="<?php echo $medicine['selling_price']; ?>"
                            data-stock="<?php echo $medicine['available_quantity']; ?>"
                            class="<?php echo $stock_class; ?>"
                            <?php echo $disabled; ?>>
                            <?php echo htmlspecialchars($medicine['name']); ?>
                            (Stock: <?php echo $medicine['available_quantity']; ?>)
                            <?php if ($disabled): ?> - Expired<?php endif; ?> <!-- Optional: Add "Expired" text -->
                        </option>
                    <?php endwhile; ?>
                </select>
                <div class="price-display text-sm text-gray-600 mt-2"></div>
                <div class="stock-warning hidden mt-2 text-sm text-red-600"></div>
            </div>
            <div>
                <!-- Quantity input remains the same -->
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                <input type="number"
                    name="quantity[]"
                    min="1"
                    class="block w-full h-12 px-4 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    required
                    onchange="validateQuantity(this)">
            </div>
            <div class="flex items-center">
                <button type="button"
                    onclick="addSaleItem()"
                    class="w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>Add Item
                </button>
            </div>
            <div class="flex items-center">
                <button type="button"
                    onclick="removeSaleItem(this)"
                    class="w-full bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-200">
                    <i class="fas fa-trash mr-2"></i>Remove
                </button>
            </div>
        </div>
    </template>
</body>

</html>