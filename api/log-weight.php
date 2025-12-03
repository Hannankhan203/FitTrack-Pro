<?php
require '../includes/functions.php';
require_login();

$user_id = get_user_id();
require '../includes/db.php';

header('Content-Type: application/json');

// Check if weight is provided
if (!isset($_POST['weight']) || empty($_POST['weight'])) {
    echo json_encode(['status' => 'error', 'message' => 'Weight is required']);
    exit;
}

$weight = floatval($_POST['weight']);
$date = date('Y-m-d');

try {
    // Check if weight already exists for today
    $checkStmt = $pdo->prepare("SELECT id FROM progress WHERE user_id = ? AND date = ?");
    $checkStmt->execute([$user_id, $date]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        // Update existing record WITHOUT updated_at
        $stmt = $pdo->prepare("UPDATE progress SET weight = ? WHERE id = ?");
        $stmt->execute([$weight, $existing['id']]);
    } else {
        // Insert new record WITHOUT updated_at
        $stmt = $pdo->prepare("INSERT INTO progress (user_id, weight, date) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $weight, $date]);
    }
    
    // Also update the weight in users table
    $updateUser = $pdo->prepare("UPDATE users SET weight = ? WHERE id = ?");
    $updateUser->execute([$weight, $user_id]);
    
    echo json_encode(['status' => 'success', 'message' => 'Weight logged successfully']);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>