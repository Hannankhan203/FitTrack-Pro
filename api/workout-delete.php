<?php
// api/workout-delete.php
session_start();
header('Content-Type: application/json');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../includes/functions.php';
require '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the POST data
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!isset($data['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Exercise ID required']);
    exit();
}

$exercise_id = $data['id'];

// Verify the exercise belongs to the current user
$stmt = $pdo->prepare("SELECT user_id FROM workouts WHERE id = ?");
$stmt->execute([$exercise_id]);
$exercise = $stmt->fetch();

if (!$exercise || $exercise['user_id'] != $user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Exercise not found or access denied']);
    exit();
}

// Delete the exercise
$stmt = $pdo->prepare("DELETE FROM workouts WHERE id = ? AND user_id = ?");
$deleted = $stmt->execute([$exercise_id, $user_id]);

if ($deleted) {
    echo json_encode(['status' => 'success', 'message' => 'Exercise deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete exercise']);
}
?>