<!-- includes/header.php -->
<?php if(isset($_SESSION['user_name']) && $_SESSION['user_name'] == 'Admin User'): ?>
<a class="nav-link" href="/fitness-tracker/admin/dashboard.php">Admin</a>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/fitness-tracker/dashboard.php">FitTrack Pro</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="/fitness-tracker/dashboard.php">Dashboard</a>
            <a class="nav-link" href="/fitness-tracker/workouts/log.php">Workouts</a>
            <a class="nav-link" href="/fitness-tracker/meals/planner.php">Meals</a>
            <a class="nav-link" href="/fitness-tracker/progress/charts.php">Progress</a>
            <a class="nav-link" href="/fitness-tracker/progress/photos.php">Photos</a>
            <a class="nav-link" href="/fitness-tracker/logout.php">Logout</a>
        </div>
    </div>
</nav>