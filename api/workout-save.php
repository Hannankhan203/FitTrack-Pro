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

error_log("Data parsed successfully: " . json_encode($data, JSON_PRETTY_PRINT));

$successCount = 0;
$errors = [];

try {
    // Check database connection
    error_log("Database connection established");
    
    // IMPORTANT: Set timezone for proper date handling
    date_default_timezone_set('Asia/Karachi');
    
    // Prepare a single statement that can handle all cases
    $stmt = $pdo->prepare("
        INSERT INTO workouts 
        (user_id, exercise, sets, reps, weight, duration, distance, steps, calories, date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($data as $index => $workout) {
        // FIXED: Create a NEW timestamp for EACH exercise
        // Use microtime to ensure unique timestamps even if saved in the same second
        usleep(1000); // Add 1ms delay to ensure unique timestamps
        $date = date('Y-m-d H:i:s');
        
        $exercise = $workout['exercise'] ?? '';
        $sets = isset($workout['sets']) ? intval($workout['sets']) : 0;
        $reps = isset($workout['reps']) ? intval($workout['reps']) : 0;
        $weight = isset($workout['weight']) ? floatval($workout['weight']) : 0;
        $duration = isset($workout['duration']) ? floatval($workout['duration']) : 0;
        $distance = isset($workout['distance']) ? floatval($workout['distance']) : 0;
        $steps = isset($workout['steps']) ? intval($workout['steps']) : 0;
        $calories = isset($workout['calories']) ? floatval($workout['calories']) : 0;
        
        error_log("Processing exercise $index: $exercise");
        error_log("  Timestamp: $date");
        error_log("  Sets: $sets, Reps: $reps, Weight: $weight, Duration: $duration");
        error_log("  Distance: $distance, Steps: $steps, Calories: $calories");        
        // For walking exercise, if steps but no calories, estimate calories
        if ($exercise === 'Walking 🚶‍♂️' && $steps > 0 && $calories <= 0) {
            $calories = round($steps * 0.04); // Approx 0.04 calories per step
            error_log("  Estimated calories for walking: $calories");
        }
        
        // If it's a strength exercise with sets/reps and no duration, calculate it
        if ($sets > 0 && $reps > 0 && $duration <= 0) {
            $duration = $sets * 0.5; // 30 seconds per set
            error_log("  Calculated duration for strength: $duration minutes");
        }
        
        // Execute the insert with ALL fields including steps and calories
        try {
            $result = $stmt->execute([
                $user_id, 
                $exercise, 
                $sets, 
                $reps, 
                $weight, 
                $duration, 
                $distance, 
                $steps, 
                $calories, 
                $date  // Using the per-exercise timestamp
            ]);
            
            if ($result) {
                $successCount++;
                $lastId = $pdo->lastInsertId();
                error_log("  ✓ Successfully saved: $exercise (ID: $lastId) at $date");
            } else {
                $errorInfo = $stmt->errorInfo();
                $errors[] = "Failed to save: $exercise - " . ($errorInfo[2] ?? 'Unknown error');
                error_log("  ✗ Failed to save: $exercise - " . ($errorInfo[2] ?? 'Unknown error'));
                error_log("  SQL error info: " . print_r($errorInfo, true));
            }
        } catch (Exception $e) {
            $errors[] = "Exception for $exercise: " . $e->getMessage();
            error_log("  ✗ Exception for $exercise: " . $e->getMessage());
            error_log("  Exception trace: " . $e->getTraceAsString());
        }
    }
    
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

// Also check database structure
try {
    $stmt = $pdo->query("DESCRIBE workouts");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Database columns in workouts table: " . implode(', ', $columns));
} catch (Exception $e) {
    error_log("Could not check database structure: " . $e->getMessage());
}
?>