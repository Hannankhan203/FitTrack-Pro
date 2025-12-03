<!-- admin/dashboard.php -->
<?php 
require '../includes/functions.php'; require_login();
require '../includes/db.php';
$user = $pdo->query("SELECT role FROM users WHERE id = ".get_user_id())->fetch();
if ($user['role'] !== 'admin') die("Access Denied");
$users = $pdo->query("SELECT id, name, email, created_at FROM users")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Admin Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="manifest" href="manifest.json">
<link rel="icon" href="assets/img/favicon-16x16.png">
<meta name="theme-color" content="#0d6efd">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container mt-5">
    <h1>Admin Panel</h1>
    <table class="table table-striped">
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Joined</th></tr>
        <?php foreach($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= $u['name'] ?></td>
            <td><?= $u['email'] ?></td>
            <td><?= $u['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/fitness-tracker/sw.js');
}
</script>
</body>
</html>