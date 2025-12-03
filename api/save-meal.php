<?php
// api/save-meal.php - SIMPLIFIED WORKING VERSION
header('Content-Type: application/json');

// Enable CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

require '../includes/functions.php';
require_login();

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// If no JSON data, try form data
if (!$data) {
    $data = $_POST;
}

// Debug logging
error_log("Meal save request received: " . print_r($data, true));

// Validate required fields
if (empty($data['food'])) {
    echo json_encode(['status' => 'error', 'message' => 'Food name is required']);
    exit();
}

$user_id = get_user_id();
$date = date('Y-m-d');

// Extract data with defaults
$food = trim($data['food']);
$calories = isset($data['calories']) ? intval($data['calories']) : 100;
$protein = isset($data['protein']) ? floatval($data['protein']) : 0;
$carbs = isset($data['carbs']) ? floatval($data['carbs']) : 0;
$fat = isset($data['fat']) ? floatval($data['fat']) : 0;
$meal_time = isset($data['meal']) ? $data['meal'] : 'lunch';

require '../includes/db.php';

try {
    // Try to insert into meals table
    $stmt = $pdo->prepare("INSERT INTO meals (user_id, food_name, calories, protein, carbs, fat, meal_time, date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $success = $stmt->execute([$user_id, $food, $calories, $protein, $carbs, $fat, $meal_time, $date]);
    
    if ($success) {
        $meal_id = $pdo->lastInsertId();
        error_log("Meal saved successfully: ID $meal_id, Food: $food, Calories: $calories");
        
        // Return SUCCESS response
        echo json_encode([
            'success' => true,
            'status' => 'success',
            'meal_id' => $meal_id,
            'message' => 'Meal saved successfully'
        ]);
    } else {
        error_log("Failed to save meal: execute() returned false");
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'Failed to save meal to database'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Database exception: " . $e->getMessage());
    
    // Try with minimal columns if the above fails
    try {
        $stmt = $pdo->prepare("INSERT INTO meals (user_id, food_name, calories, meal_time, date) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $food, $calories, $meal_time, $date]);
        $meal_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'status' => 'success',
            'meal_id' => $meal_id,
            'message' => 'Meal saved (basic info only)'
        ]);
    } catch (Exception $e2) {
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'Database error: ' . $e2->getMessage()
        ]);
    }
}
?>