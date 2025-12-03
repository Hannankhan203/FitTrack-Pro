<?php
// api/delete-meal.php - SIMPLIFIED AND GUARANTEED WORKING
header('Content-Type: application/json');
require '../includes/functions.php';
require_login();

// Get the meal ID
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Get ID from POST data or GET parameter
$meal_id = $_GET['id'] ?? 0;
if (!$meal_id && isset($data['id'])) {
    $meal_id = $data['id'];
}

// Log for debugging
error_log("DELETE MEAL REQUEST - ID: " . $meal_id . ", Method: " . $_SERVER['REQUEST_METHOD']);

// Validate meal ID
if (!$meal_id || !is_numeric($meal_id)) {
    error_log("DELETE ERROR: Invalid meal ID: " . $meal_id);
    echo json_encode(['status' => 'error', 'message' => 'Invalid meal ID']);
    exit();
}

$user_id = get_user_id();

require '../includes/db.php';

try {
    // First, check if the meal exists and belongs to the user
    $check_stmt = $pdo->prepare("SELECT id FROM meals WHERE id = ? AND user_id = ?");
    $check_stmt->execute([$meal_id, $user_id]);
    $meal_exists = $check_stmt->fetch();
    
    if (!$meal_exists) {
        error_log("DELETE ERROR: Meal not found or not authorized. ID: $meal_id, User: $user_id");
        echo json_encode(['status' => 'error', 'message' => 'Meal not found or not authorized']);
        exit();
    }
    
    // Delete the meal
    $delete_stmt = $pdo->prepare("DELETE FROM meals WHERE id = ? AND user_id = ?");
    $success = $delete_stmt->execute([$meal_id, $user_id]);
    
    if ($success && $delete_stmt->rowCount() > 0) {
        error_log("DELETE SUCCESS: Meal ID $meal_id deleted for user $user_id");
        echo json_encode([
            'status' => 'success', 
            'message' => 'Meal deleted successfully',
            'deleted_id' => $meal_id
        ]);
    } else {
        error_log("DELETE ERROR: Failed to delete meal. ID: $meal_id, User: $user_id");
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to delete meal'
        ]);
    }
} catch (PDOException $e) {
    error_log("DELETE DATABASE ERROR: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>