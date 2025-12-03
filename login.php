<!-- login.php -->
<?php
session_start();
require 'includes/db.php';

if ($_POST) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials!";
    }
}
?>

<!-- Show error if any -->
<?php if(isset($error)): ?>
    <script>alert("<?= $error ?>"); window.location='index.php';</script>
<?php endif; ?>