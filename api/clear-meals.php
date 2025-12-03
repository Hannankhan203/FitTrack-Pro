<?php
// api/clear-meals.php
header('Content-Type: application/json');
require '../includes/functions.php';
require_login();

$user_id = get_user_id();
$date = date('Y-m-d');

// If specific date provided in POST
$data = json_decode(file_get_contents('php://input'), true);
if (isset($data['date']) && !empty($data['date'])) {
    $date = $data['date'];
}

require '../includes/db.php';

try {
    $stmt = $pdo->prepare("DELETE FROM meals WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $date]);
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'All meals cleared for ' . $date,
        'deleted_count' => $stmt->rowCount()
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>