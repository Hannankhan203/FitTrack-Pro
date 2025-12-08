<?php
// Start session at the very beginning
session_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/functions.php';
require_login();

require_once 'includes/db.php';
$user_id = get_user_id();

// Check if user is logged in and session has user_name
if (!isset($_SESSION['user_name'])) {
    header('Location: login.php');
    exit();
}

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

// Initialize variables
$today_workout = ['count' => 0, 'total_duration' => 0];
$today_exercises = [];
$today_meals_display = [];
$today_meals = ['total_calories' => 0];
$goal_info = null;
$streak = 0;
$achievements_count = 0;
$weekly_data = [];

// DEBUG: Log today's date and timezone
error_log("==========================================");
error_log("Dashboard loaded - User ID: " . $user_id);
error_log("Today's date (Pakistan): " . $today);
error_log("Current time (Pakistan): " . $current_time);
error_log("==========================================");

// ==================== SIMPLIFIED EXERCISES FETCH ====================
try {
    // Get ALL exercises for this user to see what we have
    $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? ORDER BY date DESC LIMIT 20");
    $stmt->execute([$user_id]);
    $all_exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Total exercises found for user: " . count($all_exercises));
} catch (PDOException $e) {
    error_log("Debug query error: " . $e->getMessage());
}

// Now get today's exercises - SIMPLIFIED APPROACH
try {
    // Try direct comparison first
    $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? AND DATE(date) = ? ORDER BY date DESC LIMIT 10");
    $stmt->execute([$user_id, $today]);
    $today_exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Method 1 (DATE()) found: " . count($today_exercises) . " exercises");

    // If no exercises found, try to see if there are any recent ones
    if (empty($today_exercises)) {
        error_log("No exercises found for today. Checking last 2 days...");
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? AND (DATE(date) = ? OR DATE(date) = ?) ORDER BY date DESC LIMIT 10");
        $stmt->execute([$user_id, $today, $yesterday]);
        $recent_exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Recent exercises (today+yesterday): " . count($recent_exercises));

        // For debugging, let's use recent exercises if none found for today
        if (count($recent_exercises) > 0 && count($today_exercises) === 0) {
            $today_exercises = $recent_exercises;
            error_log("Using recent exercises for display");
        }
    }
} catch (PDOException $e) {
    error_log("Database error (today exercises): " . $e->getMessage());
    $today_exercises = [];
}

// ==================== TODAY'S WORKOUT STATS ====================
try {
    $today_workout_count = count($today_exercises);
    $today_total_duration = 0;

    foreach ($today_exercises as $workout) {
        if (!empty($workout['duration']) && $workout['duration'] > 0) {
            // Check if it's a duration exercise (like plank)
            $exercise_name = strtolower($workout['exercise'] ?? '');
            $is_duration_exercise = false;
            $duration_exercises = [
                'plank',
                'forearm plank',
                'side plank',
                'hollow body hold',
                'dead bug',
                'bird-dog',
                'bear crawls',
                'rc plank'
            ];

            foreach ($duration_exercises as $dur_ex) {
                if (strpos($exercise_name, $dur_ex) !== false) {
                    $is_duration_exercise = true;
                    break;
                }
            }

            // Also check if it's stored as duration exercise (sets and reps = 0)
            $is_stored_as_duration = (isset($workout['sets']) && $workout['sets'] == 0) &&
                (isset($workout['reps']) && $workout['reps'] == 0);

            $is_duration_exercise = $is_duration_exercise || $is_stored_as_duration;

            if ($is_duration_exercise) {
                // Convert seconds to minutes for total duration calculation
                $today_total_duration += ($workout['duration'] / 60);
            } else {
                // Regular exercise, duration is already in minutes
                $today_total_duration += $workout['duration'];
            }
        } else {
            // Estimate duration
            $today_total_duration += 10; // Default 10 minutes per exercise
        }
    }

    $today_workout = [
        'count' => $today_workout_count,
        'total_duration' => round($today_total_duration, 1)
    ];
} catch (Exception $e) {
    error_log("Error calculating workout stats: " . $e->getMessage());
}

// ==================== TODAY'S MEALS ====================
try {
    $stmt = $pdo->prepare("SELECT meal_time, food_name, calories, protein, carbs, fat, date FROM meals WHERE user_id = ? AND DATE(date) = ? ORDER BY 
        CASE meal_time 
            WHEN 'breakfast' THEN 1
            WHEN 'lunch' THEN 2
            WHEN 'dinner' THEN 3
            WHEN 'snack' THEN 4
            ELSE 5
        END, id DESC LIMIT 5");
    $stmt->execute([$user_id, $today]);
    $today_meals_display = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error (today meals): " . $e->getMessage());
}

// ==================== MEAL STATS ====================
try {
    $stmt = $pdo->prepare("SELECT SUM(calories) as total_calories FROM meals WHERE user_id = ? AND DATE(date) = ?");
    $stmt->execute([$user_id, $today]);
    $today_meals = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$today_meals || $today_meals['total_calories'] === null) {
        $today_meals = ['total_calories' => 0];
    }
} catch (PDOException $e) {
    error_log("Database error (meal stats): " . $e->getMessage());
}

// ==================== GOAL INFO - COMPLETELY FIXED ====================
try {
    // Get user's goal info
    $stmt = $pdo->prepare("SELECT goal_weight, goal_type, weight FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $goal_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Initialize variables
    $goal_progress = 0;
    $starting_weight = 0;
    $current_weight = 0;

    if ($goal_info && isset($goal_info['goal_weight']) && $goal_info['goal_weight'] > 0) {

        // 1. Get STARTING weight: Try to get FIRST weight from progress table
        try {
            $stmt = $pdo->prepare("SELECT weight FROM progress WHERE user_id = ? ORDER BY created_at ASC, date ASC LIMIT 1");
            $stmt->execute([$user_id]);
            $firstWeight = $stmt->fetch();
            if ($firstWeight && $firstWeight['weight'] > 0) {
                $starting_weight = $firstWeight['weight'];
            } else {
                // If no progress entries, use weight from users table as starting weight
                $starting_weight = $goal_info['weight'] ?? 0;
            }
        } catch (PDOException $e) {
            error_log("Error fetching first weight: " . $e->getMessage());
            $starting_weight = $goal_info['weight'] ?? 0;
        }

        // 2. Get CURRENT weight: Try to get LATEST weight from progress table
        try {
            $stmt = $pdo->prepare("SELECT weight FROM progress WHERE user_id = ? ORDER BY created_at DESC, date DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $latestWeight = $stmt->fetch();
            if ($latestWeight && $latestWeight['weight'] > 0) {
                $current_weight = $latestWeight['weight'];
            } else {
                // If no progress entries, use starting weight as current weight
                $current_weight = $starting_weight;
            }
        } catch (PDOException $e) {
            error_log("Error fetching latest weight: " . $e->getMessage());
            $current_weight = $starting_weight;
        }

        // 3. Calculate progress
        $goal_weight = $goal_info['goal_weight'];
        $goal_type = $goal_info['goal_type'] ?? 'maintain';

        // Debug output - uncomment to see what's happening
        /*
        echo "<div style='background:#222;color:white;padding:10px;margin:10px;border:2px solid red;'>";
        echo "DEBUG - Goal Progress Calculation:<br>";
        echo "Starting Weight: " . $starting_weight . " kg<br>";
        echo "Current Weight: " . $current_weight . " kg<br>";
        echo "Goal Weight: " . $goal_weight . " kg<br>";
        echo "Goal Type: " . $goal_type . "<br>";
        */

        if ($goal_type == 'lose') {
            if ($starting_weight > $goal_weight && $starting_weight > 0) {
                $total_to_lose = $starting_weight - $goal_weight;
                $current_lost = $starting_weight - $current_weight;

                // Debug
                // echo "Total to lose: " . $total_to_lose . " kg<br>";
                // echo "Current lost: " . $current_lost . " kg<br>";

                if ($total_to_lose > 0) {
                    $goal_progress = ($current_lost / $total_to_lose) * 100;
                    $goal_progress = min(100, max(0, $goal_progress));
                }
            }
        } elseif ($goal_type == 'gain') {
            if ($starting_weight < $goal_weight && $starting_weight > 0) {
                $total_to_gain = $goal_weight - $starting_weight;
                $current_gain = $current_weight - $starting_weight;

                if ($total_to_gain > 0) {
                    $goal_progress = ($current_gain / $total_to_gain) * 100;
                    $goal_progress = min(100, max(0, $goal_progress));
                }
            }
        } else {
            // Maintenance goal
            $goal_progress = 100;
        }

        // Debug
        // echo "Progress: " . round($goal_progress) . "%<br>";
        // echo "</div>";

        // Round for display
        $goal_progress = round($goal_progress);

        // Add weights to goal_info for display
        $goal_info['starting_weight'] = $starting_weight;
        $goal_info['current_weight'] = $current_weight;
    }
} catch (PDOException $e) {
    error_log("Database error (goal info): " . $e->getMessage());
    $goal_info = false;
}

// ==================== STREAK CALCULATION ====================
try {
    // Simple streak calculation
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(date)) as streak FROM workouts WHERE user_id = ? AND DATE(date) >= DATE_SUB(?, INTERVAL 7 DAY)");
    $stmt->execute([$user_id, $today]);
    $streak_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $streak = $streak_data['streak'] ?? 0;
} catch (PDOException $e) {
    error_log("Database error (streak): " . $e->getMessage());
}

// ==================== ACHIEVEMENTS ====================
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as badge_count FROM achievements WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $achievements_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $achievements_count = $achievements_data['badge_count'] ?? 0;
} catch (PDOException $e) {
    error_log("Database error (achievements): " . $e->getMessage());
}

// ==================== WEEKLY DATA FOR CHART ====================
try {
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));

        // Get all workouts for this day to calculate total duration properly
        $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? AND DATE(date) = ?");
        $stmt->execute([$user_id, $day]);
        $day_workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_minutes = 0;

        if (!empty($day_workouts)) {
            foreach ($day_workouts as $workout) {
                if (!empty($workout['duration']) && $workout['duration'] > 0) {
                    // Check if it's a duration exercise
                    $exercise_name = strtolower($workout['exercise'] ?? '');
                    $is_duration_exercise = false;
                    $duration_exercises = [
                        'plank',
                        'forearm plank',
                        'side plank',
                        'hollow body hold',
                        'dead bug',
                        'bird-dog',
                        'bear crawls',
                        'rc plank'
                    ];

                    foreach ($duration_exercises as $dur_ex) {
                        if (strpos($exercise_name, $dur_ex) !== false) {
                            $is_duration_exercise = true;
                            break;
                        }
                    }

                    // Also check if it's stored as duration exercise
                    $is_stored_as_duration = (isset($workout['sets']) && $workout['sets'] == 0) &&
                        (isset($workout['reps']) && $workout['reps'] == 0);

                    $is_duration_exercise = $is_duration_exercise || $is_stored_as_duration;

                    if ($is_duration_exercise) {
                        // Convert seconds to minutes
                        $total_minutes += ($workout['duration'] / 60);
                    } else {
                        // Regular exercise, duration is already in minutes
                        $total_minutes += $workout['duration'];
                    }
                } else {
                    // Estimate duration
                    $total_minutes += 10; // Default 10 minutes per exercise
                }
            }
        }

        $weekly_data[] = [
            'day' => date('D', strtotime($day)),
            'minutes' => round($total_minutes, 1)
        ];
    }
} catch (PDOException $e) {
    error_log("Database error (weekly data): " . $e->getMessage());
}

// Function to format datetime
function formatPakistanTime($datetime)
{
    if (empty($datetime)) return '';

    try {
        $date = new DateTime($datetime);
        return $date->format('g:i A');
    } catch (Exception $e) {
        return '';
    }
}
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
            font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--darker);
            color: var(--light);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            padding-top: 80px;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 10% 20%,
                    rgba(0, 212, 255, 0.15) 0%,
                    transparent 40%),
                radial-gradient(circle at 90% 80%,
                    rgba(255, 45, 117, 0.15) 0%,
                    transparent 40%),
                radial-gradient(circle at 50% 50%,
                    rgba(157, 78, 221, 0.1) 0%,
                    transparent 60%);
            z-index: -2;
        }

        /* ==================== FIXED NAVIGATION ==================== */
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

        /* ==================== DASHBOARD HEADER ==================== */
        .dashboard-header {
            background: linear-gradient(135deg,
                    rgba(0, 212, 255, 0.1),
                    rgba(157, 78, 221, 0.1));
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg,
                    #ffffff 0%,
                    var(--primary) 50%,
                    var(--accent) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .dashboard-header .greeting {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .date-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .date-display i {
            color: var(--primary);
        }

        .streak-badge {
            background: var(--gradient-secondary);
            color: white;
            padding: 0.8rem 1.8rem;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 5px 20px rgba(255, 45, 117, 0.3);
            margin-left: 2rem;
        }

        /* Exercises Card */
        .exercises-card {
            background: linear-gradient(145deg,
                    rgba(0, 212, 255, 0.05),
                    rgba(0, 212, 255, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.1);
        }

        .exercises-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .exercises-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .exercises-title h4 {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            margin: 0;
        }

        .exercises-count {
            background: var(--gradient-primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .exercises-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .exercise-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 1.8rem;
            border-left: 5px solid var(--primary);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .exercise-item:hover {
            transform: translateY(-5px);
            background: rgba(0, 212, 255, 0.08);
            border-left-color: var(--secondary);
            box-shadow: 0 15px 40px rgba(0, 212, 255, 0.15);
        }

        .exercise-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .exercise-name {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 0.8rem;
            color: white;
            letter-spacing: -0.3px;
        }

        .exercise-category {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .exercise-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 1.2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 0.8rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .stat-icon.calorie-stat {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
        }

        .stat-icon.interval-stat {
            background: rgba(157, 78, 221, 0.1);
            color: #9d4edd;
        }

        .stat-icon.info-stat {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .stat-icon.strength-stat {
            background: rgba(0, 212, 255, 0.1);
            color: var(--primary);
        }

        .stat-icon.weight-stat {
            background: rgba(0, 230, 118, 0.1);
            color: var(--success);
        }

        .stat-icon.cardio-stat {
            background: rgba(255, 45, 117, 0.1);
            color: var(--secondary);
        }

        .stat-icon.time-stat {
            background: rgba(157, 78, 221, 0.1);
            color: var(--accent);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 900;
            margin-bottom: 0.25rem;
            color: white;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .view-all-exercises {
            text-align: center;
            margin-top: 2rem;
        }

        .empty-exercises {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-exercises-icon {
            font-size: 5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-exercises h4 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: white;
            letter-spacing: -0.5px;
        }

        .empty-exercises p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            font-size: 1.1rem;
        }

        /* Meals Card */
        .meals-card {
            background: linear-gradient(145deg,
                    rgba(255, 45, 117, 0.05),
                    rgba(255, 45, 117, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 45, 117, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(255, 45, 117, 0.1);
        }

        .meals-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .meals-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .meals-title h4 {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            margin: 0;
        }

        .meals-count {
            background: var(--gradient-secondary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .meals-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .meal-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 1.8rem;
            border-left: 5px solid var(--secondary);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .meal-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 45, 117, 0.08);
            border-left-color: var(--accent);
            box-shadow: 0 15px 40px rgba(255, 45, 117, 0.15);
        }

        .meal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .meal-name {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 0.8rem;
            color: white;
            letter-spacing: -0.3px;
        }

        .meal-type {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meal-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .meal-stat-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 1.2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .meal-stat-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
        }

        .meal-stat-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 0.8rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .meal-stat-icon.calorie-stat {
            background: rgba(255, 45, 117, 0.1);
            color: var(--secondary);
        }

        .meal-stat-icon.protein-stat {
            background: rgba(0, 212, 255, 0.1);
            color: var(--primary);
        }

        .meal-stat-icon.carbs-stat {
            background: rgba(0, 230, 118, 0.1);
            color: var(--success);
        }

        .meal-stat-icon.fat-stat {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .meal-stat-value {
            font-size: 1.8rem;
            font-weight: 900;
            margin-bottom: 0.25rem;
            color: white;
        }

        .meal-stat-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .view-all-meals {
            text-align: center;
            margin-top: 2rem;
        }

        .empty-meals {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-meals-icon {
            font-size: 5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-meals h4 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: white;
            letter-spacing: -0.5px;
        }

        .empty-meals p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            font-size: 1.1rem;
        }

        /* Stats Cards */
        .stats-card {
            background: linear-gradient(145deg,
                    rgba(255, 255, 255, 0.03),
                    rgba(255, 255, 255, 0.01));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.2);
        }

        .stats-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1.5rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
        }

        .stats-card:hover .stats-icon {
            background: var(--gradient);
            color: white;
            transform: scale(1.1);
        }

        .stats-value {
            font-size: 2.8rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            line-height: 1;
            color: white;
        }

        .stats-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        /* Chart Container */
        .chart-container {
            background: linear-gradient(145deg,
                    rgba(157, 78, 221, 0.05),
                    rgba(157, 78, 221, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(157, 78, 221, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(157, 78, 221, 0.1);
        }

        .chart-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
            margin-bottom: 2rem;
        }

        .chart-title i {
            color: var(--accent);
            font-size: 1.5rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .action-card {
            background: linear-gradient(145deg,
                    rgba(0, 230, 118, 0.05),
                    rgba(0, 230, 118, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 230, 118, 0.15);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .action-card:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 20px 40px rgba(0, 212, 255, 0.2);
        }

        .action-icon {
            width: 70px;
            height: 70px;
            margin-bottom: 1.5rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .action-card:hover .action-icon {
            background: var(--gradient);
            color: white;
            transform: scale(1.1);
        }

        .action-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            color: white;
        }

        .action-desc {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Goal Cards */
        .goal-card {
            background: linear-gradient(145deg,
                    rgba(255, 193, 7, 0.05),
                    rgba(255, 193, 7, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 193, 7, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(255, 193, 7, 0.1);
        }

        .goal-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .goal-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 230, 118, 0.3);
        }

        .goal-header h5 {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: white;
            letter-spacing: -0.5px;
        }

        .progress-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }

        .progress-title span {
            font-weight: 700;
            color: white;
            font-size: 1.1rem;
        }

        .progress-title .fw-bold.text-success {
            font-weight: 900;
            font-size: 1.8rem;
            background: var(--gradient-success);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .progress-bar-custom {
            height: 14px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 7px;
            overflow: hidden;
            margin-bottom: 2rem;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient-success);
            border-radius: 7px;
            width: 0;
            transition: width 1.5s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        .progress-fill::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.2),
                    transparent);
            animation: shimmer 2s infinite;
        }

        .goal-stats {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .goal-stat {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .goal-stat:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-3px);
        }

        .goal-value {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            color: white;
        }

        .goal-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
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

        /* Footer */
        .footer {
            text-align: center;
            padding: 3rem 0;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 3rem;
        }

        /* Buttons */
        .btn {
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
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

        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }

        .btn-danger {
            background: var(--gradient-secondary);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 45, 117, 0.3);
        }

        .btn-outline-danger {
            background: transparent;
            border: 2px solid var(--secondary);
            color: var(--secondary);
        }

        .btn-outline-danger:hover {
            background: var(--secondary);
            color: white;
        }

        /* Animations */
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        .small {
            color: #ffffff !important;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                position: fixed;
                top: 80px;
                left: 0;
                right: 0;
                bottom: 0;
                backdrop-filter: blur(30px);
                border-radius: 0;
                padding: 2rem;
                margin: 0;
                opacity: 0;
                visibility: hidden;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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
            }

            .navbar-collapse.show .navbar-close {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
                margin-top: 1rem;
            }

            .exercises-card,
            .meals-card,
            .chart-container,
            .goal-card {
                padding: 1.5rem;
                border-radius: 24px;
                margin-bottom: 1.5rem;
            }

            .exercises-header,
            .meals-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
            }

            .exercises-title h4,
            .meals-title h4 {
                font-size: 1.4rem;
            }

            .exercises-count,
            .meals-count {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .exercise-stats,
            .meal-stats,
            .goal-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stats-value {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .navbar {
                height: 70px;
            }

            .navbar-brand.logo {
                font-size: 1.3rem;
            }

            .dashboard-header h1 {
                font-size: 1.8rem;
            }

            .exercises-title h4,
            .meals-title h4 {
                font-size: 1.2rem;
            }

            .exercise-stats,
            .meal-stats,
            .goal-stats {
                grid-template-columns: 1fr;
            }

            .stats-value {
                font-size: 1.8rem;
            }

            .streak-badge {
                padding: 0.4rem 1rem;
                border-radius: 15px;
                gap: 0px;
                font-weight: 700;
                font-size: 0.7rem;
                box-shadow: 0 5px 20px rgba(255, 45, 117, 0.3);
                margin-left: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand logo" href="dashboard.php">FitTrack Pro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <button class="navbar-close" id="navbarClose" type="button">
                    <i class="fas fa-times"></i>
                </button>
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
                    <h1 id="dynamic-greeting"><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($_SESSION['user_name']) ?>! 👋</h1>
                    <p class="greeting">Keep pushing towards your fitness goals. You're doing great!</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="date-display">
                            <i class="fas fa-calendar-alt"></i>
                            <span id="current-date"><?= htmlspecialchars($display_date) ?></span>
                            <i class="fas fa-clock ms-3"></i>
                            <span id="current-time"><?= htmlspecialchars($display_time) ?></span>
                            <span class="ms-2 small">PKT (Islamabad)</span>
                        </div>
                        <?php if ($streak > 0): ?>
                            <div class="streak-badge">
                                <i class="fas fa-fire me-2"></i><?= htmlspecialchars($streak) ?> Day Streak
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
                <?php if (!empty($today_exercises)): ?>
                    <a href="/fitness-tracker/workouts/log.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus me-1"></i>Add More
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($today_exercises)): ?>
                <div class="exercises-list">
                    <?php foreach ($today_exercises as $exercise): ?>
                        <?php
                        // Get exercise name safely
                        $exercise_name = htmlspecialchars($exercise['exercise'] ?? 'Unnamed Exercise');
                        $exercise_name_lower = strtolower($exercise_name);

                        // Determine if it's cardio or strength
                        $is_cardio = false;
                        $cardio_keywords = ['run', 'jog', 'cycle', 'swim', 'walk', 'row', 'elliptical', 'stair', 'jump', 'burpee', 'sprint', 'bike'];

                        foreach ($cardio_keywords as $keyword) {
                            if (strpos($exercise_name_lower, $keyword) !== false) {
                                $is_cardio = true;
                                break;
                            }
                        }

                        $category = $is_cardio ? 'Cardio' : 'Strength';
                        $category_color = $is_cardio ? '#ff006e' : '#3a86ff';
                        $category_bg = $is_cardio ? 'rgba(255, 0, 110, 0.1)' : 'rgba(58, 134, 255, 0.1)';

                        // Check if it's a duration exercise (like plank)
                        $is_duration_exercise = false;
                        $duration_exercises = [
                            'plank',
                            'forearm plank',
                            'side plank',
                            'hollow body hold',
                            'dead bug',
                            'bird-dog',
                            'bear crawls',
                            'rc plank'
                        ];

                        foreach ($duration_exercises as $dur_ex) {
                            if (strpos($exercise_name_lower, $dur_ex) !== false) {
                                $is_duration_exercise = true;
                                break;
                            }
                        }

                        // Also check if it's stored as duration exercise (sets and reps = 0)
                        $is_stored_as_duration = (isset($exercise['sets']) && $exercise['sets'] == 0) &&
                            (isset($exercise['reps']) && $exercise['reps'] == 0);

                        $is_duration_exercise = $is_duration_exercise || $is_stored_as_duration;
                        ?>

                        <div class="exercise-item">
                            <div class="exercise-header">
                                <div>
                                    <div class="exercise-name"><?= $exercise_name ?></div>
                                    <span class="exercise-category" style="background: <?= $category_bg ?>; color: <?= $category_color ?>;">
                                        <?= $category ?>
                                        <?php if ($is_duration_exercise): ?>
                                            <span class="badge bg-warning ms-1">Duration</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="exercise-stats">
                                <?php if (!$is_cardio): ?>
                                    <!-- Strength Stats -->
                                    <?php if ($is_duration_exercise): ?>
                                        <!-- Duration exercise display (like plank) -->
                                        <?php if (!empty($exercise['duration']) && $exercise['duration'] > 0): ?>
                                            <div class="stat-item">
                                                <div class="stat-icon strength-stat">
                                                    <i class="fas fa-stopwatch"></i>
                                                </div>
                                                <div class="stat-value"><?= htmlspecialchars($exercise['duration']) ?> sec</div>
                                                <div class="stat-label">Duration</div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- For duration exercises, we don't show sets/reps -->
                                    <?php else: ?>
                                        <!-- Regular strength exercise -->
                                        <?php if (!empty($exercise['sets']) && $exercise['sets'] > 0): ?>
                                            <div class="stat-item">
                                                <div class="stat-icon strength-stat">
                                                    <i class="fas fa-redo"></i>
                                                </div>
                                                <div class="stat-value"><?= htmlspecialchars($exercise['sets']) ?></div>
                                                <div class="stat-label">Sets</div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($exercise['reps']) && $exercise['reps'] > 0): ?>
                                            <div class="stat-item">
                                                <div class="stat-icon strength-stat">
                                                    <i class="fas fa-sync-alt"></i>
                                                </div>
                                                <div class="stat-value"><?= htmlspecialchars($exercise['reps']) ?></div>
                                                <div class="stat-label">Reps</div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($exercise['weight']) && $exercise['weight'] > 0): ?>
                                            <div class="stat-item">
                                                <div class="stat-icon weight-stat">
                                                    <i class="fas fa-weight-hanging"></i>
                                                </div>
                                                <div class="stat-value"><?= htmlspecialchars($exercise['weight']) ?> kg</div>
                                                <div class="stat-label">Weight</div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($exercise['duration']) && $exercise['duration'] > 0 && !$is_duration_exercise): ?>
                                            <div class="stat-item">
                                                <div class="stat-icon time-stat">
                                                    <i class="fas fa-stopwatch"></i>
                                                </div>
                                                <div class="stat-value"><?= htmlspecialchars($exercise['duration']) ?> min</div>
                                                <div class="stat-label">Duration</div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Cardio Stats -->
                                    <?php if (!empty($exercise['duration']) && $exercise['duration'] > 0): ?>
                                        <div class="stat-item">
                                            <div class="stat-icon cardio-stat">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <div class="stat-value"><?= htmlspecialchars($exercise['duration']) ?> min</div>
                                            <div class="stat-label">Duration</div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($exercise['distance']) && $exercise['distance'] > 0): ?>
                                        <div class="stat-item">
                                            <div class="stat-icon cardio-stat">
                                                <i class="fas fa-route"></i>
                                            </div>
                                            <div class="stat-value"><?= htmlspecialchars($exercise['distance']) ?> km</div>
                                            <div class="stat-label">Distance</div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($exercise['calories']) && $exercise['calories'] > 0): ?>
                                        <div class="stat-item">
                                            <div class="stat-icon cardio-stat">
                                                <i class="fas fa-fire"></i>
                                            </div>
                                            <div class="stat-value"><?= htmlspecialchars($exercise['calories']) ?></div>
                                            <div class="stat-label">Calories</div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($exercise['sets']) && $exercise['sets'] > 0): ?>
                                        <div class="stat-item">
                                            <div class="stat-icon cardio-stat">
                                                <i class="fas fa-redo"></i>
                                            </div>
                                            <div class="stat-value"><?= htmlspecialchars($exercise['sets']) ?></div>
                                            <div class="stat-label">Intervals</div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- If no stats available, show a message -->
                                <?php
                                $has_stats = false;
                                $stat_fields = ['sets', 'reps', 'weight', 'duration', 'distance', 'calories'];
                                foreach ($stat_fields as $field) {
                                    if (!empty($exercise[$field]) && $exercise[$field] > 0) {
                                        $has_stats = true;
                                        break;
                                    }
                                }

                                if (!$has_stats): ?>
                                    <div class="stat-item">
                                        <div class="stat-icon info-stat">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div class="stat-value">--</div>
                                        <div class="stat-label">No details</div>
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

        <!-- Today's Meals Card -->
        <div class="meals-card mb-4">
            <div class="meals-header">
                <div class="meals-title">
                    <i class="fas fa-utensils fa-lg" style="color: #ff006e;"></i>
                    <h4 class="mb-0">Today's Meals</h4>
                    <div class="meals-count"><?= count($today_meals_display) ?></div>
                </div>
                <?php if (!empty($today_meals_display)): ?>
                    <a href="/fitness-tracker/meals/planner.php" class="btn btn-sm btn-outline-danger" style="border-color: #ff006e; color: #ff006e;">
                        <i class="fas fa-plus me-1"></i>Add More
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($today_meals_display)): ?>
                <div class="meals-list">
                    <?php foreach ($today_meals_display as $meal): ?>
                        <?php
                        $meal_type = htmlspecialchars($meal['meal_time']);
                        $meal_name = htmlspecialchars($meal['food_name']);
                        $meal_type_color = '#ff006e';
                        $meal_type_bg = 'rgba(255, 0, 110, 0.1)';

                        switch ($meal_type) {
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
                            </div>

                            <div class="meal-stats">
                                <div class="meal-stat-item">
                                    <div class="meal-stat-icon calorie-stat">
                                        <i class="fas fa-fire"></i>
                                    </div>
                                    <div class="meal-stat-value"><?= htmlspecialchars($meal['calories']) ?></div>
                                    <div class="meal-stat-label">Calories</div>
                                </div>

                                <?php if (!empty($meal['protein']) && $meal['protein'] > 0): ?>
                                    <div class="meal-stat-item">
                                        <div class="meal-stat-icon protein-stat">
                                            <i class="fas fa-drumstick-bite"></i>
                                        </div>
                                        <div class="meal-stat-value"><?= htmlspecialchars($meal['protein']) ?>g</div>
                                        <div class="meal-stat-label">Protein</div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($meal['carbs']) && $meal['carbs'] > 0): ?>
                                    <div class="meal-stat-item">
                                        <div class="meal-stat-icon carbs-stat">
                                            <i class="fas fa-bread-slice"></i>
                                        </div>
                                        <div class="meal-stat-value"><?= htmlspecialchars($meal['carbs']) ?>g</div>
                                        <div class="meal-stat-label">Carbs</div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($meal['fat']) && $meal['fat'] > 0): ?>
                                    <div class="meal-stat-item">
                                        <div class="meal-stat-icon fat-stat">
                                            <i class="fas fa-oil-can"></i>
                                        </div>
                                        <div class="meal-stat-value"><?= htmlspecialchars($meal['fat']) ?>g</div>
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
                    <div class="stats-value"><?= htmlspecialchars($today_workout['count']) ?></div>
                    <div class="stats-label">Today's Workouts</div>
                    <div class="small text-muted mt-2">
                        <i class="fas fa-clock me-1"></i><?= number_format($today_workout['total_duration'], 1) ?> min total
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #a1c4fd, #c2e9fb); color: #4d96ff;">
                        <i class="fas fa-apple-alt"></i>
                    </div>
                    <div class="stats-value"><?= htmlspecialchars($today_meals['total_calories']) ?></div>
                    <div class="stats-label">Calories Today</div>
                    <div class="small text-muted mt-2">
                        <?php if ($today_meals['total_calories'] > 0): ?>
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
                        <?php if ($goal_info && isset($goal_info['goal_type'])): ?>
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
                    <div class="stats-value"><?= htmlspecialchars($achievements_count) ?></div>
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
                <h4 class="mb-3 text-white">Quick Actions</h4>
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
                <?php if (
                    $goal_info && isset($goal_info['goal_type']) && $goal_info['goal_type'] &&
                    isset($goal_info['goal_weight']) && $goal_info['goal_weight'] &&
                    isset($goal_info['weight']) && $goal_info['weight']
                ): ?>
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
                            <span class="fw-bold text-success"><?= $goal_progress ?>%</span>
                        </div>

                        <div class="progress-bar-custom">
                            <div class="progress-fill" id="goalProgressFill" style="width: <?= $goal_progress ?>%"></div>
                        </div>

                        <div class="goal-stats">
                            <div class="goal-stat">
                                <div class="goal-value"><?= htmlspecialchars($goal_info['current_weight'] ?? $goal_info['weight'] ?? 0) ?> kg</div>
                                <div class="goal-label">Current</div>
                            </div>
                            <div class="goal-stat">
                                <div class="goal-value"><?= htmlspecialchars($goal_info['goal_weight'] ?? 0) ?> kg</div>
                                <div class="goal-label">Target</div>
                            </div>
                            <div class="goal-stat">
                                <div class="goal-value">
                                    <?php
                                    $remaining = 0;
                                    if (isset($goal_info['goal_type'])) {
                                        if ($goal_info['goal_type'] == 'lose') {
                                            $remaining = max(0, ($goal_info['current_weight'] ?? $goal_info['weight'] ?? 0) - $goal_info['goal_weight']);
                                        } elseif ($goal_info['goal_type'] == 'gain') {
                                            $remaining = max(0, $goal_info['goal_weight'] - ($goal_info['current_weight'] ?? $goal_info['weight'] ?? 0));
                                        }
                                    }
                                    echo htmlspecialchars($remaining);
                                    ?> kg
                                </div>
                                <div class="goal-label">Remaining</div>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <a href="/fitness-tracker/profile.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>Update Progress
                            </a>
                        </div>
                    </div>
                <?php elseif ($goal_info && isset($goal_info['goal_type']) && $goal_info['goal_type']): ?>
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
                            <?php if (isset($goal_info['goal_weight']) && $goal_info['goal_weight']): ?>
                                to <span class="fw-bold"><?= htmlspecialchars($goal_info['goal_weight']) ?> kg</span>
                            <?php endif; ?>
                        </p>

                        <?php if (!isset($goal_info['weight']) || !$goal_info['weight']): ?>
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

        // Simple navbar toggler icon animation
        document.addEventListener('DOMContentLoaded', function() {
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navbarClose = document.getElementById('navbarClose');

            // Add rotation animation to hamburger icon
            if (navbarToggler) {
                navbarToggler.addEventListener('click', function() {
                    const icon = this.querySelector('.navbar-toggler-icon');
                    if (icon) {
                        if (this.getAttribute('aria-expanded') === 'true') {
                            // Menu is opening
                            icon.style.transform = 'rotate(90deg)';
                            document.body.classList.add('menu-open');
                        } else {
                            // Menu is closing
                            icon.style.transform = 'rotate(0deg)';
                            document.body.classList.remove('menu-open');
                        }
                    }
                });
            }

            // Handle close button click
            if (navbarClose) {
                navbarClose.addEventListener('click', function() {
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    if (navbarCollapse.classList.contains('show')) {
                        // Trigger the hamburger button click to close
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
                            // Close the navbar
                            navbarToggler.click();
                        }
                    }
                });
            });

            // Animate goal progress bar on page load
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
            const optionsDate = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
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