<?php
// debug-workout.php
session_start();
require '../includes/functions.php';
require '../includes/db.php';

echo "<h1>Workout Debug Page</h1>";

// Check login
echo "<h3>Session Status:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Logged in: " . (is_logged_in() ? 'YES' : 'NO') . "<br>";
echo "User ID: " . (get_user_id() ?? 'NOT FOUND') . "<br><br>";

// Check database connection
echo "<h3>Database Connection:</h3>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "Connected: YES<br>";
    
    // Check workouts table
    echo "<h3>Workouts Table Structure:</h3>";
    $result = $pdo->query("DESCRIBE workouts");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check existing workouts for this user
    echo "<h3>Existing Workouts for User " . get_user_id() . ":</h3>";
    $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? ORDER BY date DESC LIMIT 10");
    $stmt->execute([get_user_id()]);
    $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($workouts)) {
        echo "No workouts found for this user.<br>";
    } else {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (array_keys($workouts[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        foreach ($workouts as $workout) {
            echo "<tr>";
            foreach ($workout as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "<br>";
}

// Check database schema
echo "<h3>Database Schema:</h3>";
echo "Current date format: " . date('Y-m-d') . "<br>";

// Create table if it doesn't exist
echo "<h3>Create Table SQL (if needed):</h3>";
echo "<pre>";
echo "CREATE TABLE IF NOT EXISTS workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exercise VARCHAR(100) NOT NULL,
    sets INT DEFAULT NULL,
    reps INT DEFAULT NULL,
    weight DECIMAL(5,2) DEFAULT NULL,
    duration DECIMAL(5,2) DEFAULT NULL,
    distance DECIMAL(5,2) DEFAULT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX user_date_idx (user_id, date)
)";
echo "</pre>";

echo "<br><a href='log.php'>Back to Workout Log</a>";
?>