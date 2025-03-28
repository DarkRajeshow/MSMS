<?php
require_once '../config/database.php';
require_once '../classes/Medicine.php';
require_once '../auth/auth_functions.php';

session_start();
requireLogin();

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Medicine object
$medicine = new Medicine($db);

// Fetch companies and diseases for dropdowns
$companiesQuery = "SELECT id, name FROM companies ORDER BY name";
$companiesResult = $db->query($companiesQuery);

$diseasesQuery = "SELECT id, name FROM diseases ORDER BY name";
$diseasesResult = $db->query($diseasesQuery);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = '';
    $error = '';

    if (isset($_POST['add_medicine'])) {
        $medicine->name = $_POST['name'];
        $medicine->selling_price = $_POST['selling_price'];
        $medicine->available_quantity = isset($_POST['available_quantity']) ? $_POST['available_quantity'] : 0;
        $medicine->expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $medicine->company_id = $_POST['company_id'];
        $medicine->disease_id = $_POST['disease_id'];

        if ($medicine->create()) {
            $message = "Medicine added successfully.";
        } else {
            $error = "Unable to add medicine.";
        }
    } elseif (isset($_POST['update_medicine'])) {
        $medicine->id = $_POST['medicine_id'];
        $medicine->name = $_POST['name'];
        $medicine->selling_price = $_POST['selling_price'];
        $medicine->available_quantity = isset($_POST['available_quantity']) ? $_POST['available_quantity'] : 0;
        $medicine->expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $medicine->company_id = $_POST['company_id'];
        $medicine->disease_id = $_POST['disease_id'];

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
            $error = "Unable to delete medicine. Check if there are related records.";
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="/msms/assets/css/custom.css" rel="stylesheet">

</head>

<body class="bg-gray-100">
    <?php include "../includes/navigation.php"; ?>

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex space-x-7">
                    <div class="flex items-center space-x-4">
                        <a href="medicines.php" class="py-4 px-2 text-blue-500 border-b-4 border-blue-500 font-semibold">Medicines</a>
                        <a href="companies.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-blue-500 transition duration-300">Companies</a>
                        <a href="diseases.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-blue-500 transition duration-300">Diseases</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- Stats Section -->
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

        <!-- Add/Edit Medicine Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-semibold mb-4" id="formTitle">Add New Medicine</h2>
            <form method="POST" id="medicineForm">
                <input type="hidden" name="medicine_id" id="medicine_id">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" required placeholder="Write medicine name"
                            class="mt-1 p-2 bg-gray-50 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <!-- <div>
                        <label class="block text-sm font-medium text-gray-700">Use</label>
                        <input type="text" name="use" id="use"
                            placeholder="Write medicine use"
                            class="mt-1 p-2 bg-gray-50 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div> -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Company</label>
                        <select name="company_id" id="company_id" required
                            class="mt-1 p-2 bg-gray-50 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Company</option>
                            <?php while ($company = $companiesResult->fetch_assoc()): ?>
                                <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Disease</label>
                        <select name="disease_id" id="disease_id" required
                            class="mt-1 p-2 bg-gray-50 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Disease</option>
                            <?php while ($disease = $diseasesResult->fetch_assoc()): ?>
                                <option value="<?php echo $disease['id']; ?>"><?php echo htmlspecialchars($disease['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Selling Price</label>
                        <input type="number" step="0.01" name="selling_price" id="selling_price"
                            placeholder="Enter selling price" required
                            class="mt-1 p-2 bg-gray-50 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Quantity</label>
                        <input value="0" disabled type="number" name="available_quantity" id="available_quantity" placeholder="Enter quantity" required
                            class="mt-1 p-2 bg-gray-50 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Expiry Date</label>
                        <input disabled type="date" name="expiry_date" id="expiry_date"
                            class="mt-1 p-2 bg-gray-50 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4 flex justify-between">
                    <button type="submit" name="add_medicine" id="submitBtn"
                        class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Add Medicine
                    </button>
                    <button type="reset" id="resetBtn"
                        class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Medicines List -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold">Medicines List</h2>
                <form method="GET" class="flex gap-4">
                    <input type="text" name="search" placeholder="Search medicines..." value="<?php echo htmlspecialchars($search); ?>"
                        class="rounded-md p-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Search
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?sort_by=name&sort_order=<?php echo $sort_by == 'name' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>">
                                    Medicine Name
                                    <?php if ($sort_by == 'name'): ?>
                                        <?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disease</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($row = $medicines->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['company_name'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['disease_name'] ?? 'N/A'); ?></td>
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
                                    <!-- <button onclick="editMedicine(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                        class="text-blue-600 hover:text-blue-900">Edit</button> -->
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

    <script>
        function editMedicine(medicine) {
            document.getElementById('formTitle').textContent = 'Edit Medicine';
            document.getElementById('formTitle').textContent = 'Edit Medicine';
            document.getElementById('medicine_id').value = medicine.id;
            document.getElementById('name').value = medicine.name;
            // document.getElementById('use').value = medicine.use;
            document.getElementById('selling_price').value = medicine.selling_price;
            document.getElementById('available_quantity').value = medicine.available_quantity;
            document.getElementById('available_quantity').removeAttribute('disabled');
            document.getElementById('expiry_date').value = medicine.expiry_date;
            document.getElementById('expiry_date').removeAttribute('disabled');
            document.getElementById('company_id').value = medicine.company_id;
            document.getElementById('disease_id').value = medicine.disease_id;

            document.getElementById('submitBtn').name = 'update_medicine';
            document.getElementById('submitBtn').textContent = 'Update';

            let qtyField = document.getElementById('available_quantity');
            let expiryField = document.getElementById('expiry_date');

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
            document.getElementById('company_id').value = '';
            document.getElementById('disease_id').value = '';
        });

        // Form validation
        document.getElementById('medicineForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const company = document.getElementById('company_id').value;
            const disease = document.getElementById('disease_id').value;
            const price = parseFloat(document.getElementById('selling_price').value);

            let errors = [];

            if (!name) errors.push('Medicine name is required');
            if (!company) errors.push('Please select a company');
            if (!disease) errors.push('Please select a disease');
            if (!price || price <= 0) errors.push('Selling price must be greater than 0');

            if (errors.length > 0) {
                e.preventDefault();
                Swal.fire({
                    title: 'Validation Error',
                    html: errors.join('<br>'),
                    icon: 'error'
                });
            }
        });

        // Show success/error messages using SweetAlert2
        <?php if (!empty($message)): ?>
            Swal.fire({
                title: 'Success',
                text: '<?php echo $message; ?>',
                icon: 'success'
            });
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            Swal.fire({
                title: 'Error',
                text: '<?php echo $error; ?>',
                icon: 'error'
            });
        <?php endif; ?>
    </script>
</body>

</html>