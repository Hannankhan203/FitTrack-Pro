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
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
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
            --primary: #00d4ff;
            --primary-dark: #0099cc;
            --secondary: #ff2d75;
            --accent: #9d4edd;
            --success: #00e676;
            --warning: #ffc107;
            --error: #ff4757;
            --dark: #0a0f23;
            --darker: #070a17;
            --light: #f8fafc;
            --gray: #64748b;
            --card-bg: rgba(255, 255, 255, 0.03);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --gradient: linear-gradient(135deg, #00d4ff 0%, #9d4edd 50%, #ff2d75 100%);
            --gradient-primary: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            --gradient-secondary: linear-gradient(135deg, #ff2d75 0%, #ff006e 100%);
            --gradient-accent: linear-gradient(135deg, #9d4edd 0%, #8338ec 100%);
            --gradient-success: linear-gradient(135deg, #00e676 0%, #00b894 100%);
            --neon-shadow: 0 0 30px rgba(0, 212, 255, 0.4);
            --glow: drop-shadow(0 0 10px rgba(0, 212, 255, 0.5));
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--darker);
            color: var(--light);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            padding-top: 80px;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 10% 20%, rgba(0, 212, 255, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(255, 45, 117, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(157, 78, 221, 0.1) 0%, transparent 60%);
            z-index: -2;
        }

        /* ==================== NAVIGATION ==================== */
        .navbar {
            background: rgba(10, 15, 35, 0.98) !important;
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.8rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
            height: 80px;
        }

        .navbar-brand.logo {
            font-size: 1.8rem;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            filter: var(--glow);
            letter-spacing: -0.5px;
            margin-left: 1rem;
        }

        .navbar-toggler {
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            padding: 0.5rem 0.8rem;
            margin-right: 1rem;
            background: transparent !important;
            transition: all 0.3s ease;
            display: none;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 2px rgba(0, 212, 255, 0.3) !important;
            outline: none !important;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
            width: 1.5em;
            height: 1.5em;
            transition: transform 0.3s ease;
        }

        .navbar-collapse {
            background: rgba(10, 15, 35, 0.98);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 0 0 15px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: none;
        }

        .navbar-nav {
            gap: 0.5rem;
        }

        .navbar-nav .nav-link {
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .navbar-nav .nav-link.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 20px rgba(0, 212, 255, 0.3);
        }

        .navbar-nav .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link i {
            font-size: 1.2rem;
            width: 20px;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(157, 78, 221, 0.1));
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.3);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, var(--primary) 50%, var(--accent) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .page-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        /* Stats Container */
        .stats-container {
            background: linear-gradient(145deg, rgba(0, 212, 255, 0.05), rgba(0, 212, 255, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.1);
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 2rem;
            border-left: 5px solid var(--primary);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            height: 100%;
            margin-bottom: 1.5rem;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            background: rgba(0, 212, 255, 0.08);
            border-left-color: var(--accent);
            box-shadow: 0 15px 40px rgba(0, 212, 255, 0.15);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            background: var(--gradient-primary);
            color: white;
        }

        .stats-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stats-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .stats-subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        /* Trend Indicators */
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
            background: rgba(0, 230, 118, 0.1);
            color: var(--success);
        }

        .trend-down {
            background: rgba(255, 45, 117, 0.1);
            color: var(--secondary);
        }

        .trend-neutral {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
        }

        /* Chart Container - FIXED FOR MOBILE */
        .chart-container {
            background: linear-gradient(145deg, rgba(157, 78, 221, 0.05), rgba(157, 78, 221, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(157, 78, 221, 0.15);
            border-radius: 28px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(157, 78, 221, 0.1);
            height: auto;
            min-height: 300px;
            position: relative;
            overflow: hidden;
        }

        .chart-wrapper {
            width: 100%;
            height: 100%;
            min-height: 250px;
            position: relative;
        }

        .chart-canvas-container {
            width: 100%;
            height: 100%;
            position: relative;
        }

        #weightChart {
            width: 100% !important;
            height: 100% !important;
            min-height: 250px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .chart-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 200px;
        }

        .chart-title i {
            color: var(--accent);
            font-size: 1.3rem;
        }

        .chart-controls {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .chart-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 45px;
            text-align: center;
        }

        .chart-btn:hover,
        .chart-btn.active {
            background: var(--gradient-primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);
        }

        /* Recent Weights Container */
        .recent-weights {
            background: linear-gradient(145deg, rgba(255, 45, 117, 0.05), rgba(255, 45, 117, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 45, 117, 0.15);
            border-radius: 28px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(255, 45, 117, 0.1);
        }

        /* Table Styles */
        .recent-weights table {
            width: 100%;
            color: rgba(255, 255, 255, 0.9);
        }

        .recent-weights th {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            padding: 1rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            text-align: left;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .recent-weights td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: white;
        }

        .recent-weights tr:last-child td {
            border-bottom: none;
        }

        .recent-weights tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        /* Macronutrient Card */
        .macro-card {
            background: linear-gradient(145deg, rgba(0, 212, 255, 0.05), rgba(0, 212, 255, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 28px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.1);
        }

        .macro-donut {
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem auto;
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
            border-radius: 16px;
            transition: transform 0.3s ease;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .macro-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.05);
        }

        .macro-item.protein {
            border-color: rgba(58, 134, 255, 0.2);
        }

        .macro-item.carbs {
            border-color: rgba(255, 0, 110, 0.2);
        }

        .macro-item.fat {
            border-color: rgba(255, 190, 11, 0.2);
        }

        .macro-amount {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: white;
        }

        .macro-name {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .macro-grams {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        /* Weight Form Card */
        .weight-form-card {
            background: linear-gradient(145deg, rgba(157, 78, 221, 0.05), rgba(157, 78, 221, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(157, 78, 221, 0.15);
            border-radius: 28px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(157, 78, 221, 0.1);
        }

        /* Form Elements */
        .form-label {
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: white;
            display: block;
            font-size: 1.1rem;
        }

        .input-group {
            position: relative;
        }

        .form-control {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .input-unit {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-weight: 500;
            pointer-events: none;
        }

        .form-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: block;
        }

        /* Buttons */
        .btn {
            border: none;
            padding: 0.875rem 1.75rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
        }

        .btn-success {
            background: var(--gradient-success);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 230, 118, 0.3);
        }

        .btn-w-100 {
            width: 100%;
        }

        /* Last Recorded Section */
        .last-recorded {
            background: rgba(255, 255, 255, 0.03);
            padding: 1rem;
            border-radius: 12px;
            margin-top: 1.5rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .last-recorded i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state-icon {
            font-size: 4rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: white;
            letter-spacing: -0.5px;
        }

        .empty-state p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            font-size: 1rem;
        }

        /* Alerts */
        .alert {
            position: relative;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            border: 1px solid;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .alert-success {
            background: rgba(0, 230, 118, 0.1);
            border-color: rgba(0, 230, 118, 0.2);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(255, 45, 117, 0.1);
            border-color: rgba(255, 45, 117, 0.2);
            color: var(--secondary);
        }

        .alert i {
            margin-right: 0.5rem;
        }

        .btn-close {
            filter: invert(1) brightness(2);
            opacity: 0.7;
        }

        .btn-close:hover {
            opacity: 1;
        }

        /* Mobile navbar close button */
        .navbar-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1003;
            display: none;
        }

        .navbar-close:hover {
            background: var(--secondary);
            transform: rotate(90deg);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient);
            border-radius: 6px;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        /* ==================== RESPONSIVE DESIGN ==================== */
        /* Large screens (992px and up) - Desktop navigation visible */
        @media (min-width: 992px) {
            .navbar-collapse {
                display: flex !important;
                background: transparent;
                border: none;
                padding: 0;
                margin: 0;
            }

            .navbar-nav {
                flex-direction: row;
            }

            .navbar-toggler {
                display: none;
            }

            .mobile-nav {
                display: none;
            }

            .navbar-close {
                display: none !important;
            }

            .chart-container {
                height: 400px;
            }

            .chart-wrapper {
                height: 300px;
            }
        }

        /* Medium and small screens (below 992px) - Mobile navigation */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                position: fixed;
                top: 80px;
                left: 0;
                right: 0;
                bottom: 0;
                backdrop-filter: blur(30px);
                -webkit-backdrop-filter: blur(30px);
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 0;
                padding: 2rem;
                margin: 0;
                overflow: hidden;
                opacity: 0;
                visibility: hidden;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
                display: block !important;
                z-index: 1001;
                height: 100vh;
            }

            .navbar-collapse.show {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
                overflow-y: auto;
                height: 100vh;
            }

            .navbar-nav {
                flex-direction: column;
                gap: 1rem;
                padding: 2rem 0;
                min-height: 120vh;
            }

            .navbar-toggler {
                display: block;
                z-index: 1002;
                position: relative;
            }

            .navbar-collapse.show .navbar-close {
                display: flex;
            }

            .navbar-nav .nav-link {
                padding: 1.2rem 1.5rem;
                font-size: 1.1rem;
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.05);
                margin-bottom: 0.5rem;
                text-align: center;
                justify-content: center;
            }

            .navbar-nav .nav-link i {
                font-size: 1.3rem;
                width: 24px;
            }

            body {
                padding-bottom: 20px;
                overflow-x: hidden;
            }

            .navbar {
                height: 80px;
                z-index: 1000;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
            }

            .navbar-brand.logo {
                font-size: 1.5rem;
                z-index: 1003;
                position: relative;
            }

            body.menu-open {
                overflow: hidden;
                position: fixed;
                width: 100%;
                height: 100%;
            }

            .navbar-collapse.show::before {
                content: '';
                position: fixed;
                top: 80px;
                left: 0;
                right: 0;
                bottom: 0;
                backdrop-filter: blur(5px);
                z-index: -1;
            }

            /* Chart adjustments for tablet */
            .chart-container {
                height: 350px;
                padding: 1.5rem;
            }

            .chart-wrapper {
                height: 250px;
            }
        }

        /* Tablet (768px and below) */
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem;
                margin: 1rem 0;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .page-header p {
                font-size: 1rem;
            }

            .stats-container,
            .chart-container,
            .recent-weights,
            .macro-card,
            .weight-form-card {
                padding: 1.5rem;
                border-radius: 24px;
                margin-bottom: 1.5rem;
            }

            .stats-card {
                padding: 1.2rem;
                border-radius: 18px;
            }

            .stats-icon {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }

            .stats-value {
                font-size: 1.8rem;
            }

            .chart-container {
                height: 320px;
            }

            .chart-wrapper {
                height: 220px;
            }

            .chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .chart-controls {
                align-self: stretch;
                justify-content: center;
            }

            .chart-btn {
                flex: 1;
                min-width: 60px;
            }

            .macro-donut {
                width: 180px;
                height: 180px;
            }

            .macro-breakdown {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.75rem;
            }

            .form-control {
                padding: 0.875rem;
            }

            .btn {
                padding: 0.75rem 1.5rem;
            }

            .trend-indicator {
                font-size: 0.8rem;
                padding: 0.2rem 0.5rem;
            }

            /* Table responsive */
            .recent-weights .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .recent-weights table {
                min-width: 500px;
            }
        }

        /* Mobile (576px and below) */
        @media (max-width: 576px) {
            .navbar {
                height: 70px;
            }

            .navbar-brand.logo {
                font-size: 1.3rem;
                margin-left: 0.5rem;
            }

            .navbar-toggler {
                padding: 0.4rem 0.6rem;
                margin-right: 0.5rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .page-header p {
                font-size: 0.9rem;
            }

            .stats-container h5,
            .chart-container h5,
            .macro-card h5,
            .weight-form-card h5 {
                font-size: 1.2rem;
            }

            .stats-card {
                padding: 1rem;
            }

            .stats-value {
                font-size: 1.5rem;
            }

            .chart-container {
                height: 430px !important;
                padding: 1.25rem;
                /* border: 10px solid black; */
            }

            .chart-wrapper {
                height: 200px;
            }

            .chart-btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.8rem;
                min-width: 50px;
            }

            .chart-title {
                font-size: 1.3rem;
            }

            .macro-donut {
                width: 150px;
                height: 150px;
            }

            .macro-breakdown {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .macro-item {
                padding: 0.75rem;
            }

            .form-control {
                padding: 0.75rem;
                font-size: 0.95rem;
            }

            .btn {
                padding: 0.75rem 1.25rem;
                font-size: 0.95rem;
            }

            .navbar-collapse {
                top: 70px;
                padding: 1.5rem;
            }

            .navbar-close {
                top: 15px;
                right: 15px;
                width: 35px;
                height: 35px;
            }

            .navbar-nav .nav-link {
                padding: 1rem;
                font-size: 1rem;
            }

            /* Fix for very small screens */
            @media (max-width: 380px) {
                .chart-container {
                    height: 280px;
                }

                .chart-wrapper {
                    height: 180px;
                }

                .chart-controls {
                    gap: 0.25rem;
                }

                .chart-btn {
                    padding: 0.3rem 0.5rem;
                    font-size: 0.75rem;
                    min-width: 40px;
                }
            }
        }
    </style>
</head>

<body>
    <!-- Desktop Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand logo" href="../dashboard.php">FitTrack Pro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Close button for mobile (hidden on desktop) -->
                <button class="navbar-close" id="navbarClose" type="button">
                    <i class="fas fa-times"></i>
                </button>
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
        <div class="stats-container">
            <h4 class="mb-4"><i class="fas fa-chart-bar me-2 text-primary"></i>Today's Progress Summary</h4>
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stats-value"><?= number_format($calories) ?></div>
                        <div class="stats-label">Calories Today</div>
                        <div class="stats-subtitle">Total kcal consumed</div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-weight"></i>
                        </div>
                        <div class="stats-value"><?= $current_weight ? number_format($current_weight, 1) : '--' ?></div>
                        <div class="stats-label">Current Weight</div>
                        <div class="stats-subtitle"><?= count($progress) ?> records</div>
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
                        <div class="stats-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="stats-value"><?= $weeklyWorkouts['count'] ?? 0 ?></div>
                        <div class="stats-label">Weekly Workouts</div>
                        <div class="stats-subtitle">This week</div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="stats-value"><?= round($goal_progress) ?>%</div>
                        <div class="stats-label">Goal Progress</div>
                        <div class="stats-subtitle"><?= isset($goal_info['goal_type']) ? ucfirst($goal_info['goal_type']) . ' weight' : 'No goal set' ?></div>
                    </div>
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
                    <div class="chart-wrapper">
                        <?php if (!empty($dates) && !empty($weights)): ?>
                            <div class="chart-canvas-container">
                                <canvas id="weightChart"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="empty-state" style="height: 250px; display: flex; flex-direction: column; justify-content: center;">
                                <div class="empty-state-icon">
                                    <i class="fas fa-weight-scale"></i>
                                </div>
                                <h4>No Weight Data</h4>
                                <p>Start logging your weight to see progress charts</p>
                            </div>
                        <?php endif; ?>
                    </div>
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
                                <div class="macro-name">Protein</div>
                                <div class="macro-grams"><?= round($protein) ?>g</div>
                            </div>
                            <div class="macro-item carbs">
                                <div class="macro-amount"><?= round($carbs) ?></div>
                                <div class="macro-name">Carbs</div>
                                <div class="macro-grams"><?= round($carbs) ?>g</div>
                            </div>
                            <div class="macro-item fat">
                                <div class="macro-amount"><?= round($fat) ?></div>
                                <div class="macro-name">Fat</div>
                                <div class="macro-grams"><?= round($fat) ?>g</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-apple-alt"></i>
                            </div>
                            <h4>No nutrition data for today</h4>
                            <p>Track your meals to see nutrition breakdown</p>
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

            // Mobile navigation handling
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navbarClose = document.getElementById('navbarClose');

            if (navbarToggler) {
                navbarToggler.addEventListener('click', function() {
                    const icon = this.querySelector('.navbar-toggler-icon');
                    if (icon) {
                        if (this.getAttribute('aria-expanded') === 'true') {
                            icon.style.transform = 'rotate(90deg)';
                            document.body.classList.add('menu-open');
                        } else {
                            icon.style.transform = 'rotate(0deg)';
                            document.body.classList.remove('menu-open');
                        }
                    }
                });
            }

            if (navbarClose) {
                navbarClose.addEventListener('click', function() {
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    if (navbarCollapse.classList.contains('show')) {
                        navbarToggler.click();
                    }
                });
            }

            // Close menu when clicking on a nav link (on mobile)
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        const navbarCollapse = document.querySelector('.navbar-collapse');
                        if (navbarCollapse.classList.contains('show')) {
                            navbarToggler.click();
                        }
                    }
                });
            });

            // Close menu when clicking outside (for mobile)
            document.addEventListener('click', function(event) {
                if (window.innerWidth < 992) {
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    const isClickInsideNavbar = document.querySelector('.navbar').contains(event.target);

                    if (navbarCollapse && navbarCollapse.classList.contains('show') && !isClickInsideNavbar) {
                        navbarToggler.click();
                    }
                }
            });

            // Handle window resize for chart responsiveness
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    if (weightChart) {
                        weightChart.resize();
                    }
                    if (macroChart) {
                        macroChart.resize();
                    }
                }, 250);
            });
        });

        function createWeightChart(dates, weights) {
            const ctx = document.getElementById('weightChart');
            if (!ctx) return;

            // Clear previous chart if exists
            if (weightChart) {
                weightChart.destroy();
            }

            // Determine number of ticks based on screen size
            const maxTicksLimit = window.innerWidth < 768 ? 5 : window.innerWidth < 992 ? 8 : 10;

            // Format dates for mobile if needed
            const formattedDates = dates.map(date => {
                if (window.innerWidth < 576) {
                    // Shorten date format for mobile
                    return date.replace(/([A-Za-z]{3}) (\d{1,2}) .*/, '$1 $2');
                }
                return date;
            });

            weightChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: formattedDates,
                    datasets: [{
                        label: 'Weight (kg)',
                        data: weights,
                        borderColor: '#00d4ff',
                        backgroundColor: 'rgba(0, 212, 255, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#00d4ff',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: window.innerWidth < 768 ? 3 : 5,
                        pointHoverRadius: window.innerWidth < 768 ? 6 : 8
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
                            backgroundColor: 'rgba(10, 15, 35, 0.9)',
                            borderColor: 'rgba(0, 212, 255, 0.3)',
                            borderWidth: 1,
                            titleColor: 'white',
                            bodyColor: 'white',
                            titleFont: {
                                size: window.innerWidth < 768 ? 12 : 14
                            },
                            bodyFont: {
                                size: window.innerWidth < 768 ? 12 : 14
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
                                color: 'rgba(255, 255, 255, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                font: {
                                    size: window.innerWidth < 768 ? 10 : 12
                                }
                            },
                            title: {
                                display: true,
                                text: 'Weight (kg)',
                                color: 'rgba(255, 255, 255, 0.9)',
                                font: {
                                    size: window.innerWidth < 768 ? 11 : 13
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                maxTicksLimit: maxTicksLimit,
                                font: {
                                    size: window.innerWidth < 768 ? 9 : 11
                                },
                                callback: function(value, index, values) {
                                    // Show fewer labels on mobile
                                    if (window.innerWidth < 576 && index % 2 !== 0) {
                                        return '';
                                    }
                                    return this.getLabelForValue(value);
                                }
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
                        hoverOffset: window.innerWidth < 768 ? 10 : 15
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
                            backgroundColor: 'rgba(10, 15, 35, 0.9)',
                            borderColor: 'rgba(0, 212, 255, 0.3)',
                            borderWidth: 1,
                            titleColor: 'white',
                            bodyColor: 'white',
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.parsed}g`;
                                }
                            }
                        }
                    },
                    cutout: window.innerWidth < 768 ? '60%' : '65%'
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