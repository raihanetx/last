<?php
session_start();
header('Content-Type: application/json');

// --- Configuration & Security ---
$data_file = __DIR__ . '/../data.json';

// Security Check: Only allow logged-in users
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// --- Helper Functions ---
function read_data($file_path) {
    if (!file_exists($file_path)) {
        // Create the file with a default structure if it doesn't exist
        $initial_data = json_encode(['categories' => [], 'products' => []], JSON_PRETTY_PRINT);
        file_put_contents($file_path, $initial_data);
        return ['categories' => [], 'products' => []];
    }
    $json = file_get_contents($file_path);
    return json_decode($json, true);
}

function write_data($file_path, $data) {
    // Use file locking to prevent race conditions
    $fp = fopen($file_path, 'w');
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

function send_response($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// --- Main Request Handler ---
$action = $_POST['action'] ?? null;
if (!$action) {
    send_response(false, 'No action specified.');
}

$data = read_data($data_file);

switch ($action) {
    case 'add_category':
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        if (empty($name) || empty($icon)) {
            send_response(false, 'Category name and icon cannot be empty.');
        }
        $new_category = [
            'id' => 'cat_' . uniqid(),
            'name' => $name,
            'icon' => $icon,
        ];
        $data['categories'][] = $new_category;
        write_data($data_file, $data);
        send_response(true, 'Category added successfully.', $new_category);
        break;

    case 'delete_category':
        $id = $_POST['id'] ?? null;
        if (empty($id)) {
            send_response(false, 'No category ID specified.');
        }
        // Check if any product is using this category
        foreach ($data['products'] as $product) {
            if ($product['categoryId'] === $id) {
                send_response(false, 'Cannot delete category. It is currently in use by one or more products.');
            }
        }
        $data['categories'] = array_filter($data['categories'], function ($category) use ($id) {
            return $category['id'] !== $id;
        });
        // Re-index array if needed
        $data['categories'] = array_values($data['categories']);
        write_data($data_file, $data);
        send_response(true, 'Category deleted successfully.');
        break;

    case 'add_product':
    case 'edit_product':
        // Validation
        $required_fields = ['name', 'categoryId', 'price'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                send_response(false, "Field '{$field}' is required.");
            }
        }

        $product_data = [
            'name'        => trim($_POST['name']),
            'categoryId'  => $_POST['categoryId'],
            'description' => trim($_POST['description'] ?? ''),
            'price'       => floatval($_POST['price']),
            'icon'        => trim($_POST['icon'] ?? ''),
            'color'       => trim($_POST['color'] ?? ''),
            'stock_out'   => isset($_POST['stock_out']) && $_POST['stock_out'] === 'on',
        ];

        if ($action === 'add_product') {
            $product_data['id'] = 'prod_' . uniqid();
            $data['products'][] = $product_data;
            $message = 'Product added successfully.';
        } else { // edit_product
            $id = $_POST['id'] ?? null;
            if (empty($id)) {
                send_response(false, 'No product ID specified for editing.');
            }
            $product_data['id'] = $id;
            $product_found = false;
            foreach ($data['products'] as &$product) {
                if ($product['id'] === $id) {
                    $product = $product_data;
                    $product_found = true;
                    break;
                }
            }
            if (!$product_found) {
                send_response(false, "Product with ID {$id} not found.");
            }
            $message = 'Product updated successfully.';
        }

        write_data($data_file, $data);
        send_response(true, $message, $product_data);
        break;

    case 'delete_product':
        $id = $_POST['id'] ?? null;
        if (empty($id)) {
            send_response(false, 'No product ID specified.');
        }
        $data['products'] = array_filter($data['products'], function ($product) use ($id) {
            return $product['id'] !== $id;
        });
        // Re-index array
        $data['products'] = array_values($data['products']);
        write_data($data_file, $data);
        send_response(true, 'Product deleted successfully.');
        break;

    default:
        send_response(false, 'Invalid action specified.');
        break;
}
?>
