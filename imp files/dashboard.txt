<?php
require_once 'includes/functions.php';
require_login();

require_once 'includes/db.php';
$user_id = get_user_id();

// ==================== PAKISTAN ISLAMABAD TIMEZONE ====================
// Set timezone to Pakistan (Islamabad) - UTC+5
date_default_timezone_set('Asia/Karachi');

// Get current date and time in Pakistan time
$today = date('Y-m-d');
$current_time = date('H:i:s');
$current_datetime = date('Y-m-d H:i:s');
$display_date = date('l, F j, Y');  // Format: Monday, January 15, 2024
$display_time = date('g:i A');      // Format: 2:30 PM

// Dynamic greeting based on time of day
$hour = date('H');
if ($hour < 5) {  // Changed from 12 to 5 for "Good night"
    $greeting = "Good night";
} elseif ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 17) {
    $greeting = "Good afternoon";
} elseif ($hour < 20) {
    $greeting = "Good evening";
} else {
    $greeting = "Good night";
}

// Timezone offset for Pakistan (UTC+5)
$timezone_offset = '+05:00';

// Store the actual Pakistan time values for JavaScript
$pakistan_date_js = date('Y-m-d');
$pakistan_time_js = date('H:i:s');
$pakistan_hour_js = date('H');
$pakistan_timestamp = time(); // Current timestamp in Pakistan time
// ================================================================

// Get today's workout stats
$stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(duration) as total_duration FROM workouts WHERE user_id = ? AND DATE(CONVERT_TZ(date, '+00:00', '+05:00')) = ?");
$stmt->execute([$user_id, $today]);
$today_workout = $stmt->fetch();

// Get today's exercises for display
$stmt = $pdo->prepare("SELECT exercise, sets, reps, weight, duration, distance, date FROM workouts WHERE user_id = ? AND DATE(CONVERT_TZ(date, '+00:00', '+05:00')) = ? ORDER BY date DESC LIMIT 5");
$stmt->execute([$user_id, $today]);
$today_exercises = $stmt->fetchAll();

// Get today's meals for display
$stmt = $pdo->prepare("SELECT meal_time, food_name, calories, protein, carbs, fat, date FROM meals WHERE user_id = ? AND DATE(CONVERT_TZ(date, '+00:00', '+05:00')) = ? ORDER BY 
    CASE meal_time 
        WHEN 'breakfast' THEN 1
        WHEN 'lunch' THEN 2
        WHEN 'dinner' THEN 3
        WHEN 'snack' THEN 4
        ELSE 5
    END, id DESC LIMIT 5");
$stmt->execute([$user_id, $today]);
$today_meals_display = $stmt->fetchAll();

// Get meal stats
$stmt = $pdo->prepare("SELECT SUM(calories) as total_calories FROM meals WHERE user_id = ? AND DATE(CONVERT_TZ(date, '+00:00', '+05:00')) = ?");
$stmt->execute([$user_id, $today]);
$today_meals = $stmt->fetch();

// Get goal info
$stmt = $pdo->prepare("SELECT goal_weight, goal_type, weight FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$goal_info = $stmt->fetch();

// Calculate goal progress
$goal_progress = 0;
if ($goal_info && $goal_info['goal_weight'] && $goal_info['weight']) {
    if ($goal_info['goal_type'] == 'lose') {
        if ($goal_info['weight'] > $goal_info['goal_weight']) {
            $total_to_lose = $goal_info['weight'] - $goal_info['goal_weight'];
            $current_lost = isset($goal_info['current_lost']) ? $goal_info['current_lost'] : 0;
            $goal_progress = min(100, ($current_lost / $total_to_lose) * 100);
        }
    } elseif ($goal_info['goal_type'] == 'gain') {
        if ($goal_info['weight'] < $goal_info['goal_weight']) {
            $total_to_gain = $goal_info['goal_weight'] - $goal_info['weight'];
            $current_gain = isset($goal_info['current_gain']) ? $goal_info['current_gain'] : 0;
            $goal_progress = min(100, ($current_gain / $total_to_gain) * 100);
        }
    } else {
        $goal_progress = 100;
    }
}

// Calculate streak manually with Pakistan timezone
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(CONVERT_TZ(date, '+00:00', '+05:00'))) as streak FROM (
    SELECT date FROM workouts WHERE user_id = ? AND DATE(CONVERT_TZ(date, '+00:00', '+05:00')) <= ? 
    ORDER BY date DESC
) as recent_workouts WHERE DATE(CONVERT_TZ(date, '+00:00', '+05:00')) >= DATE_SUB(?, INTERVAL 6 DAY)");
$stmt->execute([$user_id, $today, $today]);
$streak_data = $stmt->fetch();
$streak = $streak_data['streak'] ?? 0;

// Get achievements count
$stmt = $pdo->prepare("SELECT COUNT(*) as badge_count FROM achievements WHERE user_id = ?");
$stmt->execute([$user_id]);
$achievements_data = $stmt->fetch();
$achievements_count = $achievements_data['badge_count'] ?? 0;

// Get weekly workout minutes for chart (Pakistan timezone)
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT SUM(duration) as total FROM workouts WHERE user_id = ? AND DATE(CONVERT_TZ(date, '+00:00', '+05:00')) = ?");
    $stmt->execute([$user_id, $day]);
    $day_data = $stmt->fetch();
    $weekly_data[] = [
        'day' => date('D', strtotime($day)),
        'minutes' => $day_data['total'] ?? 0
    ];
}

// Function to format datetime in Pakistan timezone
function formatPakistanTime($datetime) {
    if (empty($datetime)) return '';
    
    // If the datetime is already in Pakistan timezone (from CONVERT_TZ in query)
    // just format it
    $date = new DateTime($datetime, new DateTimeZone('Asia/Karachi'));
    return $date->format('g:i A');
}

// Get server timestamp for JavaScript - in Pakistan timezone
$server_timestamp = $pakistan_timestamp * 1000; // Convert to milliseconds for JavaScript
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - FitTrack Pro</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <!-- Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #3a86ff;
            --primary-dark: #2667cc;
            --secondary-color: #ff006e;
            --accent-color: #8338ec;
            --success-color: #38b000;
            --warning-color: #ffbe0b;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gradient-primary: linear-gradient(135deg, #3a86ff, #8338ec);
            --gradient-secondary: linear-gradient(135deg, #ff006e, #fb5607);
            --gradient-success: linear-gradient(135deg, #38b000, #70e000);
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
        
        .nav-link:hover, .nav-link.active {
            background: var(--gradient-primary);
            color: white;
        }
        
        .nav-link i {
            margin-right: 8px;
        }
        
        .dashboard-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .dashboard-header h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        
        .greeting {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .date-display {
            background: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            margin-top: 1rem;
        }
        
        .date-display i {
            margin-right: 10px;
        }
        
        .streak-badge {
            background: var(--gradient-secondary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            font-weight: 600;
            margin-left: 1rem;
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
        
        .progress-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
        }
        
        .progress-title {
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .progress-bar-custom {
            height: 12px;
            border-radius: 10px;
            background-color: #e9ecef;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 10px;
            background: var(--gradient-success);
            transition: width 1s ease-in-out;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .action-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            color: inherit;
        }
        
        .action-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
        
        .action-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .action-desc {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .goal-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }
        
        .goal-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .goal-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .goal-stats {
            display: flex;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
            margin: 1rem 0;
        }
        
        .goal-stat {
            text-align: center;
            flex: 1;
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
        
        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }
        
        .chart-title {
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .footer {
            text-align: center;
            padding: 2rem 0;
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 3rem;
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
        
        /* Today's Exercises - IMPROVED STYLES */
        .exercises-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.75rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }
        
        .exercises-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f4ff;
        }
        
        .exercises-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .exercises-count {
            background: var(--gradient-primary);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            font-weight: 700;
        }
        
        .exercise-item {
            background: #f8f9ff;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .exercise-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(58, 134, 255, 0.15);
        }
        
        .exercise-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
        }
        
        .exercise-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        
        .exercise-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }
        
        .exercise-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .exercise-time {
            color: #6c757d;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .exercise-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            background: white;
            border-radius: 10px;
            padding: 0.75rem;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 0.9rem;
        }
        
        .stat-value {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.125rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .cardio-stat {
            color: #ff006e;
            background: rgba(255, 0, 110, 0.1);
        }
        
        .strength-stat {
            color: #3a86ff;
            background: rgba(58, 134, 255, 0.1);
        }
        
        .weight-stat {
            color: #38b000;
            background: rgba(56, 176, 0, 0.1);
        }
        
        .time-stat {
            color: #8338ec;
            background: rgba(131, 56, 236, 0.1);
        }
        
        .empty-exercises {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .empty-exercises-icon {
            font-size: 4rem;
            color: #e0e7ff;
            margin-bottom: 1.5rem;
        }
        
        .empty-exercises h4 {
            color: #495057;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .empty-exercises p {
            color: #6c757d;
            max-width: 300px;
            margin: 0 auto 1.5rem;
        }
        
        .view-all-exercises {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f4ff;
        }
        
        /* Today's Meals Card - UPDATED STYLES (Time Removed) */
        .meals-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.75rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }
        
        .meals-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f4ff;
        }
        
        .meals-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .meals-count {
            background: linear-gradient(135deg, #ff006e, #fb5607);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            font-weight: 700;
        }
        
        .meal-item {
            background: #fff9fb;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid #ff006e;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .meal-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 0, 110, 0.15);
        }
        
        .meal-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #ff006e;
        }
        
        .meal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .meal-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }
        
        .meal-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Removed .meal-time styles */
        
        .meal-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .meal-stat-item {
            background: white;
            border-radius: 10px;
            padding: 0.75rem;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .meal-stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 0.9rem;
        }
        
        .meal-stat-value {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.125rem;
        }
        
        .meal-stat-label {
            color: #6c757d;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .calorie-stat {
            color: #ff006e;
            background: rgba(255, 0, 110, 0.1);
        }
        
        .protein-stat {
            color: #3a86ff;
            background: rgba(58, 134, 255, 0.1);
        }
        
        .carbs-stat {
            color: #38b000;
            background: rgba(56, 176, 0, 0.1);
        }
        
        .fat-stat {
            color: #ffbe0b;
            background: rgba(255, 190, 11, 0.1);
        }
        
        .empty-meals {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .empty-meals-icon {
            font-size: 4rem;
            color: #ffe0eb;
            margin-bottom: 1.5rem;
        }
        
        .empty-meals h4 {
            color: #495057;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .empty-meals p {
            color: #6c757d;
            max-width: 300px;
            margin: 0 auto 1.5rem;
        }
        
        .view-all-meals {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f4ff;
        }
        
        @media (max-width: 768px) {
            .mobile-nav {
                display: flex;
                justify-content: space-around;
            }
            
            .navbar-nav {
                display: none;
            }
            
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .exercise-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .exercise-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .meal-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .meal-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .date-display {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                padding: 0.75rem 1rem;
            }
            
            .date-display i {
                margin-right: 8px;
            }
        }
        
        @media (max-width: 576px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .goal-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .exercise-stats {
                grid-template-columns: 1fr;
            }
            
            .meal-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Desktop Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand logo" href="dashboard.php">FitTrack Pro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/fitness-tracker/workouts/log.php">
                            <i class="fas fa-dumbbell"></i> Workouts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/fitness-tracker/meals/planner.php">
                            <i class="fas fa-utensils"></i> Meals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/fitness-tracker/progress/charts.php">
                            <i class="fas fa-chart-line"></i> Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/fitness-tracker/progress/photos.php">
                            <i class="fas fa-camera"></i> Photos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/fitness-tracker/profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 id="dynamic-greeting"><?= $greeting ?>, <?= htmlspecialchars($_SESSION['user_name']) ?>! 👋</h1>
                    <p class="greeting">Keep pushing towards your fitness goals. You're doing great!</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="date-display">
                            <i class="fas fa-calendar-alt"></i>
                            <span id="current-date"><?= $display_date ?></span>
                            <i class="fas fa-clock ms-3"></i>
                            <span id="current-time"><?= $display_time ?></span>
                            <span class="ms-2 small">PKT (Islamabad)</span>
                        </div>
                        <?php if($streak > 0): ?>
                        <div class="streak-badge">
                            <i class="fas fa-fire me-2"></i><?= $streak ?> Day Streak
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-inline-block text-start bg-white rounded p-3 shadow-sm">
                        <div class="text-muted small">Fitness Level</div>
                        <div class="fw-bold text-primary">Intermediate</div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: 65%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Today's Exercises Card -->
        <div class="exercises-card mb-4">
            <div class="exercises-header">
                <div class="exercises-title">
                    <i class="fas fa-dumbbell fa-lg text-primary"></i>
                    <h4 class="mb-0">Today's Exercises</h4>
                    <div class="exercises-count"><?= count($today_exercises) ?></div>
                </div>
                <?php if(!empty($today_exercises)): ?>
                <a href="/fitness-tracker/workouts/log.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus me-1"></i>Add More
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($today_exercises)): ?>
                <div class="exercises-list">
                    <?php foreach ($today_exercises as $exercise): ?>
                        <?php
                        // Determine exercise type and styling
                        $exercise_name = htmlspecialchars($exercise['exercise']);
                        $is_cardio = in_array(strtolower($exercise_name), ['running', 'cycling', 'swimming', 'jump rope', 'elliptical', 'burpees', 'rowing', 'stair climber', 'walking', 'jogging']);
                        
                        // Set category and color
                        $category = 'Strength';
                        $category_color = '#3a86ff';
                        
                        if ($is_cardio) {
                            $category = 'Cardio';
                            $category_color = '#ff006e';
                        }
                        
                        // Format time in Pakistan timezone
                        $time_display = '';
                        if (!empty($exercise['date'])) {
                            $time = formatPakistanTime($exercise['date']);
                            $time_display = '<div class="exercise-time"><i class="far fa-clock"></i> ' . $time . '</div>';
                        }
                        ?>
                        
                        <div class="exercise-item">
                            <div class="exercise-header">
                                <div>
                                    <div class="exercise-name"><?= $exercise_name ?></div>
                                    <span class="exercise-category" style="background: <?= $category_color ?>20; color: <?= $category_color ?>;">
                                        <?= $category ?>
                                    </span>
                                </div>
                                <?= $time_display ?>
                            </div>
                            
                            <div class="exercise-stats">
                                <?php if (!$is_cardio && !empty($exercise['sets'])): ?>
                                    <div class="stat-item">
                                        <div class="stat-icon strength-stat">
                                            <i class="fas fa-redo"></i>
                                        </div>
                                        <div class="stat-value"><?= $exercise['sets'] ?></div>
                                        <div class="stat-label">Sets</div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!$is_cardio && !empty($exercise['reps'])): ?>
                                    <div class="stat-item">
                                        <div class="stat-icon strength-stat">
                                            <i class="fas fa-sync-alt"></i>
                                        </div>
                                        <div class="stat-value"><?= $exercise['reps'] ?></div>
                                        <div class="stat-label">Reps</div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!$is_cardio && !empty($exercise['weight']) && $exercise['weight'] > 0): ?>
                                    <div class="stat-item">
                                        <div class="stat-icon weight-stat">
                                            <i class="fas fa-weight-hanging"></i>
                                        </div>
                                        <div class="stat-value"><?= $exercise['weight'] ?> kg</div>
                                        <div class="stat-label">Weight</div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($is_cardio && !empty($exercise['distance']) && $exercise['distance'] > 0): ?>
                                    <div class="stat-item">
                                        <div class="stat-icon cardio-stat">
                                            <i class="fas fa-route"></i>
                                        </div>
                                        <div class="stat-value"><?= $exercise['distance'] ?> km</div>
                                        <div class="stat-label">Distance</div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($exercise['duration']) && $exercise['duration'] > 0): ?>
                                    <div class="stat-item">
                                        <div class="stat-icon time-stat">
                                            <i class="fas fa-stopwatch"></i>
                                        </div>
                                        <div class="stat-value"><?= $exercise['duration'] ?> min</div>
                                        <div class="stat-label">Duration</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($today_exercises) >= 5): ?>
                        <div class="view-all-exercises">
                            <a href="/fitness-tracker/workouts/log.php" class="btn btn-primary">
                                <i class="fas fa-list me-1"></i>View All Exercises
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-exercises">
                    <div class="empty-exercises-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h4>No exercises logged today</h4>
                    <p>Start your fitness journey by logging your first workout!</p>
                    <a href="/fitness-tracker/workouts/log.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Log Your First Workout
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Today's Meals Card - TIME PART REMOVED -->
        <div class="meals-card mb-4">
            <div class="meals-header">
                <div class="meals-title">
                    <i class="fas fa-utensils fa-lg" style="color: #ff006e;"></i>
                    <h4 class="mb-0">Today's Meals</h4>
                    <div class="meals-count"><?= count($today_meals_display) ?></div>
                </div>
                <?php if(!empty($today_meals_display)): ?>
                <a href="/fitness-tracker/meals/planner.php" class="btn btn-sm btn-outline-danger" style="border-color: #ff006e; color: #ff006e;">
                    <i class="fas fa-plus me-1"></i>Add More
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($today_meals_display)): ?>
                <div class="meals-list">
                    <?php foreach ($today_meals_display as $meal): ?>
                        <?php
                        // Format meal type
                        $meal_type = htmlspecialchars($meal['meal_time']);
                        $meal_name = htmlspecialchars($meal['food_name']);
                        
                        // Set color based on meal type
                        $meal_type_color = '#ff006e';
                        $meal_type_bg = 'rgba(255, 0, 110, 0.1)';
                        
                        switch($meal_type) {
                            case 'breakfast':
                                $meal_type_color = '#ff9a9e';
                                $meal_type_bg = 'rgba(255, 154, 158, 0.1)';
                                break;
                            case 'lunch':
                                $meal_type_color = '#3a86ff';
                                $meal_type_bg = 'rgba(58, 134, 255, 0.1)';
                                break;
                            case 'dinner':
                                $meal_type_color = '#38b000';
                                $meal_type_bg = 'rgba(56, 176, 0, 0.1)';
                                break;
                            case 'snack':
                                $meal_type_color = '#8338ec';
                                $meal_type_bg = 'rgba(131, 56, 236, 0.1)';
                                break;
                        }
                        ?>
                        
                        <div class="meal-item">
                            <div class="meal-header">
                                <div>
                                    <div class="meal-name"><?= $meal_name ?></div>
                                    <span class="meal-type" style="background: <?= $meal_type_bg ?>; color: <?= $meal_type_color ?>;">
                                        <?= ucfirst($meal_type) ?>
                                    </span>
                                </div>
                                <!-- Time display has been removed -->
                            </div>
                            
                            <div class="meal-stats">
                                <div class="meal-stat-item">
                                    <div class="meal-stat-icon calorie-stat">
                                        <i class="fas fa-fire"></i>
                                    </div>
                                    <div class="meal-stat-value"><?= $meal['calories'] ?></div>
                                    <div class="meal-stat-label">Calories</div>
                                </div>
                                
                                <?php if (!empty($meal['protein']) && $meal['protein'] > 0): ?>
                                    <div class="meal-stat-item">
                                        <div class="meal-stat-icon protein-stat">
                                            <i class="fas fa-drumstick-bite"></i>
                                        </div>
                                        <div class="meal-stat-value"><?= $meal['protein'] ?>g</div>
                                        <div class="meal-stat-label">Protein</div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($meal['carbs']) && $meal['carbs'] > 0): ?>
                                    <div class="meal-stat-item">
                                        <div class="meal-stat-icon carbs-stat">
                                            <i class="fas fa-bread-slice"></i>
                                        </div>
                                        <div class="meal-stat-value"><?= $meal['carbs'] ?>g</div>
                                        <div class="meal-stat-label">Carbs</div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($meal['fat']) && $meal['fat'] > 0): ?>
                                    <div class="meal-stat-item">
                                        <div class="meal-stat-icon fat-stat">
                                            <i class="fas fa-oil-can"></i>
                                        </div>
                                        <div class="meal-stat-value"><?= $meal['fat'] ?>g</div>
                                        <div class="meal-stat-label">Fat</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($today_meals_display) >= 5): ?>
                        <div class="view-all-meals">
                            <a href="/fitness-tracker/meals/planner.php" class="btn btn-danger">
                                <i class="fas fa-list me-1"></i>View All Meals
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-meals">
                    <div class="empty-meals-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h4>No meals logged today</h4>
                    <p>Track your nutrition by logging your first meal!</p>
                    <a href="/fitness-tracker/meals/planner.php" class="btn btn-danger btn-lg">
                        <i class="fas fa-plus me-2"></i>Log Your First Meal
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #ff9a9e, #fad0c4); color: #ff6b6b;">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <div class="stats-value"><?= $today_workout['count'] ?? 0 ?></div>
                    <div class="stats-label">Today's Workouts</div>
                    <div class="small text-muted mt-2">
                        <?php if(($today_workout['total_duration'] ?? 0) > 0): ?>
                        <i class="fas fa-clock me-1"></i><?= number_format($today_workout['total_duration'], 1) ?> min total
                        <?php else: ?>
                        <i class="fas fa-info-circle me-1"></i>No workouts today
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #a1c4fd, #c2e9fb); color: #4d96ff;">
                        <i class="fas fa-apple-alt"></i>
                    </div>
                    <div class="stats-value"><?= $today_meals['total_calories'] ?? 0 ?></div>
                    <div class="stats-label">Calories Today</div>
                    <div class="small text-muted mt-2">
                        <?php if(($today_meals['total_calories'] ?? 0) > 0): ?>
                        <i class="fas fa-fire me-1"></i>Calories consumed
                        <?php else: ?>
                        <i class="fas fa-info-circle me-1"></i>No meals logged
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #84fab0, #8fd3f4); color: #38b000;">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="stats-value"><?= round($goal_progress) ?>%</div>
                    <div class="stats-label">Goal Progress</div>
                    <div class="small text-muted mt-2">
                        <?php if($goal_info && $goal_info['goal_type']): ?>
                        <i class="fas fa-<?= $goal_info['goal_type'] == 'lose' ? 'arrow-down' : 'arrow-up' ?> me-1"></i><?= ucfirst($goal_info['goal_type']) ?> weight
                        <?php else: ?>
                        <i class="fas fa-info-circle me-1"></i>Set a goal in profile
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #fbc2eb, #a6c1ee); color: #8338ec;">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stats-value"><?= $achievements_count ?></div>
                    <div class="stats-label">Achievements</div>
                    <div class="small text-muted mt-2">
                        <i class="fas fa-award me-1"></i>Badges earned
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Activity Chart -->
        <div class="chart-container mb-4">
            <div class="chart-title">
                <i class="fas fa-chart-bar me-2 text-primary"></i> Weekly Workout Activity
            </div>
            <canvas id="weeklyChart" height="100"></canvas>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-lg-8 mb-4">
                <h4 class="mb-3">Quick Actions</h4>
                <div class="quick-actions">
                    <a href="/fitness-tracker/workouts/log.php" class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, #ff9a9e, #fad0c4); color: #ff6b6b;">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="action-title">Log Workout</div>
                        <div class="action-desc">Add today's workout session</div>
                    </a>
                    
                    <a href="/fitness-tracker/meals/planner.php" class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, #a1c4fd, #c2e9fb); color: #4d96ff;">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="action-title">Plan Meals</div>
                        <div class="action-desc">Create your meal plan</div>
                    </a>
                    
                    <a href="/fitness-tracker/progress/charts.php" class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, #84fab0, #8fd3f4); color: #38b000;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="action-title">View Progress</div>
                        <div class="action-desc">Track your fitness journey</div>
                    </a>
                    
                    <a href="/fitness-tracker/progress/photos.php" class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, #fbc2eb, #a6c1ee); color: #8338ec;">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="action-title">Progress Photos</div>
                        <div class="action-desc">Visual transformation</div>
                    </a>
                    
                    <a href="/fitness-tracker/profile.php" class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, #ffd1ff, #fad0c4); color: #ff6b6b;">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="action-title">Set Goals</div>
                        <div class="action-desc">Define your targets</div>
                    </a>
                </div>
            </div>
            
            <!-- Goal Progress -->
            <div class="col-lg-4 mb-4">
                <?php if($goal_info && $goal_info['goal_type'] && $goal_info['goal_weight'] && $goal_info['weight']): ?>
                <div class="goal-card">
                    <div class="goal-header">
                        <div class="goal-icon" style="background: var(--gradient-success); color: white;">
                            <i class="fas fa-<?= $goal_info['goal_type'] == 'lose' ? 'arrow-down' : 'arrow-up' ?>"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Goal Progress</h5>
                            <p class="text-muted mb-0"><?= ucfirst($goal_info['goal_type']) ?> Weight</p>
                        </div>
                    </div>
                    
                    <div class="progress-title">
                        <span>Progress</span>
                        <span class="fw-bold text-success"><?= round($goal_progress) ?>%</span>
                    </div>
                    
                    <div class="progress-bar-custom">
                        <div class="progress-fill" id="goalProgressFill" style="width: <?= $goal_progress ?>%"></div>
                    </div>
                    
                    <div class="goal-stats">
                        <div class="goal-stat">
                            <div class="goal-value"><?= $goal_info['weight'] ?> kg</div>
                            <div class="goal-label">Current</div>
                        </div>
                        <div class="goal-stat">
                            <div class="goal-value"><?= $goal_info['goal_weight'] ?> kg</div>
                            <div class="goal-label">Target</div>
                        </div>
                        <div class="goal-stat">
                            <div class="goal-value"><?= abs($goal_info['weight'] - $goal_info['goal_weight']) ?> kg</div>
                            <div class="goal-label">Remaining</div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="/fitness-tracker/profile.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>Update Progress
                        </a>
                    </div>
                </div>
                <?php elseif($goal_info && $goal_info['goal_type']): ?>
                <div class="goal-card">
                    <div class="goal-header">
                        <div class="goal-icon" style="background: var(--gradient-primary); color: white;">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Your Goal</h5>
                            <p class="text-muted mb-0">Set your current weight</p>
                        </div>
                    </div>
                    
                    <p class="mb-3">
                        You have set a goal to <span class="fw-bold"><?= ucfirst($goal_info['goal_type']) ?></span> weight
                        <?php if($goal_info['goal_weight']): ?>
                        to <span class="fw-bold"><?= $goal_info['goal_weight'] ?> kg</span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if(!$goal_info['weight']): ?>
                    <a href="/fitness-tracker/profile.php" class="btn btn-primary w-100">
                        <i class="fas fa-weight me-1"></i>Set Current Weight
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="goal-card">
                    <div class="goal-header">
                        <div class="goal-icon" style="background: var(--gradient-secondary); color: white;">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Set Your First Goal</h5>
                            <p class="text-muted mb-0">Start your fitness journey</p>
                        </div>
                    </div>
                    
                    <p class="mb-3">Setting goals helps you stay motivated and track progress. Define what you want to achieve!</p>
                    
                    <a href="/fitness-tracker/profile.php" class="btn btn-primary w-100">
                        <i class="fas fa-bullseye me-1"></i>Set Fitness Goals
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Daily Motivation -->
                <div class="goal-card mt-3">
                    <div class="goal-header">
                        <div class="goal-icon" style="background: linear-gradient(135deg, #f6d365, #fda085); color: white;">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Daily Motivation</h5>
                            <p class="text-muted mb-0">Stay inspired</p>
                        </div>
                    </div>
                    <p class="fst-italic text-center mt-3">
                        "The only bad workout is the one that didn't happen."
                    </p>
                    <p class="text-center text-muted small">- Fitness Proverb</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <div class="mobile-nav">
        <a href="dashboard.php" class="mobile-nav-item active">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="/fitness-tracker/workouts/" class="mobile-nav-item">
            <i class="fas fa-dumbbell"></i>
            <span>Workouts</span>
        </a>
        <a href="/fitness-tracker/meals/" class="mobile-nav-item">
            <i class="fas fa-utensils"></i>
            <span>Meals</span>
        </a>
        <a href="/fitness-tracker/progress/" class="mobile-nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Progress</span>
        </a>
        <a href="/fitness-tracker/profile.php" class="mobile-nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p class="mb-0">© 2023 FitTrack Pro. All rights reserved. | <a href="#">Contact Support</a></p>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize Weekly Activity Chart
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyChart = new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($weekly_data, 'day')) ?>,
                datasets: [{
                    label: 'Workout Minutes',
                    data: <?= json_encode(array_column($weekly_data, 'minutes')) ?>,
                    backgroundColor: [
                        'rgba(58, 134, 255, 0.7)',
                        'rgba(58, 134, 255, 0.7)',
                        'rgba(58, 134, 255, 0.7)',
                        'rgba(58, 134, 255, 0.7)',
                        'rgba(58, 134, 255, 0.7)',
                        'rgba(255, 0, 110, 0.7)',
                        'rgba(131, 56, 236, 0.7)'
                    ],
                    borderColor: [
                        'rgba(58, 134, 255, 1)',
                        'rgba(58, 134, 255, 1)',
                        'rgba(58, 134, 255, 1)',
                        'rgba(58, 134, 255, 1)',
                        'rgba(58, 134, 255, 1)',
                        'rgba(255, 0, 110, 1)',
                        'rgba(131, 56, 236, 1)'
                    ],
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Minutes'
                        },
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Animate goal progress bar on page load
        document.addEventListener('DOMContentLoaded', function() {
            const goalProgressFill = document.getElementById('goalProgressFill');
            if (goalProgressFill) {
                // Reset width to 0
                goalProgressFill.style.width = '0%';
                
                // Animate to actual width after a short delay
                setTimeout(() => {
                    const targetWidth = goalProgressFill.getAttribute('style').match(/width: (\d+)%/)[1];
                    goalProgressFill.style.width = targetWidth + '%';
                }, 300);
            }
            
            // Update Pakistan time immediately and set interval
            updatePakistanTime();
            setInterval(updatePakistanTime, 60000); // Update every minute
        });
        
        // Function to update Pakistan time using browser's timezone
        function updatePakistanTime() {
            const now = new Date();
            
            // Get current time in UTC
            const utcTime = now.getTime() + (now.getTimezoneOffset() * 60000);
            
            // Pakistan is UTC+5
            const pakistanOffset = 5 * 60; // 5 hours in minutes
            const pakistanTime = new Date(utcTime + (pakistanOffset * 60000));
            
            // Format date
            const optionsDate = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateStr = pakistanTime.toLocaleDateString('en-US', optionsDate);
            
            // Format time
            let hours = pakistanTime.getHours();
            const minutes = pakistanTime.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            const timeStr = hours + ':' + minutes + ' ' + ampm;
            
            // Update display
            document.getElementById('current-date').textContent = dateStr;
            document.getElementById('current-time').textContent = timeStr;
            
            // Update greeting based on Pakistan time
            const hour = pakistanTime.getHours();
            let greeting = "Good night";
            
            if (hour >= 5 && hour < 12) {
                greeting = "Good morning";
            } else if (hour >= 12 && hour < 17) {
                greeting = "Good afternoon";
            } else if (hour >= 17 && hour < 20) {
                greeting = "Good evening";
            } else {
                greeting = "Good night";
            }
            
            // Update greeting in header
            const greetingElement = document.getElementById('dynamic-greeting');
            if (greetingElement) {
                // Keep the user name from the original greeting
                const userName = "<?= htmlspecialchars($_SESSION['user_name']) ?>";
                greetingElement.textContent = greeting + ', ' + userName + '! 👋';
            }
        }
    </script>
</body>
</html>