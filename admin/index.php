<?php
session_start();

// --- Configuration ---
$admin_password = 'password123'; // CHANGE THIS!
$data_file = __DIR__ . '/../data.json';

// --- Login Logic ---
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['is_logged_in'] = true;
        $is_logged_in = true;
        // Redirect to avoid form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Invalid password.';
    }
}

// --- Logout Logic ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// --- Load Data (only if logged in) ---
$data = ['categories' => [], 'products' => []];
if ($is_logged_in && file_exists($data_file)) {
    $data = json_decode(file_get_contents($data_file), true);
}

// --- Helper function to find category name by ID ---
function getCategoryName($categories, $categoryId) {
    foreach ($categories as $category) {
        if ($category['id'] === $categoryId) {
            return $category['name'];
        }
    }
    return 'N/A';
}

// --- Login Form ---
if (!$is_logged_in) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 flex items-center justify-center h-screen">
        <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
            <h1 class="text-2xl font-bold text-center mb-6">Admin Login</h1>
            <form method="POST">
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                    <input type="password" name="password" id="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <?php if (isset($login_error)): ?>
                    <p class="text-red-500 text-xs italic mb-4"><?php echo $login_error; ?></p>
                <?php endif; ?>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Log In
                    </button>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>

<!-- ======================================================= -->
<!-- =============   START: ADMIN DASHBOARD   ============== -->
<!-- ======================================================= -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

    <div class="flex justify-between items-center bg-white shadow-md p-4">
        <h1 class="text-2xl font-bold">Admin Dashboard</h1>
        <a href="?logout=true" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
            Logout
        </a>
    </div>

    <div class="container mx-auto p-4 md:p-8">

        <!-- Status Message -->
        <div id="status-message" class="hidden p-4 mb-4 text-sm text-white rounded-lg" role="alert">
            <span class="font-medium"></span>
        </div>

        <!-- Manage Categories -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">Manage Categories</h2>
            <form id="add-category-form" class="mb-6 flex gap-4 items-end">
                <div>
                    <label for="category-name" class="block text-sm font-medium text-gray-700">Category Name</label>
                    <input type="text" id="category-name" name="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                </div>
                <div>
                    <label for="category-icon" class="block text-sm font-medium text-gray-700">Font Awesome Icon</label>
                    <input type="text" id="category-icon" name="icon" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g., fas fa-book" required>
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Add Category</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categories-table-body" class="bg-white divide-y divide-gray-200">
                        <?php foreach ($data['categories'] as $category): ?>
                            <tr id="category-<?php echo htmlspecialchars($category['id']); ?>">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($category['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><i class="<?php echo htmlspecialchars($category['icon']); ?>"></i> (<?php echo htmlspecialchars($category['icon']); ?>)</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="deleteItem('category', '<?php echo htmlspecialchars($category['id']); ?>')" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Manage Products -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Manage Products</h2>
            <button onclick="openModal('add-product-modal')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mb-6">
                Add New Product
            </button>

             <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="products-table-body" class="bg-white divide-y divide-gray-200">
                        <?php foreach ($data['products'] as $product): ?>
                            <tr id="product-<?php echo htmlspecialchars($product['id']); ?>">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo getCategoryName($data['categories'], $product['categoryId']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">$<?php echo htmlspecialchars($product['price']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $product['stock_out'] ? '<span class="text-red-500">Out</span>' : '<span class="text-green-500">In Stock</span>'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick='openEditModal(<?php echo json_encode($product); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                    <button onclick="deleteItem('product', '<?php echo htmlspecialchars($product['id']); ?>')" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="add-product-modal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="product-form">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Add Product</h3>
                        <input type="hidden" name="id" id="product-id">
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="product-name" class="block text-sm font-medium text-gray-700">Product Name</label>
                                <input type="text" name="name" id="product-name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label for="product-category" class="block text-sm font-medium text-gray-700">Category</label>
                                <select name="categoryId" id="product-category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                    <?php foreach ($data['categories'] as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="product-description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea name="description" id="product-description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                            </div>
                            <div>
                                <label for="product-price" class="block text-sm font-medium text-gray-700">Price</label>
                                <input type="number" name="price" id="product-price" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                             <div>
                                <label for="product-icon" class="block text-sm font-medium text-gray-700">Remix Icon Class</label>
                                <input type="text" name="icon" id="product-icon" placeholder="e.g., ri-html5-fill" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                             <div>
                                <label for="product-color" class="block text-sm font-medium text-gray-700">Tailwind Gradient</label>
                                <input type="text" name="color" id="product-color" placeholder="e.g., from-blue-400 to-blue-600" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="stock_out" id="product-stock_out" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                <label for="product-stock_out" class="ml-2 block text-sm text-gray-900">Mark as Stock Out</label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                        <button type="button" onclick="closeModal('add-product-modal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    const API_URL = '../api/admin_api.php';

    function showStatus(message, isSuccess) {
        const statusDiv = document.getElementById('status-message');
        statusDiv.querySelector('span').textContent = message;
        statusDiv.className = `p-4 mb-4 text-sm text-white rounded-lg ${isSuccess ? 'bg-green-500' : 'bg-red-500'}`;
        statusDiv.classList.remove('hidden');
        setTimeout(() => {
            statusDiv.classList.add('hidden');
        }, 3000);
    }

    // --- Modal Logic ---
    function openModal(modalId) {
        // Reset form for 'add'
        if (modalId === 'add-product-modal') {
            document.getElementById('product-form').reset();
            document.getElementById('product-id').value = '';
            document.getElementById('modal-title').textContent = 'Add New Product';
        }
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function openEditModal(product) {
        openModal('add-product-modal');
        document.getElementById('modal-title').textContent = 'Edit Product';
        document.getElementById('product-id').value = product.id;
        document.getElementById('product-name').value = product.name;
        document.getElementById('product-category').value = product.categoryId;
        document.getElementById('product-description').value = product.description;
        document.getElementById('product-price').value = product.price;
        document.getElementById('product-icon').value = product.icon;
        document.getElementById('product-color').value = product.color;
        document.getElementById('product-stock_out').checked = product.stock_out;
    }

    // --- API Calls ---
    async function handleFormSubmit(event, action) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', action);

        // Handle checkbox value
        if (action.includes('product')) {
            if (!formData.has('stock_out')) {
                formData.append('stock_out', 'off'); // 'off' will be converted to false in PHP
            }
        }

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                 const errorText = await response.text();
                 throw new Error(`Server responded with ${response.status}: ${errorText}`);
            }

            const result = await response.json();

            if (result.success) {
                showStatus(result.message, true);
                // Optionally, reload the page to see changes
                setTimeout(() => location.reload(), 500);
            } else {
                showStatus(result.message, false);
            }
        } catch (error) {
            console.error('Error:', error);
            showStatus('An error occurred. Check the console for details.', false);
        }
    }

    document.getElementById('add-category-form').addEventListener('submit', (e) => handleFormSubmit(e, 'add_category'));
    document.getElementById('product-form').addEventListener('submit', (e) => {
        const action = document.getElementById('product-id').value ? 'edit_product' : 'add_product';
        handleFormSubmit(e, action);
    });

    async function deleteItem(type, id) {
        if (!confirm(`Are you sure you want to delete this ${type}?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', `delete_${type}`);
        formData.append('id', id);

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                 const errorText = await response.text();
                 throw new Error(`Server responded with ${response.status}: ${errorText}`);
            }

            const result = await response.json();

            if (result.success) {
                showStatus(result.message, true);
                document.getElementById(`${type}-${id}`).remove();
            } else {
                showStatus(result.message, false);
            }
        } catch (error) {
            console.error('Error:', error);
            showStatus('An error occurred. Check the console for details.', false);
        }
    }
    </script>
</body>
</html>
