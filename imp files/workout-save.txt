<?php
// api/workout-save.php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log that we reached the API
error_log("=== WORKOUT SAVE API CALLED ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

require '../includes/functions.php';
require '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in - session user_id not set");
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
error_log("User ID from session: $user_id");

// Get the POST data
$raw_input = file_get_contents('php://input');
error_log("Raw input received (length: " . strlen($raw_input) . ")");

$data = json_decode($raw_input, true);

if (!$data) {
    error_log("No data or invalid JSON received");
    echo json_encode(['status' => 'error', 'message' => 'No valid data received']);
    exit();
}

error_log("Data parsed successfully: " . json_encode($data));

$date = date('Y-m-d H:i:s');
$successCount = 0;
$errors = [];

try {
    // Check database connection
    error_log("Database connection established");
    
    // First, disable foreign key checks to avoid constraint issues
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Prepare a single statement that can handle all cases
    $stmt = $pdo->prepare("
        INSERT INTO workouts 
        (user_id, exercise, sets, reps, weight, duration, distance, date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($data as $index => $workout) {
        $exercise = $workout['exercise'] ?? '';
        $sets = isset($workout['sets']) ? intval($workout['sets']) : null;
        $reps = isset($workout['reps']) ? intval($workout['reps']) : null;
        $weight = isset($workout['weight']) ? floatval($workout['weight']) : null;
        $duration = isset($workout['duration']) ? floatval($workout['duration']) : 0;
        $distance = isset($workout['distance']) ? floatval($workout['distance']) : null;
        
        error_log("Processing exercise $index: $exercise");
        error_log("  Sets: $sets, Reps: $reps, Weight: $weight, Duration: $duration, Distance: $distance");
        
        // If it's a strength exercise with sets/reps, calculate duration
        if ($sets && $reps && !isset($workout['duration'])) {
            $duration = $sets * 0.5; // 30 seconds per set
            error_log("  Calculated duration for strength: $duration minutes");
        }
        
        // Execute the insert
        try {
            $result = $stmt->execute([$user_id, $exercise, $sets, $reps, $weight, $duration, $distance, $date]);
            
            if ($result) {
                $successCount++;
                $lastId = $pdo->lastInsertId();
                error_log("  ✓ Successfully saved: $exercise (ID: $lastId)");
            } else {
                $errorInfo = $stmt->errorInfo();
                $errors[] = "Failed to save: $exercise - " . ($errorInfo[2] ?? 'Unknown error');
                error_log("  ✗ Failed to save: $exercise - " . ($errorInfo[2] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $errors[] = "Exception for $exercise: " . $e->getMessage();
            error_log("  ✗ Exception for $exercise: " . $e->getMessage());
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    error_log("=== SAVE COMPLETE ===");
    error_log("Total saved: $successCount");
    error_log("Errors: " . count($errors));
    
    if ($successCount > 0) {
        echo json_encode([
            'status' => 'success', 
            'message' => "Saved $successCount exercises successfully",
            'saved_count' => $successCount
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'No exercises were saved. ' . implode(', ', $errors)
        ]);
    }
    
} catch (Exception $e) {
    error_log("Database exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>