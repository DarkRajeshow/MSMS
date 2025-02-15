<?php
// pages/medicines.php
require_once '../config/database.php';
require_once '../classes/Medicine.php';


session_start();

// Include the auth functions file
require_once '../auth/auth_functions.php'; 

// Protect the page
requireLogin();  // This function will redirect to login if not logged in



// Create database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Medicine object
$medicine = new Medicine($db);
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = '';
    $error = '';

    if (isset($_POST['add_medicine'])) {
        // Check if available_quantity is set before using it
        $medicine->name = $_POST['name'];
        $medicine->use = $_POST['use'];
        $medicine->selling_price = $_POST['selling_price'];
        $medicine->available_quantity = isset($_POST['available_quantity']) ? $_POST['available_quantity'] : 0; // Default to 0 if not set
        $medicine->expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

        if ($medicine->create()) {
            $message = "Medicine added successfully.";
        } else {
            $error = "Unable to add medicine.";
        }
    } elseif (isset($_POST['update_medicine'])) {
        // Check if available_quantity is set before using it
        $medicine->id = $_POST['medicine_id'];
        $medicine->name = $_POST['name'];
        $medicine->use = $_POST['use'];
        $medicine->selling_price = $_POST['selling_price'];
        $medicine->available_quantity = isset($_POST['available_quantity']) ? $_POST['available_quantity'] : 0; // Default to 0 if not set
        $medicine->expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

        if ($medicine->update()) {
            $message = "Medicine updated successfully.";
        } else {
            $error = "Unable to update medicine.";
        }
    } elseif (isset($_POST['delete_medicine'])) {
        $medicine->id = $_POST['medicine_id'];
        if ($medicine->delete()) {
            $message = "Medicine deleted successfully.";
        } else {
            $error = "Unable to delete medicine. Check if there are related sales records.";
        }
    }
}

// Get search and sort parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';

// Fetch medicines with search and sort
$medicines = $medicine->read($search, $sort_by, $sort_order);

// Get statistics
$stats = $medicine->getStatistics();
$expired = $medicine->getExpired();
// Get low stock and expiring medicines
$lowStock = $medicine->getLowStock(10);
$expiring = $medicine->getExpiring(30);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Medicine Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="/msms/assets/css/custom.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <?php include "../includes/navigation.php"; ?>

    <div class="container mx-auto p-6">
        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-700">Total Medicines</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_medicines']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-700">Out of Stock</h3>
                <p class="text-3xl font-bold text-red-600"><?php echo $stats['out_of_stock']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-700">Total Stock Value</h3>
                <p class="text-3xl font-bold text-green-600">₹<?php echo number_format($stats['total_stock_value'], 2); ?></p>
            </div>
        </div>

        <!-- Alerts Section -->
        <?php if (!empty($message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Main Content Grid -->
        <div class="">
            <!-- Left Column - Forms -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Add/Edit Medicine Form -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-semibold mb-4" id="formTitle">Add New Medicine</h2>
                    <form method="POST" id="medicineForm">
                        <input type="hidden" name="medicine_id" id="medicine_id">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" name="name" id="name" required
                                    class="mt-1 block px-2 py-1.5 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Use</label>
                                <input type="text" name="use" id="use"
                                    class="mt-1 block px-2 py-1.5 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Selling Price</label>
                                <input type="number" step="0.01" name="selling_price" id="selling_price" required
                                    class="mt-1 block px-2 py-1.5 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Quantity</label>
                                <input value="0" disabled type="number" name="available_quantity" id="available_quantity" required
                                    class="mt-1 block px-2 py-1.5 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Expiry Date</label>
                                <input type="date" name="expiry_date" id="expiry_date"
                                    class="mt-1 block px-2 py-1.5 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div class="flex justify-between">
                                <button type="submit" name="add_medicine" id="submitBtn"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Add Medicine
                                </button>
                                <button type="reset" id="resetBtn"
                                    class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- Right Column - Medicines List -->
                <div class="lg:col-span-3">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold">Medicines in Stock</h2>

                            <!-- Search Bar -->
                            <form method="GET" class="flex gap-4">
                                <input type="text" name="search" placeholder="Search medicines..." value="<?php echo htmlspecialchars($search); ?>"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-2">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Search
                                </button>
                            </form>
                        </div>

                        <!-- Medicines Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <a href="?sort_by=name&sort_order=<?php echo $sort_by == 'name' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>">
                                                Name
                                                <?php if ($sort_by == 'name'): ?>
                                                    <?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Use</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <a href="?sort_by=selling_price&sort_order=<?php echo $sort_by == 'selling_price' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>">
                                                Price
                                                <?php if ($sort_by == 'selling_price'): ?>
                                                    <?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <a href="?sort_by=available_quantity&sort_order=<?php echo $sort_by == 'available_quantity' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>">
                                                Quantity
                                                <?php if ($sort_by == 'available_quantity'): ?>
                                                    <?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <a href="?sort_by=expiry_date&sort_order=<?php echo $sort_by == 'expiry_date' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>">
                                                Expiry
                                                <?php if ($sort_by == 'expiry_date'): ?>
                                                    <?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($row = $medicines->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['use']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">₹<?php echo number_format($row['selling_price'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="<?php echo $row['available_quantity'] <= 10 ? 'text-red-600 font-semibold' : ''; ?>">
                                                    <?php echo $row['available_quantity']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($row['expiry_date']): ?>
                                                    <span class="<?php echo strtotime($row['expiry_date']) <= strtotime('+30 days') ? 'text-red-600 font-semibold' : ''; ?>">
                                                        <?php echo date('d M Y', strtotime($row['expiry_date'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                <button onclick="editMedicine(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                                    class="text-blue-600 hover:text-blue-900">Edit</button>
                                                <button onclick="deleteMedicine(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')"
                                                    class="text-red-600 hover:text-red-900">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Alerts Section -->
            <div class="bg-white p-6 rounded-lg shadow-md space-y-6">
                <h2 class="text-xl font-semibold mb-4">Alerts</h2>

                <!-- Expired Medicines Alerts -->
                <?php if ($expired->num_rows > 0): ?>
                    <div class="bg-red-100 p-4 rounded-md">
                        <h3 class="text-lg font-medium text-red-900 mb-2">
                            <span class="inline-block w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                            Expired Medicines
                        </h3>
                        <ul class="space-y-2">
                            <?php while ($row = $expired->fetch_assoc()): ?>
                                <li class="text-red-800">
                                    <span class="font-medium"><?php echo htmlspecialchars($row['name']); ?></span> -
                                    Expired on <?php echo date('d M Y', strtotime($row['expiry_date'])); ?>
                                    <?php if ($row['available_quantity'] > 0): ?>
                                        (<?php echo $row['available_quantity']; ?> units remaining)
                                    <?php endif; ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Expiring Soon Alerts -->
                <?php if ($expiring->num_rows > 0): ?>
                    <div class="bg-yellow-100 p-4 rounded-md">
                        <h3 class="text-lg font-medium text-yellow-900 mb-2">
                            <span class="inline-block w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                            Expiring Soon (Next 30 Days)
                        </h3>
                        <ul class="space-y-2">
                            <?php while ($row = $expiring->fetch_assoc()): ?>
                                <li class="text-yellow-800">
                                    <span class="font-medium"><?php echo htmlspecialchars($row['name']); ?></span> -
                                    Expires on <?php echo date('d M Y', strtotime($row['expiry_date'])); ?>
                                    (<?php echo $row['available_quantity']; ?> units remaining)
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Low Stock Alerts -->
                <?php if ($lowStock->num_rows > 0): ?>
                    <div class="bg-blue-100 p-4 rounded-md">
                        <h3 class="text-lg font-medium text-blue-900 mb-2">
                            <span class="inline-block w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                            Low Stock Medicines (10 or fewer units)
                        </h3>
                        <ul class="space-y-2">
                            <?php while ($row = $lowStock->fetch_assoc()): ?>
                                <li class="text-blue-800">
                                    <span class="font-medium"><?php echo htmlspecialchars($row['name']); ?></span> -
                                    Only <?php echo $row['available_quantity']; ?> units left
                                    <?php if ($row['expiry_date']): ?>
                                        (Expires: <?php echo date('d M Y', strtotime($row['expiry_date'])); ?>)
                                    <?php endif; ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($expired->num_rows == 0 && $expiring->num_rows == 0 && $lowStock->num_rows == 0): ?>
                    <div class="bg-green-100 p-4 rounded-md">
                        <p class="text-green-800">No alerts at this time. All stock levels and expiry dates are within normal ranges.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function editMedicine(medicine) {
            document.getElementById('formTitle').textContent = 'Edit Medicine';
            document.getElementById('medicine_id').value = medicine.id;
            document.getElementById('name').value = medicine.name;
            document.getElementById('use').value = medicine.use;
            document.getElementById('selling_price').value = medicine.selling_price;
            document.getElementById('available_quantity').value = medicine.available_quantity;
            document.getElementById('available_quantity').disabled = false;
            document.getElementById('expiry_date').value = medicine.expiry_date;

            document.getElementById('submitBtn').name = 'update_medicine';
            document.getElementById('submitBtn').textContent = 'Update';

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function deleteMedicine(id, name) {
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to delete ${name}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="medicine_id" value="${id}">
                        <input type="hidden" name="delete_medicine" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        document.getElementById('resetBtn').addEventListener('click', function() {
            document.getElementById('formTitle').textContent = 'Add New Medicine';
            document.getElementById('medicine_id').value = '';
            document.getElementById('submitBtn').name = 'add_medicine';
            document.getElementById('submitBtn').textContent = 'Add Medicine';
        });
    </script>
</body>

</html>