<?php
// pages/diseases.php
require_once '../config/database.php';
require_once '../classes/Disease.php';

session_start();

// Include the auth functions file
require_once '../auth/auth_functions.php';

// Protect the page
requireLogin();  // This function will redirect to login if not logged in

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Disease object
$disease = new Disease($db);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = '';
    $error = '';

    if (isset($_POST['add_disease'])) {
        $disease->name = $_POST['name'];
        $disease->description = $_POST['description'];

        if ($disease->create()) {
            $message = "Disease added successfully.";
        } else {
            $error = "Unable to add disease.";
        }
    } elseif (isset($_POST['update_disease'])) {
        $disease->id = $_POST['disease_id'];
        $disease->name = $_POST['name'];
        $disease->description = $_POST['description'];

        if ($disease->update()) {
            $message = "Disease updated successfully.";
        } else {
            $error = "Unable to update disease.";
        }
    } elseif (isset($_POST['delete_disease'])) {
        $disease->id = $_POST['disease_id'];
        if ($disease->delete()) {
            $message = "Disease deleted successfully.";
        } else {
            $error = "Unable to delete disease. Check if there are related medicines.";
        }
    }
}

// Get search and sort parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';

// Fetch diseases with search and sort
$diseases = $disease->read($search, $sort_by, $sort_order);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Disease Management</title>
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
                        <a href="companies.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-blue-500 transition duration-300">Companies</a>
                        <a href="diseases.php" class="py-4 px-2 text-blue-500 border-b-4 border-blue-500 font-semibold hover:text-blue-500 transition duration-300">Diseases</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
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
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Left Column - Forms -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4" id="formTitle">Add New Disease</h2>
                <form method="POST" id="diseaseForm">
                    <input type="hidden" name="disease_id" id="disease_id">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="name" required
                                class="mt-1 bg-gray-50 p-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="4"
                                class="mt-1 bg-gray-50 p-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                        <div class="flex justify-between">
                            <button type="submit" name="add_disease" id="submitBtn"
                                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Add Disease
                            </button>
                            <button type="reset" id="resetBtn"
                                class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Column - Diseases List -->
            <div class="lg:col-span-3">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Disease List</h2>

                        <!-- Search Bar -->
                        <form method="GET" class="flex gap-4">
                            <input type="text" name="search" placeholder="Search diseases..." value="<?php echo htmlspecialchars($search); ?>"
                                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <button type="submit"
                                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Search
                            </button>
                        </form>
                    </div>

                    <!-- Diseases Table -->
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($row = $diseases->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button onclick="editDisease(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                                class="text-blue-600 hover:text-blue-900">Edit</button>
                                            <button onclick="deleteDisease(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')"
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
        function editDisease(disease) {
            document.getElementById('formTitle').textContent = 'Edit Disease';
            document.getElementById('disease_id').value = disease.id;
            document.getElementById('name').value = disease.name;
            document.getElementById('description').value = disease.description;

            document.getElementById('submitBtn').name = 'update_disease';
            document.getElementById('submitBtn').textContent = 'Update';

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function deleteDisease(id, name) {
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
                        <input type="hidden" name="disease_id" value="${id}">
                        <input type="hidden" name="delete_disease" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        document.getElementById('resetBtn').addEventListener('click', function() {
            document.getElementById('formTitle').textContent = 'Add New Disease';
            document.getElementById('disease_id').value = '';
            document.getElementById('submitBtn').name = 'add_disease';
            document.getElementById('submitBtn').textContent = 'Add Disease';
        });
    </script>
</body>

</html>