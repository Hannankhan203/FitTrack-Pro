<?php 
// Set timezone to Pakistan/Islamabad
date_default_timezone_set('Asia/Karachi');

require '../includes/functions.php';
require_login(); 
?>
<?php
$user_id = get_user_id();
require '../includes/db.php';

// First, let's make sure the database table has the correct structure
try {
    // Check if the progress table has created_at column
    $checkTable = $pdo->prepare("SHOW COLUMNS FROM progress LIKE 'created_at'");
    $checkTable->execute();
    $hasCreatedAt = $checkTable->fetch();
    
    if (!$hasCreatedAt) {
        // Add created_at column if it doesn't exist
        $pdo->exec("ALTER TABLE progress ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }
} catch (PDOException $e) {
    error_log("Error checking/updating table structure: " . $e->getMessage());
}

// Handle weight logging form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_weight') {
    if (isset($_POST['weight']) && is_numeric($_POST['weight']) && $_POST['weight'] > 0) {
        $weight = floatval($_POST['weight']);
        $current_time = date('Y-m-d H:i:s');
        
        try {
            // Always insert new weight record
            $stmt = $pdo->prepare("INSERT INTO progress (user_id, weight, date, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $weight, date('Y-m-d'), $current_time]);

            // Update user's current weight in users table
            $updateUserStmt = $pdo->prepare("UPDATE users SET weight = ? WHERE id = ?");
            $updateUserStmt->execute([$weight, $user_id]);

            // Redirect to avoid form resubmission
            header("Location: charts.php?success=weight_logged");
            exit();
        } catch (PDOException $e) {
            error_log("Error logging weight: " . $e->getMessage());
            $error = "Failed to save weight. Please try again.";
        }
    } else {
        $error = "Please enter a valid weight.";
    }
}

// Initialize arrays
$dates = [];
$weights = [];
$progress = [];

try {
    // Get weight history - all entries in chronological order
    $stmt = $pdo->prepare("SELECT date, weight, created_at FROM progress WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->execute([$user_id]);
    $progress = $stmt->fetchAll();
    
    foreach ($progress as $p) {
        if (!empty($p['created_at'])) {
            // Format date according to Pakistan timezone
            $dateTime = new DateTime($p['created_at'], new DateTimeZone('Asia/Karachi'));
            $dates[] = $dateTime->format('M j g:i A');
        } else {
            $dates[] = date('M j', strtotime($p['date']));
        }
        $weights[] = $p['weight'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Error fetching progress: " . $e->getMessage());
    $progress = [];
}

// Get user's current weight (most recent)
$current_weight = 0;
$last_weight_date = '';
try {
    $stmt = $pdo->prepare("SELECT weight, created_at FROM progress WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $latestWeight = $stmt->fetch();
    
    if ($latestWeight) {
        $current_weight = $latestWeight['weight'] ?? 0;
        $last_weight_date = $latestWeight['created_at'] ?? '';
    } else {
        // Fallback to user table
        $stmt = $pdo->prepare("SELECT weight FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $userData = $stmt->fetch();
        $current_weight = $userData['weight'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Error fetching user weight: " . $e->getMessage());
}

// Today's Macros
$today = date('Y-m-d');
try {
    $stmt = $pdo->prepare("SELECT SUM(protein) as p, SUM(carbs) as c, SUM(fat) as f, SUM(calories) as cal FROM meals WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $today]);
    $macros = $stmt->fetch();
    $calories = $macros['cal'] ?? 0;
    $protein = $macros['p'] ?? 0;
    $carbs = $macros['c'] ?? 0;
    $fat = $macros['f'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching macros: " . $e->getMessage());
    $calories = $protein = $carbs = $fat = 0;
}

// Weekly workout data
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM workouts WHERE user_id = ? AND date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $weekStart, $weekEnd]);
    $weeklyWorkouts = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching workouts: " . $e->getMessage());
    $weeklyWorkouts = ['count' => 0];
}

// Goal progress
try {
    $stmt = $pdo->prepare("SELECT goal_weight, goal_type, weight FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $goal_info = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching goal info: " . $e->getMessage());
    $goal_info = false;
}

$goal_progress = 0;
if ($goal_info && $goal_info['goal_weight'] && $goal_info['weight']) {
    if ($goal_info['goal_type'] == 'lose') {
        if ($goal_info['weight'] > $goal_info['goal_weight']) {
            $total_to_lose = $goal_info['weight'] - $goal_info['goal_weight'];
            $currentWeight = $current_weight ?: $goal_info['weight'];
            $current_lost = $goal_info['weight'] - $currentWeight;
            $goal_progress = min(100, max(0, ($current_lost / $total_to_lose) * 100));
        }
    } elseif ($goal_info['goal_type'] == 'gain') {
        if ($goal_info['weight'] < $goal_info['goal_weight']) {
            $total_to_gain = $goal_info['goal_weight'] - $goal_info['weight'];
            $currentWeight = $current_weight ?: $goal_info['weight'];
            $current_gain = $currentWeight - $goal_info['weight'];
            $goal_progress = min(100, max(0, ($current_gain / $total_to_gain) * 100));
        }
    } else {
        $goal_progress = 100;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Progress Dashboard - FitTrack Pro</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #3a86ff;
            --primary-dark: #2667cc;
            --secondary-color: #ff006e;
            --accent-color: #8338ec;
            --success-color: #38b000;
            --warning-color: #ffbe0b;
            --protein-color: #3a86ff;
            --carbs-color: #ff006e;
            --fat-color: #ffbe0b;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gradient-primary: linear-gradient(135deg, #3a86ff, #8338ec);
            --gradient-secondary: linear-gradient(135deg, #ff006e, #fb5607);
            --gradient-success: linear-gradient(135deg, #38b000, #70e000);
            --gradient-warning: linear-gradient(135deg, #ffbe0b, #ff9100);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --border-radius: 16px;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f9ff;
            color: var(--dark-color);
            min-height: 100vh;
        }

        .logo {
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            font-size: 1.8rem;
            background: var(--gradient-secondary);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .navbar {
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 1rem 0;
        }

        .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--gradient-primary);
            color: white;
        }

        .nav-link i {
            margin-right: 8px;
        }

        .page-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .page-header h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            height: 100%;
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
            height: 400px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .chart-controls {
            display: flex;
            gap: 0.5rem;
        }

        .chart-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: #495057;
            transition: all 0.3s;
        }

        .chart-btn:hover,
        .chart-btn.active {
            background: var(--gradient-primary);
            color: white;
            border-color: var(--primary-color);
        }

        .macro-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }

        .macro-donut {
            width: 200px;
            height: 200px;
            margin: 0 auto;
        }

        .macro-breakdown {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .macro-item {
            text-align: center;
            padding: 1rem;
            border-radius: 12px;
            transition: transform 0.3s;
        }

        .macro-item:hover {
            transform: translateY(-5px);
        }

        .macro-item.protein {
            background: linear-gradient(135deg, rgba(58, 134, 255, 0.1), rgba(58, 134, 255, 0.05));
        }

        .macro-item.carbs {
            background: linear-gradient(135deg, rgba(255, 0, 110, 0.1), rgba(255, 0, 110, 0.05));
        }

        .macro-item.fat {
            background: linear-gradient(135deg, rgba(255, 190, 11, 0.1), rgba(255, 190, 11, 0.05));
        }

        .macro-amount {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .macro-name {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .macro-grams {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .goal-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }

        .goal-progress {
            margin: 1.5rem 0;
        }

        .progress-bar {
            height: 12px;
            border-radius: 10px;
            background-color: #e9ecef;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient-success);
            border-radius: 10px;
            transition: width 1s ease-in-out;
        }

        .goal-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .goal-stat {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .goal-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .goal-label {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .weight-form-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .input-group {
            position: relative;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 134, 255, 0.25);
        }

        .input-unit {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-weight: 500;
            background: white;
            padding: 0 0.5rem;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 15px rgba(58, 134, 255, 0.3);
        }

        .last-recorded {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
            text-align: center;
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .insight-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
        }

        .insight-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .insight-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .insight-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
            padding: 0.75rem;
            display: none;
            z-index: 1000;
        }

        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #6c757d;
            font-size: 0.8rem;
        }

        .mobile-nav-item.active {
            color: var(--primary-color);
        }

        .mobile-nav-item i {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        @media (max-width: 768px) {
            .mobile-nav {
                display: flex;
                justify-content: space-around;
            }

            .navbar-nav {
                display: none;
            }

            .page-header {
                padding: 1.5rem;
            }

            .chart-container {
                height: 300px;
            }

            .macro-breakdown,
            .goal-stats {
                grid-template-columns: 1fr;
            }

            .insights-grid {
                grid-template-columns: 1fr;
            }
        }

        .trend-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .trend-up {
            background: rgba(56, 176, 0, 0.1);
            color: var(--success-color);
        }

        .trend-down {
            background: rgba(255, 0, 110, 0.1);
            color: var(--secondary-color);
        }

        .trend-neutral {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        /* Recent weights table */
        .recent-weights {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            margin-top: 2rem;
        }

        .recent-weights table {
            width: 100%;
        }

        .recent-weights th {
            font-weight: 600;
            color: #6c757d;
            padding: 0.75rem;
            border-bottom: 2px solid #dee2e6;
            text-align: left;
        }

        .recent-weights td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }

        .recent-weights tr:last-child td {
            border-bottom: none;
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <!-- Desktop Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand logo" href="../dashboard.php">FitTrack Pro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../workouts/log.php">
                            <i class="fas fa-dumbbell"></i> Workouts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../meals/planner.php">
                            <i class="fas fa-utensils"></i> Meals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="charts.php">
                            <i class="fas fa-chart-line"></i> Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="photos.php">
                            <i class="fas fa-camera"></i> Photos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-chart-line me-2"></i>Progress Dashboard</h1>
            <p>Track your fitness journey with detailed analytics and insights</p>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'weight_logged'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Weight logged successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #ff9a9e, #fad0c4); color: #ff6b6b;">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stats-value"><?= number_format($calories) ?></div>
                    <div class="stats-label">Calories Today</div>
                    <div class="small text-muted mt-2">Total kcal consumed</div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #a1c4fd, #c2e9fb); color: #4d96ff;">
                        <i class="fas fa-weight"></i>
                    </div>
                    <div class="stats-value"><?= $current_weight ? number_format($current_weight, 1) : '--' ?></div>
                    <div class="stats-label">Current Weight</div>
                    <div class="small text-muted mt-2"><?= count($progress) ?> records</div>
                    <?php if (count($weights) > 1):
                        $firstWeight = $weights[0];
                        $lastWeight = end($weights);
                        $trend = $lastWeight - $firstWeight;
                        if ($trend > 0): ?>
                            <span class="trend-indicator trend-up">
                                <i class="fas fa-arrow-up me-1"></i>+<?= number_format($trend, 1) ?>kg
                            </span>
                        <?php elseif ($trend < 0): ?>
                            <span class="trend-indicator trend-down">
                                <i class="fas fa-arrow-down me-1"></i><?= number_format($trend, 1) ?>kg
                            </span>
                        <?php else: ?>
                            <span class="trend-indicator trend-neutral">
                                <i class="fas fa-minus me-1"></i>No change
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #84fab0, #8fd3f4); color: #38b000;">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <div class="stats-value"><?= $weeklyWorkouts['count'] ?? 0 ?></div>
                    <div class="stats-label">Weekly Workouts</div>
                    <div class="small text-muted mt-2">This week</div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #fbc2eb, #a6c1ee); color: #8338ec;">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="stats-value"><?= round($goal_progress) ?>%</div>
                    <div class="stats-label">Goal Progress</div>
                    <div class="small text-muted mt-2"><?= isset($goal_info['goal_type']) ? ucfirst($goal_info['goal_type']) . ' weight' : 'No goal set' ?></div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Charts -->
            <div class="col-lg-8">
                <!-- Weight Progress Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">
                            <i class="fas fa-weight-scale me-2"></i>Weight Progress Over Time
                        </div>
                        <div class="chart-controls">
                            <button class="chart-btn active" onclick="changeChartView('7d')">7D</button>
                            <button class="chart-btn" onclick="changeChartView('30d')">30D</button>
                            <button class="chart-btn" onclick="changeChartView('90d')">90D</button>
                            <button class="chart-btn" onclick="changeChartView('all')">All</button>
                        </div>
                    </div>
                    <?php if (!empty($dates) && !empty($weights)): ?>
                        <canvas id="weightChart"></canvas>
                    <?php else: ?>
                        <div class="empty-state" style="height: 300px; display: flex; flex-direction: column; justify-content: center;">
                            <i class="fas fa-weight-scale"></i>
                            <h4>No Weight Data</h4>
                            <p>Start logging your weight to see progress charts</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Weight Entries -->
                <?php if (!empty($progress)): ?>
                    <div class="recent-weights">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-history me-2"></i>Recent Weight Entries
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Weight</th>
                                        <th>Change</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $previousWeight = null;
                                    // Get latest 10 entries for display
                                    $recentEntries = array_slice(array_reverse($progress), 0, 10);
                                    foreach ($recentEntries as $entry): 
                                        $weight = $entry['weight'];
                                        $dateTime = !empty($entry['created_at']) ? $entry['created_at'] : $entry['date'];
                                        // Format date according to Pakistan timezone
                                        $dateObj = new DateTime($dateTime, new DateTimeZone('Asia/Karachi'));
                                        $formattedDate = $dateObj->format('M j, Y');
                                        $formattedTime = $dateObj->format('g:i A');
                                    ?>
                                        <tr>
                                            <td>
                                                <div><?= $formattedDate ?></div>
                                                <div class="small text-muted"><?= $formattedTime ?></div>
                                            </td>
                                            <td><strong><?= number_format($weight, 1) ?> kg</strong></td>
                                            <td>
                                                <?php if ($previousWeight !== null): 
                                                    $change = $weight - $previousWeight;
                                                    if ($change > 0): ?>
                                                        <span class="text-danger">
                                                            <i class="fas fa-arrow-up me-1"></i>+<?= number_format($change, 1) ?>kg
                                                        </span>
                                                    <?php elseif ($change < 0): ?>
                                                        <span class="text-success">
                                                            <i class="fas fa-arrow-down me-1"></i><?= number_format($change, 1) ?>kg
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">No change</span>
                                                    <?php endif;
                                                else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php $previousWeight = $weight; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Nutrition & Log Weight -->
            <div class="col-lg-4">
                <!-- Macronutrients -->
                <div class="macro-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <i class="fas fa-apple-alt me-2"></i>Today's Nutrition
                        </div>
                    </div>

                    <?php if (($protein + $carbs + $fat) > 0): ?>
                        <div class="macro-donut">
                            <canvas id="macroChart"></canvas>
                        </div>

                        <div class="macro-breakdown">
                            <div class="macro-item protein">
                                <div class="macro-amount"><?= round($protein) ?></div>
                                <div class="macro-name" style="color: var(--protein-color);">Protein</div>
                                <div class="macro-grams"><?= round($protein) ?>g</div>
                            </div>
                            <div class="macro-item carbs">
                                <div class="macro-amount"><?= round($carbs) ?></div>
                                <div class="macro-name" style="color: var(--carbs-color);">Carbs</div>
                                <div class="macro-grams"><?= round($carbs) ?>g</div>
                            </div>
                            <div class="macro-item fat">
                                <div class="macro-amount"><?= round($fat) ?></div>
                                <div class="macro-name" style="color: var(--fat-color);">Fat</div>
                                <div class="macro-grams"><?= round($fat) ?>g</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 2rem;">
                            <i class="fas fa-apple-alt"></i>
                            <p>No nutrition data for today</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Log Today's Weight -->
                <div class="weight-form-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <i class="fas fa-weight-scale me-2"></i>Log New Weight
                        </div>
                    </div>

                    <form method="POST" id="weightForm">
                        <input type="hidden" name="action" value="log_weight">
                        <div class="mb-3">
                            <label class="form-label">Enter your current weight</label>
                            <div class="input-group">
                                <input type="number" step="0.1" min="20" max="500" name="weight" class="form-control"
                                    placeholder="e.g. 72.5"
                                    required>
                                <span class="input-unit">kg</span>
                            </div>
                            <div class="form-text">This will add a new weight entry to your history</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-2"></i>Add Weight Entry
                        </button>
                    </form>

                    <div class="last-recorded">
                        <i class="fas fa-history me-2"></i>
                        <?php if (!empty($progress)): ?>
                            Last recorded: <strong><?= number_format($current_weight, 1) ?> kg</strong>
                            <?php if ($last_weight_date): 
                                $dateObj = new DateTime($last_weight_date, new DateTimeZone('Asia/Karachi'));
                            ?>
                                <div class="small text-muted"><?= $dateObj->format('M j, g:i A') ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            No weight recorded yet
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div class="mobile-nav">
        <a href="../dashboard.php" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="../workouts/" class="mobile-nav-item">
            <i class="fas fa-dumbbell"></i>
            <span>Workouts</span>
        </a>
        <a href="../meals/" class="mobile-nav-item">
            <i class="fas fa-utensils"></i>
            <span>Meals</span>
        </a>
        <a href="charts.php" class="mobile-nav-item active">
            <i class="fas fa-chart-line"></i>
            <span>Progress</span>
        </a>
        <a href="../profile.php" class="mobile-nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let weightChart = null;
        let macroChart = null;

        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($dates) && !empty($weights)): ?>
                createWeightChart(<?= json_encode($dates) ?>, <?= json_encode($weights) ?>);
            <?php endif; ?>

            // Only create macro chart if there's data
            if (<?= ($protein + $carbs + $fat) ?> > 0) {
                createMacroChart(<?= $protein ?>, <?= $carbs ?>, <?= $fat ?>);
            }

            // Weight form submission
            document.getElementById('weightForm')?.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                submitBtn.disabled = true;

                // Submit form to the same page
                fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Network error. Please try again.');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        function createWeightChart(dates, weights) {
            const ctx = document.getElementById('weightChart');
            if (!ctx) return;

            // Clear previous chart if exists
            if (weightChart) {
                weightChart.destroy();
            }

            weightChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Weight (kg)',
                        data: weights,
                        borderColor: '#3a86ff',
                        backgroundColor: 'rgba(58, 134, 255, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3a86ff',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 14
                            },
                            callbacks: {
                                label: function(context) {
                                    return `Weight: ${context.parsed.y} kg`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                drawBorder: false
                            },
                            title: {
                                display: true,
                                text: 'Weight (kg)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxTicksLimit: 10 // Limit number of labels to avoid clutter
                            }
                        }
                    }
                }
            });
        }

        function createMacroChart(protein, carbs, fat) {
            const ctx = document.getElementById('macroChart');
            if (!ctx) return;

            // Clear previous chart if exists
            if (macroChart) {
                macroChart.destroy();
            }

            macroChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Protein', 'Carbs', 'Fat'],
                    datasets: [{
                        data: [protein, carbs, fat],
                        backgroundColor: ['#3a86ff', '#ff006e', '#ffbe0b'],
                        borderWidth: 0,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.parsed}g`;
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }

        function changeChartView(range) {
            // Update active button
            document.querySelectorAll('.chart-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // In a real application, this would fetch new data for the selected range
            console.log('Changing chart view to:', range);
            // You would typically make an AJAX call here to get filtered data
        }
    </script>
</body>

</html>