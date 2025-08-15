<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow all domains for simplicity, you might want to restrict this in production

$json_file_path = __DIR__ . '/../data.json';

if (file_exists($json_file_path)) {
    $json_data = file_get_contents($json_file_path);
    echo $json_data;
} else {
    // If the file doesn't exist, return an empty structure
    http_response_code(404);
    echo json_encode(["categories" => [], "products" => []]);
}
?>
