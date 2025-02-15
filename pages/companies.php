<?php
// pages/companies.php
require_once '../config/database.php';
require_once '../classes/Company.php';

session_start();
require_once '../auth/auth_functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$company = new Company($db);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = '';
    $error = '';

    if (isset($_POST['add_company'])) {
        $company->name = $_POST['name'];
        $company->description = $_POST['description'];

        if ($company->create()) {
            $message = "Company added successfully.";
        } else {
            $error = "Unable to add company.";
        }
    } elseif (isset($_POST['update_company'])) {
        $company->id = $_POST['company_id'];
        $company->name = $_POST['name'];
        $company->description = $_POST['description'];

        if ($company->update()) {
            $message = "Company updated successfully.";
        } else {
            $error = "Unable to update company.";
        }
    } elseif (isset($_POST['delete_company'])) {
        $company->id = $_POST['company_id'];
        if ($company->delete()) {
            $message = "Company deleted successfully.";
        } else {
            $error = "Unable to delete company. Check if there are associated medicines.";
        }
    }
}

// Get search and sort parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';

// Fetch companies
$companies = $company->read($search, $sort_by, $sort_order);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Company Management</title>
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
                        <a href="medicines.php" class="py-4 px-2 text-gray-500 font-semibold">Medicines</a>
                        <a href="companies.php" class="py-4 px-2 text-blue-500 font-semibold hover:text-blue-500 transition duration-300  border-b-4 border-blue-500">Companies</a>
                        <a href="diseases.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-blue-500 transition duration-300">Diseases</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Add/Edit Company Form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4" id="formTitle">Add New Company</h2>
                <form method="POST" id="companyForm">
                    <input type="hidden" name="company_id" id="company_id">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="name" required
                                class="mt-1 p-2 bg-gray-50 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3"
                                class="mt-1 p-2 bg-gray-50 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                        <div class="flex justify-between">
                            <button type="submit" name="add_company" id="submitBtn"
                                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                                Add Company
                            </button>
                            <button type="reset" id="resetBtn"
                                class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                                Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Companies List -->
            <div class="lg:col-span-3">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Companies</h2>
                        <form method="GET" class="flex gap-4">
                            <input type="text" name="search" placeholder="Search companies..."
                                value="<?php echo htmlspecialchars($search); ?>"
                                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <button type="submit"
                                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                                Search
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($row = $companies->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button onclick='editCompany(<?php echo json_encode($row); ?>)'
                                                class="text-blue-600 hover:text-blue-900">Edit</button>
                                            <button onclick="deleteCompany(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')"
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
    </div>

    <script>
        function editCompany(company) {
            document.getElementById('formTitle').textContent = 'Edit Company';
            document.getElementById('company_id').value = company.id;
            document.getElementById('name').value = company.name;
            document.getElementById('description').value = company.description;
            document.getElementById('submitBtn').name = 'update_company';
            document.getElementById('submitBtn').textContent = 'Update Company';
        }

        function deleteCompany(id, name) {
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
                        <input type="hidden" name="company_id" value="${id}">
                        <input type="hidden" name="delete_company" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        document.getElementById('resetBtn').addEventListener('click', function() {
            document.getElementById('formTitle').textContent = 'Add New Company';
            document.getElementById('company_id').value = '';
            document.getElementById('submitBtn').name = 'add_company';
            document.getElementById('submitBtn').textContent = 'Add Company';
        });
    </script>
</body>

</html>