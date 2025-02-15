<?php
// pages/get_bill.php
require_once '../config/database.php';
require_once '../classes/Sale.php';
require_once  $_SERVER['DOCUMENT_ROOT'] . '/msms/auth/auth_functions.php';


session_start();

// Include the auth functions file
require_once '../auth/auth_functions.php'; 

// Protect the page
requireLogin();  // This function will redirect to login if not logged in



header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$sale = new Sale($db);

$bill_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($bill_id) {
    $bill_details = $sale->getBillDetails($bill_id);
    if ($bill_details) {
        echo json_encode($bill_details);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Bill not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Bill ID not provided']);
}
