<?php
$host = 'localhost';
$db   = 'fitness_tracker';
$user = 'root';
$pass = ''; // default XAMPP has no password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}