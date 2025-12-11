<?php
// Start output buffering to catch any stray output
ob_start();

// ==================== PAKISTAN ISLAMABAD TIMEZONE ====================
// Set timezone to Pakistan (Islamabad) - UTC+5
date_default_timezone_set('Asia/Karachi');

require '../includes/functions.php';
require_login();

$today = date('Y-m-d');
$display_date = date('l, F j, Y');  // Format: Monday, January 15, 2024
$display_time = date('g:i A');      // Format: 2:30 PM

require '../includes/db.php';
$user_id = get_user_id();

// Handle saving custom food to user's personal database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_custom_food'])) {
    try {
        $food_name = $_POST['food_name'];
        $calories = $_POST['calories'];
        $protein = $_POST['protein'] ?? 0;
        $carbs = $_POST['carbs'] ?? 0;
        $fat = $_POST['fat'] ?? 0;
        $category = $_POST['category'] ?? 'custom';

        // Check if food already exists for this user
        $checkStmt = $pdo->prepare("SELECT id FROM custom_foods WHERE user_id = ? AND food_name = ?");
        $checkStmt->execute([$user_id, $food_name]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            throw new Exception('This food already exists in your personal database');
        }

        // Insert into custom_foods table
        $insertStmt = $pdo->prepare("INSERT INTO custom_foods (user_id, food_name, calories, protein, carbs, fat, category, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $insertStmt->execute([$user_id, $food_name, $calories, $protein, $carbs, $fat, $category]);

        // Clear any output
        ob_end_clean();

        // Send JSON response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Custom food saved to your personal database!', 'id' => $pdo->lastInsertId()]);
        exit;
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Error saving custom food: ' . $e->getMessage()]);
        exit;
    }
}

// Handle deleting custom food
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_custom_food'])) {
    try {
        $food_id = $_POST['food_id'];

        // Verify the food belongs to the current user
        $checkStmt = $pdo->prepare("SELECT id FROM custom_foods WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$food_id, $user_id]);
        $food = $checkStmt->fetch();

        if (!$food) {
            throw new Exception('Food not found or access denied');
        }

        // Delete the food
        $deleteStmt = $pdo->prepare("DELETE FROM custom_foods WHERE id = ? AND user_id = ?");
        $deleteStmt->execute([$food_id, $user_id]);

        // Clear any output
        ob_end_clean();

        // Send JSON response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Custom food deleted successfully!']);
        exit;
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Error deleting custom food: ' . $e->getMessage()]);
        exit;
    }
}

// Handle AJAX request for custom foods
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_custom_foods'])) {
    try {
        $customFoodsStmt = $pdo->prepare("SELECT * FROM custom_foods WHERE user_id = ? ORDER BY food_name");
        $customFoodsStmt->execute([$user_id]);
        $customFoods = $customFoodsStmt->fetchAll(PDO::FETCH_ASSOC);

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'foods' => $customFoods]);
        exit;
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Error loading custom foods: ' . $e->getMessage()]);
        exit;
    }
}

// Handle saving meal plan - MUST BE AT THE VERY TOP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_meal_plan'])) {
    try {
        // Get the meals data from POST
        $meals = json_decode($_POST['meals_data'], true);

        // Initialize count
        $successCount = 0;

        // Only insert if there are meals
        if (is_array($meals) && count($meals) > 0) {
            // Prepare insert statement
            $insertStmt = $pdo->prepare("INSERT INTO meals (user_id, date, meal_time, food_name, calories, protein, carbs, fat) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($meals as $meal) {
                $insertStmt->execute([
                    $user_id,
                    $today,
                    $meal['meal_time'],
                    $meal['food_name'],
                    $meal['calories'],
                    $meal['protein'],
                    $meal['carbs'],
                    $meal['fat']
                ]);
                $successCount++;
            }
        }

        // Clear any output that might have been generated
        ob_end_clean();

        // Send JSON response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => "Successfully saved $successCount food items!", 'count' => $successCount]);
        exit;
    } catch (Exception $e) {
        // Clear any output that might have been generated
        ob_end_clean();

        // Send JSON error response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Error saving meals: ' . $e->getMessage()]);
        exit;
    }
}

// Handle deleting single meal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_meal'])) {
    try {
        $meal_id = $_POST['meal_id'];

        // Verify the meal belongs to the current user
        $checkStmt = $pdo->prepare("SELECT id FROM meals WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$meal_id, $user_id]);
        $meal = $checkStmt->fetch();

        if (!$meal) {
            throw new Exception('Meal not found or access denied');
        }

        // Delete the meal
        $deleteStmt = $pdo->prepare("DELETE FROM meals WHERE id = ? AND user_id = ?");
        $deleteStmt->execute([$meal_id, $user_id]);

        // Clear any output that might have been generated
        ob_end_clean();

        // Send JSON response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Meal deleted successfully!']);
        exit;
    } catch (Exception $e) {
        // Clear any output that might have been generated
        ob_end_clean();

        // Send JSON error response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Error deleting meal: ' . $e->getMessage()]);
        exit;
    }
}

// Get user's custom foods from database
try {
    $customFoodsStmt = $pdo->prepare("SELECT * FROM custom_foods WHERE user_id = ? ORDER BY food_name");
    $customFoodsStmt->execute([$user_id]);
    $customFoods = $customFoodsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $customFoods = [];
}

// Get existing meals for display (only for GET requests)
try {
    $stmt = $pdo->prepare("SELECT * FROM meals WHERE user_id = ? AND date = ? ORDER BY 
        CASE meal_time 
            WHEN 'breakfast' THEN 1
            WHEN 'lunch' THEN 2
            WHEN 'dinner' THEN 3
            WHEN 'snack' THEN 4
            ELSE 5
        END, id");
    $stmt->execute([$user_id, $today]);
    $existingMeals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $existingMeals = [];
}

// Calculate totals
$totalCalories = 0;
$totalProtein = 0;
$totalCarbs = 0;
$totalFat = 0;

foreach ($existingMeals as $meal) {
    $totalCalories += $meal['calories'];
    $totalProtein += $meal['protein'];
    $totalCarbs += $meal['carbs'];
    $totalFat += $meal['fat'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meal Planner - FitTrack Pro</title>

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
            background: linear-gradient(135deg, rgba(157, 78, 221, 0.1), rgba(0, 212, 255, 0.1));
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(157, 78, 221, 0.3);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, var(--accent) 50%, var(--primary) 100%);
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
            color: var(--accent);
        }

        .date-time-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.8rem 1.8rem;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            margin-top: 1rem;
        }

        /* Stats Cards */
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

        .stats-goal {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        /* Macronutrient Card */
        .macronutrient-card {
            background: linear-gradient(145deg, rgba(157, 78, 221, 0.05), rgba(157, 78, 221, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(157, 78, 221, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(157, 78, 221, 0.1);
        }

        .macronutrient-card h5 {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 2rem;
        }

        .macronutrient-card h5 i {
            color: var(--accent);
        }

        .progress-ring {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem auto;
        }

        .progress-ring-circle {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .progress-ring-bg {
            fill: none;
            stroke: rgba(255, 255, 255, 0.1);
            stroke-width: 10;
        }

        .progress-ring-fill {
            fill: none;
            stroke: var(--primary);
            stroke-width: 10;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease-in-out;
        }

        .progress-text {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .progress-percent {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .progress-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .macro-chart-container {
            height: 200px;
            margin-top: 1rem;
        }

        .macronutrient-item {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .macronutrient-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(5px);
        }

        .macro-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .macro-info {
            flex: 1;
        }

        .macro-name {
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
        }

        .macro-value {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .macro-amount {
            font-weight: 700;
            font-size: 1.2rem;
            color: white;
        }

        /* Water Tracker */
        .water-tracker {
            background: linear-gradient(145deg, rgba(0, 212, 255, 0.08), rgba(0, 212, 255, 0.03));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.1);
        }

        .water-tracker h5 {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 2rem;
        }

        .water-tracker h5 i {
            color: var(--primary);
        }

        .water-cups {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 1rem;
            justify-content: center;
        }

        .water-cup {
            width: 60px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            color: rgba(255, 255, 255, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .water-cup.filled {
            background: var(--gradient-primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 5px 20px rgba(0, 212, 255, 0.3);
        }

        .water-cup:hover {
            transform: scale(1.1);
            border-color: var(--primary);
        }

        /* Search Section */
        .search-section {
            background: linear-gradient(145deg, rgba(255, 45, 117, 0.05), rgba(255, 45, 117, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 45, 117, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(255, 45, 117, 0.1);
        }

        .search-section h5 {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 2rem;
        }

        .search-section h5 i {
            color: var(--secondary);
        }

        .search-input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.1rem;
        }

        /* Food Categories */
        .food-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .food-category-btn {
            padding: 0.6rem 1.2rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .food-category-btn:hover,
        .food-category-btn.active {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        /* Search Results */
        .search-results {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 1rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.03);
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .food-result {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .food-result:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
            border-color: rgba(0, 212, 255, 0.2);
        }

        .food-name {
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
        }

        .food-macros {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Meal Sections */
        .meal-section {
            background: linear-gradient(145deg, rgba(255, 45, 117, 0.05), rgba(255, 45, 117, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 45, 117, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(255, 45, 117, 0.1);
        }

        .meal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .meal-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-right: 1.5rem;
        }

        .meal-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: white;
            flex: 1;
        }

        .meal-calories {
            background: var(--gradient-primary);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);
        }

        /* Food Items */
        .food-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.03);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .food-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(5px);
            border-color: rgba(0, 212, 255, 0.2);
        }

        .food-info {
            flex: 1;
        }

        .food-calories {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.2rem;
            margin-left: 1rem;
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

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--gradient-success);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 230, 118, 0.3);
        }

        .btn-danger {
            background: var(--gradient-secondary);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 45, 117, 0.3);
        }

        .add-food-btn {
            background: var(--gradient-success);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-food-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 230, 118, 0.3);
        }

        .barcode-simulator {
            background: var(--gradient-accent);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .barcode-simulator:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(157, 78, 221, 0.3);
        }

        /* Delete Buttons */
        .delete-btn {
            background: var(--gradient-secondary);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 1rem;
            flex-shrink: 0;
        }

        .delete-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(255, 45, 117, 0.3);
        }

        .delete-meal-btn {
            background: transparent;
            color: #ff6b6b;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: absolute;
            top: 10px;
            right: 10px;
            opacity: 0;
        }

        .food-item:hover .delete-meal-btn {
            opacity: 1;
        }

        .delete-meal-btn:hover {
            background: #ff6b6b;
            color: white;
            transform: scale(1.1);
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state-icon {
            font-size: 5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 1.8rem;
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
            font-size: 1.1rem;
        }

        /* Meal Badges */
        .meal-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .meal-badge-breakfast {
            background: rgba(255, 154, 158, 0.2);
            color: #ff6b6b;
        }

        .meal-badge-lunch {
            background: rgba(161, 196, 253, 0.2);
            color: #4d96ff;
        }

        .meal-badge-dinner {
            background: rgba(132, 250, 176, 0.2);
            color: #38b000;
        }

        .meal-badge-snack {
            background: rgba(251, 194, 235, 0.2);
            color: #8338ec;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        /* Modal Styling */
        .modal-content {
            background: var(--darker);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 700;
            color: white;
        }

        .btn-close {
            filter: invert(1) brightness(2);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
        }

        .meal-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .meal-option:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(5px);
        }

        .meal-option.selected {
            border-color: var(--primary);
            background: rgba(0, 212, 255, 0.1);
        }

        .meal-option-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-right: 1rem;
            color: white;
        }

        .meal-option-info {
            flex: 1;
        }

        .meal-option-name {
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
        }

        .meal-option-calories {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Mobile Navigation */
        .mobile-nav {
            display: none;
        }

        /* Alert */
        .alert {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 12px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
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

        /* Custom Food Modal */
        .form-label {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
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
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

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

            .date-display {
                font-size: 0.95rem;
                padding: 0.6rem 1.2rem;
            }

            .stats-container,
            .macronutrient-card,
            .water-tracker,
            .search-section,
            .meal-section {
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

            .meal-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .meal-icon {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }

            .meal-title {
                font-size: 1.2rem;
            }

            .food-item {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .food-calories {
                margin-left: 0;
                align-self: flex-end;
            }

            .delete-btn {
                position: absolute;
                top: 10px;
                right: 10px;
            }

            .water-cup {
                width: 45px;
                height: 60px;
                font-size: 1.2rem;
            }

            .progress-ring {
                width: 100px;
                height: 100px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
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

            .date-display {
                font-size: 0.85rem;
                padding: 0.5rem 1rem;
            }

            .stats-container h5,
            .macronutrient-card h5,
            .water-tracker h5,
            .search-section h5,
            .meal-section h5 {
                font-size: 1.2rem;
            }

            .stats-card {
                padding: 1rem;
            }

            .stats-value {
                font-size: 1.5rem;
            }

            .water-cup {
                width: 40px;
                height: 55px;
                font-size: 1rem;
            }

            .food-item {
                padding: 0.875rem;
            }

            .food-name {
                font-size: 0.95rem;
            }

            .food-macros {
                font-size: 0.8rem;
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

            .progress-percent {
                font-size: 1rem;
                font-weight: 700;
                color: var(--primary);
                display: block;
            }

            .d-flex {
                flex-direction: column !important;
            }

            .progress-text {
                position: absolute;
                top: 10%;
                left: 10%;
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
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
                        <a class="nav-link active" href="planner.php">
                            <i class="fas fa-utensils"></i> Meals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../progress/charts.php">
                            <i class="fas fa-chart-line"></i> Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../progress/photos.php">
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

    <!-- Modal for selecting meal -->
    <div class="modal fade" id="mealSelectModal" tabindex="-1" aria-labelledby="mealSelectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mealSelectModalLabel">Select Meal Time</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="mealOptionsContainer">
                        <!-- Meal options will be added here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmMealSelection">Add to Selected Meal</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for adding custom food -->
    <div class="modal fade" id="customFoodModal" tabindex="-1" aria-labelledby="customFoodModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customFoodModalLabel">Add Custom Food</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="customFoodForm">
                        <div class="mb-3">
                            <label for="customFoodName" class="form-label">Food Name *</label>
                            <input type="text" class="form-control search-input" id="customFoodName" required
                                placeholder="e.g., Potato Chips, Homemade Sandwich">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customCalories" class="form-label">Calories *</label>
                                <input type="number" class="form-control search-input" id="customCalories"
                                    min="0" step="1" required placeholder="kcal">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customServing" class="form-label">Serving Size</label>
                                <input type="text" class="form-control search-input" id="customServing"
                                    placeholder="e.g., 100g, 1 packet, 1 cup">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="customProtein" class="form-label">Protein (g)</label>
                                <input type="number" class="form-control search-input" id="customProtein"
                                    min="0" step="0.1" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="customCarbs" class="form-label">Carbs (g)</label>
                                <input type="number" class="form-control search-input" id="customCarbs"
                                    min="0" step="0.1" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="customFat" class="form-label">Fat (g)</label>
                                <input type="number" class="form-control search-input" id="customFat"
                                    min="0" step="0.1" value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="customCategory" class="form-label">Category</label>
                            <select class="form-select search-input" id="customCategory">
                                <option value="custom">Custom</option>
                                <option value="pakistani">Pakistani</option>
                                <option value="fastfood">Fast Food</option>
                                <option value="snack">Snack</option>
                                <option value="dessert">Dessert</option>
                                <option value="healthy">Healthy</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCustomFoodBtn">
                        <i class="fas fa-save me-2"></i>Save & Add to Meal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for saving meals -->
    <form id="saveMealForm" method="POST" style="display: none;">
        <input type="hidden" name="save_meal_plan" value="1">
        <input type="hidden" name="meals_data" id="mealsDataInput">
    </form>

    <div class="container mt-4 mb-5">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-utensils me-2"></i>Meal Planner</h1>
            <p>Track your nutrition and calories for optimal fitness results</p>
            <div class="date-display">
                <i class="fas fa-calendar-alt"></i>
                <span id="current-date"><?= htmlspecialchars($display_date) ?></span>
                <i class="fas fa-clock ms-3"></i>
                <span id="current-time"><?= htmlspecialchars($display_time) ?></span>
                <span class="ms-2 small">PKT (Islamabad)</span>
            </div>
        </div>

        <!-- Stats Container -->
        <div class="stats-container">
            <h4 class="mb-4"><i class="fas fa-chart-bar me-2 text-primary"></i>Today's Nutrition Summary</h4>
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stats-value" id="caloriesConsumed"><?php echo $totalCalories; ?></div>
                        <div class="stats-label">Calories Consumed</div>
                        <div class="stats-goal">Goal: <span id="calorieGoal">2500</span> kcal</div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-drumstick-bite"></i>
                        </div>
                        <div class="stats-value" id="proteinAmount"><?php echo $totalProtein; ?>g</div>
                        <div class="stats-label">Protein</div>
                        <div class="stats-goal">Target: 160g (for muscle)</div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-bread-slice"></i>
                        </div>
                        <div class="stats-value" id="carbsAmount"><?php echo $totalCarbs; ?>g</div>
                        <div class="stats-label">Carbohydrates</div>
                        <div class="stats-goal">Energy source</div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-oil-can"></i>
                        </div>
                        <div class="stats-value" id="fatAmount"><?php echo $totalFat; ?>g</div>
                        <div class="stats-label">Fats</div>
                        <div class="stats-goal">Healthy fats</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Nutrition & Stats -->
            <div class="col-lg-4">
                <!-- Calorie Progress -->
                <div class="macronutrient-card">
                    <h5><i class="fas fa-bullseye me-2"></i>Calorie Goal Progress</h5>
                    <div class="progress-ring">
                        <svg class="progress-ring-circle" width="120" height="120">
                            <circle class="progress-ring-bg" cx="60" cy="60" r="50"></circle>
                            <circle class="progress-ring-fill" id="calorieProgress" cx="60" cy="60" r="50"></circle>
                        </svg>
                        <div class="progress-text">
                            <div class="progress-percent" id="caloriePercent"><?php echo round(($totalCalories / 2500) * 100); ?>%</div>
                            <div class="progress-label">of Goal</div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-secondary btn-sm me-2" onclick="adjustCalorieGoal(-100)">
                            <i class="fas fa-minus"></i> 100
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="adjustCalorieGoal(100)">
                            <i class="fas fa-plus"></i> 100
                        </button>
                    </div>
                </div>

                <!-- Macronutrient Breakdown -->
                <div class="macronutrient-card">
                    <h5><i class="fas fa-chart-pie me-2"></i>Macronutrient Breakdown</h5>
                    <div class="macro-chart-container">
                        <canvas id="macroChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="macronutrient-item">
                            <div class="macro-color" style="background: #3a86ff;"></div>
                            <div class="macro-info">
                                <div class="macro-name">Protein</div>
                                <div class="macro-value">Essential for muscle repair</div>
                            </div>
                            <div class="macro-amount" id="proteinDisplay"><?php echo $totalProtein; ?>g</div>
                        </div>
                        <div class="macronutrient-item">
                            <div class="macro-color" style="background: #ff006e;"></div>
                            <div class="macro-info">
                                <div class="macro-name">Carbohydrates</div>
                                <div class="macro-value">Primary energy source</div>
                            </div>
                            <div class="macro-amount" id="carbsDisplay"><?php echo $totalCarbs; ?>g</div>
                        </div>
                        <div class="macronutrient-item">
                            <div class="macro-color" style="background: #ffbe0b;"></div>
                            <div class="macro-info">
                                <div class="macro-name">Fats</div>
                                <div class="macro-value">Hormone production</div>
                            </div>
                            <div class="macro-amount" id="fatDisplay"><?php echo $totalFat; ?>g</div>
                        </div>
                    </div>
                </div>

                <!-- Water Intake -->
                <div class="water-tracker">
                    <h5><i class="fas fa-tint me-2"></i>Water Intake</h5>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <div class="stats-value" id="waterAmount">0/8</div>
                            <div class="stats-label">Glasses Today</div>
                        </div>
                        <div>
                            <button class="btn btn-secondary btn-sm me-2" onclick="adjustWater(-1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="adjustWater(1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="water-cups" id="waterCups">
                        <!-- Water cups will be generated here -->
                    </div>
                </div>
            </div>

            <!-- Right Column - Meals & Search -->
            <div class="col-lg-8">
                <!-- Food Search -->
                <div class="search-section">
                    <h5><i class="fas fa-search me-2"></i>Search Foods</h5>
                    <div class="search-input-group">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="foodSearch" class="search-input" placeholder="Search foods (e.g., biryani, chicken tikka, apple...)">
                    </div>

                    <!-- Food Categories -->
                    <div class="food-categories mb-3" id="foodCategories">
                        <button class="food-category-btn active" data-category="all">
                            All Foods
                        </button>
                        <button class="food-category-btn" data-category="pakistani">
                            <i class="fas fa-flag me-1"></i>Pakistani
                        </button>
                        <button class="food-category-btn" data-category="fastfood">
                            <i class="fas fa-hamburger me-1"></i>Fast Food
                        </button>
                        <button class="food-category-btn" data-category="beverages">
                            <i class="fas fa-glass-whiskey me-1"></i>Beverages
                        </button>
                        <button class="food-category-btn" data-category="fruits">
                            <i class="fas fa-apple-alt me-1"></i>Fruits
                        </button>
                        <button class="food-category-btn" data-category="desserts">
                            <i class="fas fa-ice-cream me-1"></i>Desserts
                        </button>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-primary flex-grow-1" id="searchBtn">
                            <i class="fas fa-search me-2"></i>Search Food
                        </button>
                        <button class="barcode-simulator" onclick="simulateBarcodeScan()">
                            <i class="fas fa-barcode me-2"></i>Quick Add
                        </button>
                        <button class="btn btn-secondary" id="addCustomFoodBtn" data-bs-toggle="modal" data-bs-target="#customFoodModal">
                            <i class="fas fa-plus-circle me-2"></i>Add Custom
                        </button>
                    </div>
                    <div class="search-results" id="searchResults" style="display: none;">
                        <!-- Search results will appear here -->
                    </div>
                </div>

                <!-- Today's Saved Meals -->
                <div class="meal-section">
                    <div class="meal-header">
                        <div class="meal-icon" style="background: linear-gradient(135deg, #00d4ff 0%, #9d4edd 100%);">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="meal-title">Today's Saved Meals</div>
                        <div class="meal-calories" id="total-calories"><?php echo $totalCalories; ?> kcal</div>
                    </div>

                    <div id="all-meals-items">
                        <?php if (empty($existingMeals)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <h4>No meals saved yet</h4>
                                <p>Search and add foods above to start tracking your nutrition</p>
                            </div>
                        <?php else: ?>
                            <?php
                            $mealDisplay = [
                                'breakfast' => ['name' => 'Breakfast', 'badge' => 'meal-badge-breakfast'],
                                'lunch' => ['name' => 'Lunch', 'badge' => 'meal-badge-lunch'],
                                'dinner' => ['name' => 'Dinner', 'badge' => 'meal-badge-dinner'],
                                'snack' => ['name' => 'Snacks', 'badge' => 'meal-badge-snack']
                            ];

                            foreach ($existingMeals as $item):
                                $mealInfo = $mealDisplay[$item['meal_time']] ?? ['name' => ucfirst($item['meal_time']), 'badge' => ''];
                            ?>
                                <div class="food-item" data-id="<?php echo $item['id']; ?>">
                                    <div class="food-info">
                                        <div class="food-name">
                                            <span class="meal-badge <?php echo $mealInfo['badge']; ?>">
                                                <?php echo $mealInfo['name']; ?>
                                            </span>
                                            <?php echo htmlspecialchars($item['food_name']); ?>
                                        </div>
                                        <div class="food-macros">
                                            Protein: <?php echo $item['protein']; ?>g •
                                            Carbs: <?php echo $item['carbs']; ?>g •
                                            Fat: <?php echo $item['fat']; ?>g
                                        </div>
                                    </div>
                                    <div class="food-calories"><?php echo $item['calories']; ?> kcal</div>
                                    <button class="delete-meal-btn" onclick="deleteSavedMeal(<?php echo $item['id']; ?>, this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Individual Meal Sections -->
                <?php
                $meals = [
                    'breakfast' => ['name' => 'Breakfast', 'icon' => 'fa-sun', 'color' => 'linear-gradient(135deg, #FF9A9E 0%, #FAD0C4 100%)'],
                    'lunch' => ['name' => 'Lunch', 'icon' => 'fa-utensils', 'color' => 'linear-gradient(135deg, #A1C4FD 0%, #C2E9FB 100%)'],
                    'dinner' => ['name' => 'Dinner', 'icon' => 'fa-moon', 'color' => 'linear-gradient(135deg, #84FAB0 0%, #8FD3F4 100%)'],
                    'snack' => ['name' => 'Snacks', 'icon' => 'fa-apple-alt', 'color' => 'linear-gradient(135deg, #FBC2EB 0%, #A6C1EE 100%)']
                ];

                foreach ($meals as $key => $meal) {
                    $mealCalories = 0;
                    $mealItems = array_filter($existingMeals, function ($item) use ($key) {
                        return $item['meal_time'] == $key;
                    });

                    foreach ($mealItems as $item) {
                        $mealCalories += $item['calories'];
                    }
                ?>
                    <div class="meal-section">
                        <div class="meal-header">
                            <div class="meal-icon" style="background: <?php echo $meal['color']; ?>;">
                                <i class="fas <?php echo $meal['icon']; ?>"></i>
                            </div>
                            <div class="meal-title"><?php echo $meal['name']; ?></div>
                            <div class="meal-calories" id="meal-<?php echo $key; ?>-calories">
                                <?php echo $mealCalories; ?> kcal
                            </div>
                        </div>

                        <div id="meal-<?php echo $key; ?>-items">
                            <?php if (empty($mealItems)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <h4>No foods added yet</h4>
                                    <p>Search and add foods above to this meal</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($mealItems as $item): ?>
                                    <div class="food-item" data-id="<?php echo $item['id']; ?>"
                                        data-calories="<?php echo $item['calories']; ?>"
                                        data-protein="<?php echo $item['protein']; ?>"
                                        data-carbs="<?php echo $item['carbs']; ?>"
                                        data-fat="<?php echo $item['fat']; ?>"
                                        data-meal-type="<?php echo $key; ?>">
                                        <div class="food-info">
                                            <div class="food-name"><?php echo htmlspecialchars($item['food_name']); ?></div>
                                            <div class="food-macros">
                                                Protein: <?php echo $item['protein']; ?>g •
                                                Carbs: <?php echo $item['carbs']; ?>g •
                                                Fat: <?php echo $item['fat']; ?>g
                                            </div>
                                        </div>
                                        <div class="food-calories"><?php echo $item['calories']; ?> kcal</div>
                                        <button class="delete-btn" onclick="deleteSavedMeal(<?php echo $item['id']; ?>, this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php } ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn btn-success flex-grow-1" id="saveMealPlanBtn">
                        <i class="fas fa-save me-2"></i>Save Today's Meal Plan
                    </button>
                    <button class="btn btn-danger" onclick="clearAllMeals()">
                        <i class="fas fa-trash me-2"></i>Clear All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Global variables
        let totalCalories = <?php echo $totalCalories; ?>;
        let totalProtein = <?php echo $totalProtein; ?>;
        let totalCarbs = <?php echo $totalCarbs; ?>;
        let totalFat = <?php echo $totalFat; ?>;
        let waterGlasses = 0;
        const maxWater = 8;
        let calorieGoal = 2500;
        let macroChart = null;
        let selectedMealKey = null;
        let currentFoodToAdd = null;
        let tempFoodItems = [];
        let currentCategory = 'all';

        // Pakistani Food Database
        const pakistaniFoodDatabase = {
            'pakistani': [{
                    title: "Chicken Biryani (1 Plate = 500g)",
                    nutrition: {
                        calories: 800,
                        protein: 40,
                        carbs: 105,
                        fat: 30
                    }
                },
                {
                    title: "Beef Nihari (with 1 Naan)",
                    nutrition: {
                        calories: 700,
                        protein: 35,
                        carbs: 60,
                        fat: 37
                    }
                },
                {
                    title: "Haleem (1 Bowl = 400g)",
                    nutrition: {
                        calories: 525,
                        protein: 30,
                        carbs: 70,
                        fat: 20
                    }
                },
                {
                    title: "Chicken Karahi (1 Plate)",
                    nutrition: {
                        calories: 600,
                        protein: 45,
                        carbs: 12,
                        fat: 42
                    }
                },
                {
                    title: "Beef Karahi (1 Plate)",
                    nutrition: {
                        calories: 650,
                        protein: 48,
                        carbs: 12,
                        fat: 45
                    }
                },
                {
                    title: "Daal Chawal (1 Plate)",
                    nutrition: {
                        calories: 475,
                        protein: 18,
                        carbs: 80,
                        fat: 12
                    }
                },
                {
                    title: "Vegetable Roll (1 piece)",
                    nutrition: {
                        calories: 260,
                        protein: 5,
                        carbs: 32,
                        fat: 13
                    }
                },
                {
                    title: "Aloo Samosa (1 piece)",
                    nutrition: {
                        calories: 220,
                        protein: 3,
                        carbs: 28,
                        fat: 11
                    }
                },
                {
                    title: "Gosht Ki Boti (1 piece, cooked)",
                    nutrition: {
                        calories: 55,
                        protein: 7,
                        carbs: 0,
                        fat: 3
                    }
                },
                {
                    title: "Chicken Box Patty (1 piece)",
                    nutrition: {
                        calories: 380,
                        protein: 12,
                        carbs: 32,
                        fat: 22
                    }
                },

                {
                    title: "Mutter (Peas) with Rice (1 plate)",
                    nutrition: {
                        calories: 350,
                        protein: 12,
                        carbs: 65,
                        fat: 6
                    }
                },
                {
                    title: "Aloo Chawal (Potato Rice, 1 plate)",
                    nutrition: {
                        calories: 420,
                        protein: 8,
                        carbs: 80,
                        fat: 8
                    }
                },
                {
                    title: "Vegetable Raita (1 bowl)",
                    nutrition: {
                        calories: 120,
                        protein: 6,
                        carbs: 12,
                        fat: 6
                    }
                },
                {
                    title: "Mutter Aloo (Peas & Potatoes, 1 bowl)",
                    nutrition: {
                        calories: 280,
                        protein: 10,
                        carbs: 45,
                        fat: 7
                    }
                },
                {
                    title: "Mix Vegetable Curry (1 bowl)",
                    nutrition: {
                        calories: 180,
                        protein: 8,
                        carbs: 25,
                        fat: 6
                    }
                },
                {
                    title: "Raita (Plain Yogurt, 1 bowl)",
                    nutrition: {
                        calories: 90,
                        protein: 5,
                        carbs: 8,
                        fat: 4
                    }
                },
                {
                    title: "Mutter Aloo Chawal (Peas & Potato Rice, full plate)",
                    nutrition: {
                        calories: 520,
                        protein: 16,
                        carbs: 90,
                        fat: 12
                    }
                },
                {
                    title: "Lobia (Black-eyed peas, 1 bowl)",
                    nutrition: {
                        calories: 160,
                        protein: 8,
                        carbs: 30,
                        fat: 2
                    }
                },
                {
                    title: "Red Lobia (Rajma/Kidney Beans, 1 bowl)",
                    nutrition: {
                        calories: 220,
                        protein: 15,
                        carbs: 40,
                        fat: 1
                    }
                },
                {
                    title: "Black Chole (1 bowl)",
                    nutrition: {
                        calories: 270,
                        protein: 15,
                        carbs: 45,
                        fat: 4
                    }
                },

                {
                    title: "Chana Masala with Puri (2 Puris)",
                    nutrition: {
                        calories: 575,
                        protein: 18,
                        carbs: 80,
                        fat: 25
                    }
                },
                {
                    title: "Seekh Kebab (2 pieces, 150g)",
                    nutrition: {
                        calories: 350,
                        protein: 28,
                        carbs: 8,
                        fat: 25
                    }
                },
                {
                    title: "Chicken Tikka (4 pieces, 200g)",
                    nutrition: {
                        calories: 300,
                        protein: 40,
                        carbs: 8,
                        fat: 15
                    }
                },
                {
                    title: "Aloo Gosht (1 Bowl)",
                    nutrition: {
                        calories: 400,
                        protein: 28,
                        carbs: 25,
                        fat: 25
                    }
                },
                {
                    title: "Plain Naan (1 Medium)",
                    nutrition: {
                        calories: 285,
                        protein: 9,
                        carbs: 50,
                        fat: 7
                    }
                },
                {
                    title: "Roti/Chapati (1 Medium, with ghee)",
                    nutrition: {
                        calories: 150,
                        protein: 3.5,
                        carbs: 23,
                        fat: 5
                    }
                },
                {
                    title: "Plain Paratha (1 piece)",
                    nutrition: {
                        calories: 260,
                        protein: 5,
                        carbs: 35,
                        fat: 11
                    }
                },
                {
                    title: "Aloo Paratha (1 piece, stuffed)",
                    nutrition: {
                        calories: 320,
                        protein: 7,
                        carbs: 45,
                        fat: 14
                    }
                },
                {
                    title: "Tandoori Roti (1 piece)",
                    nutrition: {
                        calories: 120,
                        protein: 4,
                        carbs: 22,
                        fat: 2
                    }
                },
                {
                    title: "Chapati (1 piece, no ghee)",
                    nutrition: {
                        calories: 90,
                        protein: 3,
                        carbs: 18,
                        fat: 1
                    }
                },
                {
                    title: "Missi Roti (1 piece)",
                    nutrition: {
                        calories: 140,
                        protein: 6,
                        carbs: 25,
                        fat: 3
                    }
                },
                {
                    title: "Puri (1 piece)",
                    nutrition: {
                        calories: 180,
                        protein: 3,
                        carbs: 22,
                        fat: 9
                    }
                },
                {
                    title: "Butter Naan (1 piece)",
                    nutrition: {
                        calories: 350,
                        protein: 9,
                        carbs: 50,
                        fat: 13
                    }
                },
                {
                    title: "Garlic Naan (1 piece)",
                    nutrition: {
                        calories: 380,
                        protein: 9,
                        carbs: 52,
                        fat: 16
                    }
                },
                {
                    title: "Rumali Roti (1 piece)",
                    nutrition: {
                        calories: 80,
                        protein: 3,
                        carbs: 16,
                        fat: 1
                    }
                },
                {
                    title: "Taftan (1 piece)",
                    nutrition: {
                        calories: 280,
                        protein: 8,
                        carbs: 48,
                        fat: 7
                    }
                },
                {
                    title: "Sheermal (1 piece)",
                    nutrition: {
                        calories: 320,
                        protein: 7,
                        carbs: 55,
                        fat: 9
                    }
                },
                {
                    title: "Kulcha (1 piece)",
                    nutrition: {
                        calories: 240,
                        protein: 6,
                        carbs: 38,
                        fat: 8
                    }
                },
                {
                    title: "Bhatura (1 piece)",
                    nutrition: {
                        calories: 280,
                        protein: 6,
                        carbs: 42,
                        fat: 11
                    }
                },
                {
                    title: "Roghni Naan (1 piece)",
                    nutrition: {
                        calories: 400,
                        protein: 9,
                        carbs: 52,
                        fat: 18
                    }
                },
                {
                    title: "Khameeri Roti (1 piece)",
                    nutrition: {
                        calories: 160,
                        protein: 5,
                        carbs: 28,
                        fat: 4
                    }
                },
                {
                    title: "Tandoori Paratha (1 piece)",
                    nutrition: {
                        calories: 300,
                        protein: 6,
                        carbs: 40,
                        fat: 13
                    }
                },
                {
                    title: "Ghee Roti (1 piece, with ghee)",
                    nutrition: {
                        calories: 180,
                        protein: 4,
                        carbs: 23,
                        fat: 8
                    }
                },
                {
                    title: "Shami Kabab Cutlet (1 piece)",
                    nutrition: {
                        calories: 120,
                        protein: 8,
                        carbs: 6,
                        fat: 7
                    }
                },
                {
                    title: "Aloo (Potato) Cutlet (1 piece)",
                    nutrition: {
                        calories: 100,
                        protein: 2,
                        carbs: 15,
                        fat: 4
                    }
                },
                {
                    title: "Chicken Cutlet (1 piece)",
                    nutrition: {
                        calories: 150,
                        protein: 12,
                        carbs: 8,
                        fat: 8
                    }
                },
                {
                    title: "Beef Cutlet (1 piece)",
                    nutrition: {
                        calories: 160,
                        protein: 10,
                        carbs: 8,
                        fat: 10
                    }
                },
                {
                    title: "Lacha Murgh (Spicy Shredded Chicken, 1 plate)",
                    nutrition: {
                        calories: 320,
                        protein: 38,
                        carbs: 10,
                        fat: 15
                    }
                },
                {
                    title: "Lacha Chicken (Shredded Chicken, 1 serving)",
                    nutrition: {
                        calories: 280,
                        protein: 35,
                        carbs: 8,
                        fat: 12
                    }
                },
                {
                    title: "Chicken Aloo Cutlet (1 piece)",
                    nutrition: {
                        calories: 130,
                        protein: 10,
                        carbs: 12,
                        fat: 6
                    }
                },
                {
                    title: "Tandoor ki Tali Hui Roti (Griddle fried roti, 1 piece)",
                    nutrition: {
                        calories: 200,
                        protein: 4,
                        carbs: 28,
                        fat: 8
                    }
                },
                {
                    title: "Whole Wheat Roti (1 piece)",
                    nutrition: {
                        calories: 110,
                        protein: 4,
                        carbs: 20,
                        fat: 2
                    }
                },
                {
                    title: "Makki di Roti (1 piece)",
                    nutrition: {
                        calories: 170,
                        protein: 4,
                        carbs: 28,
                        fat: 5
                    }
                },
                {
                    title: "Bajre ki Roti (1 piece)",
                    nutrition: {
                        calories: 130,
                        protein: 4,
                        carbs: 24,
                        fat: 3
                    }
                },
                {
                    title: "Plain Flour Roti (1 piece, maida)",
                    nutrition: {
                        calories: 130,
                        protein: 3,
                        carbs: 24,
                        fat: 2
                    }
                },
                {
                    title: "Halwa Poori (2 Puris, 1 bowl Halwa)",
                    nutrition: {
                        calories: 700,
                        protein: 10,
                        carbs: 105,
                        fat: 32
                    }
                }
            ],
            'mediterranean': [{
                    title: "Falafel (Tamiya, 1 piece)",
                    nutrition: {
                        calories: 65,
                        protein: 2.5,
                        carbs: 5,
                        fat: 3.5
                    }
                },
                {
                    title: "Mini Falafel (20g ball)",
                    nutrition: {
                        calories: 50,
                        protein: 1.8,
                        carbs: 4.2,
                        fat: 2.8
                    }
                },
                {
                    title: "Hummus (2 tbsp / 30g)",
                    nutrition: {
                        calories: 75,
                        protein: 2,
                        carbs: 5,
                        fat: 5.5
                    }
                },
                {
                    title: "Tahini Sauce (2 tbsp / 30g)",
                    nutrition: {
                        calories: 180,
                        protein: 5,
                        carbs: 3.5,
                        fat: 17
                    }
                },
                {
                    title: "Fried Eggplant (1 slice)",
                    nutrition: {
                        calories: 30,
                        protein: 0.3,
                        carbs: 3.5,
                        fat: 2
                    }
                },
            ],
            'fastfood': [{
                    title: "Beef/Chandi Burger",
                    nutrition: {
                        calories: 525,
                        protein: 30,
                        carbs: 42,
                        fat: 25
                    }
                },
                {
                    title: "Chicken Roll/Paratha Roll",
                    nutrition: {
                        calories: 600,
                        protein: 28,
                        carbs: 50,
                        fat: 32
                    }
                },
                {
                    title: "Full Grilled Chicken (with skin)",
                    nutrition: {
                        calories: 1000,
                        protein: 90,
                        carbs: 8,
                        fat: 65
                    }
                },
                {
                    title: "Plate of Mixed BBQ (Seekh, Tikka, Malai Boti)",
                    nutrition: {
                        calories: 850,
                        protein: 70,
                        carbs: 20,
                        fat: 55
                    }
                },
                {
                    title: "2 Piece Fried Chicken Meal (with fries & drink)",
                    nutrition: {
                        calories: 1100,
                        protein: 45,
                        carbs: 90,
                        fat: 60
                    }
                },
                {
                    title: "Malai Boti Roll",
                    nutrition: {
                        calories: 550,
                        protein: 25,
                        carbs: 45,
                        fat: 30
                    }
                },
                {
                    title: "French Fries (Medium)",
                    nutrition: {
                        calories: 365,
                        protein: 4,
                        carbs: 48,
                        fat: 18
                    }
                },
                {
                    title: "French Fries (Large)",
                    nutrition: {
                        calories: 480,
                        protein: 5,
                        carbs: 63,
                        fat: 23
                    }
                },
                {
                    title: "French Fries (Small)",
                    nutrition: {
                        calories: 230,
                        protein: 3,
                        carbs: 30,
                        fat: 11
                    }
                },
                {
                    title: "Homemade Chicken Burger (1 burger)",
                    nutrition: {
                        calories: 320,
                        protein: 28,
                        carbs: 30,
                        fat: 12
                    }
                },
                {
                    title: "Homemade Chicken Burger with Whole Wheat Bun",
                    nutrition: {
                        calories: 350,
                        protein: 30,
                        carbs: 35,
                        fat: 12
                    }
                },
                {
                    title: "Homemade Chicken Patty Only (grilled)",
                    nutrition: {
                        calories: 180,
                        protein: 25,
                        carbs: 5,
                        fat: 7
                    }
                },
                {
                    title: "Chicken Burger (Regular)",
                    nutrition: {
                        calories: 450,
                        protein: 25,
                        carbs: 42,
                        fat: 22
                    }
                },
                {
                    title: "Chicken Burger with Cheese",
                    nutrition: {
                        calories: 520,
                        protein: 28,
                        carbs: 45,
                        fat: 27
                    }
                },
                {
                    title: "Grilled Chicken Burger",
                    nutrition: {
                        calories: 380,
                        protein: 30,
                        carbs: 35,
                        fat: 15
                    }
                },
                {
                    title: "Spicy Chicken Burger",
                    nutrition: {
                        calories: 480,
                        protein: 26,
                        carbs: 44,
                        fat: 24
                    }
                },
                {
                    title: "Chicken Burger Meal (with fries & drink)",
                    nutrition: {
                        calories: 850,
                        protein: 32,
                        carbs: 95,
                        fat: 38
                    }
                },
                {
                    title: "Reshmi Kabab Roll",
                    nutrition: {
                        calories: 520,
                        protein: 28,
                        carbs: 42,
                        fat: 28
                    }
                },
                {
                    title: "Chicken Tikka Roll",
                    nutrition: {
                        calories: 500,
                        protein: 26,
                        carbs: 40,
                        fat: 26
                    }
                },
                {
                    title: "Beef Seekh Roll",
                    nutrition: {
                        calories: 580,
                        protein: 32,
                        carbs: 48,
                        fat: 32
                    }
                },
                {
                    title: "Zinger/Whopper Burger",
                    nutrition: {
                        calories: 650,
                        protein: 28,
                        carbs: 60,
                        fat: 38
                    }
                },
                {
                    title: "Bun Kabab (Double Patty)",
                    nutrition: {
                        calories: 425,
                        protein: 18,
                        carbs: 35,
                        fat: 25
                    }
                },
                {
                    title: "Samosa (2 pieces)",
                    nutrition: {
                        calories: 300,
                        protein: 6.5,
                        carbs: 35,
                        fat: 20
                    }
                },
                {
                    title: "Homemade Fries (Pan Fried, 1 serving)",
                    nutrition: {
                        calories: 220,
                        protein: 3,
                        carbs: 30,
                        fat: 10
                    }
                },
                {
                    title: "Homemade Fries (Air Fried, 1 serving)",
                    nutrition: {
                        calories: 150,
                        protein: 3,
                        carbs: 30,
                        fat: 3
                    }
                },
                {
                    title: "Pakora Platter (Mix, 150g)",
                    nutrition: {
                        calories: 475,
                        protein: 10,
                        carbs: 60,
                        fat: 25
                    }
                }
            ],
            'beverages': [{
                    title: "Doodh Patti Chai (1 cup)",
                    nutrition: {
                        calories: 150,
                        protein: 6,
                        carbs: 15,
                        fat: 7
                    }
                },
                {
                    title: "Dhoodh Coffee (Instant)",
                    nutrition: {
                        calories: 125,
                        protein: 5,
                        carbs: 15,
                        fat: 5
                    }
                },
                {
                    title: "Tea with Gur (Jaggery, 1 cup)",
                    nutrition: {
                        calories: 80,
                        protein: 2,
                        carbs: 18,
                        fat: 1
                    }
                },
                {
                    title: "Tea with Sugar (1 cup)",
                    nutrition: {
                        calories: 70,
                        protein: 2,
                        carbs: 16,
                        fat: 1
                    }
                },
                {
                    title: "Tea with Powdered Milk (1 cup)",
                    nutrition: {
                        calories: 90,
                        protein: 3,
                        carbs: 12,
                        fat: 3
                    }
                },
                {
                    title: "Tea with Gur & Powdered Milk (1 cup)",
                    nutrition: {
                        calories: 110,
                        protein: 4,
                        carbs: 20,
                        fat: 3
                    }
                },
                {
                    title: "Tea with Sugar & Powdered Milk (1 cup)",
                    nutrition: {
                        calories: 100,
                        protein: 4,
                        carbs: 18,
                        fat: 3
                    }
                },
                {
                    title: "Chocolate Tea (1 cup)",
                    nutrition: {
                        calories: 180,
                        protein: 5,
                        carbs: 25,
                        fat: 7
                    }
                },
                {
                    title: "Kashmiri Chai (1 cup)",
                    nutrition: {
                        calories: 185,
                        protein: 5,
                        carbs: 20,
                        fat: 8
                    }
                },
                {
                    title: "Green Tea (1 cup, plain)",
                    nutrition: {
                        calories: 2,
                        protein: 0.2,
                        carbs: 0.5,
                        fat: 0
                    }
                },
                {
                    title: "Green Tea with Honey (1 cup)",
                    nutrition: {
                        calories: 45,
                        protein: 0.2,
                        carbs: 12,
                        fat: 0
                    }
                },
                {
                    title: "Coca-Cola/Pepsi (330ml)",
                    nutrition: {
                        calories: 140,
                        protein: 0,
                        carbs: 39,
                        fat: 0
                    }
                },
                {
                    title: "Pakola (Ice Cream Soda)",
                    nutrition: {
                        calories: 180,
                        protein: 2,
                        carbs: 30,
                        fat: 4
                    }
                },
                {
                    title: "Fizz Up/Sprite/7Up (330ml)",
                    nutrition: {
                        calories: 140,
                        protein: 0,
                        carbs: 38,
                        fat: 0
                    }
                },
                {
                    title: "Fresh Orange Juice (250ml)",
                    nutrition: {
                        calories: 110,
                        protein: 2,
                        carbs: 25,
                        fat: 0.5
                    }
                },
                {
                    title: "Mango Juice (Sweetened, 250ml)",
                    nutrition: {
                        calories: 145,
                        protein: 1,
                        carbs: 37,
                        fat: 0
                    }
                },
                {
                    title: "Rooh Afza/Sharbat (1 glass)",
                    nutrition: {
                        calories: 100,
                        protein: 0,
                        carbs: 25,
                        fat: 0
                    }
                },
                {
                    title: "Lassi (Sweet, 250ml)",
                    nutrition: {
                        calories: 200,
                        protein: 8,
                        carbs: 30,
                        fat: 6
                    }
                },
                {
                    title: "Mirinda (330ml can)",
                    nutrition: {
                        calories: 140,
                        protein: 0,
                        carbs: 36,
                        fat: 0
                    }
                },
                {
                    title: "Powdered Milk Drink (no sugar, 1 glass)",
                    nutrition: {
                        calories: 120,
                        protein: 8,
                        carbs: 12,
                        fat: 4
                    }
                },
                {
                    title: "Powdered Milk Drink with Sugar (1 glass)",
                    nutrition: {
                        calories: 180,
                        protein: 8,
                        carbs: 28,
                        fat: 4
                    }
                },
                {
                    title: "Mirinda (250ml bottle)",
                    nutrition: {
                        calories: 105,
                        protein: 0,
                        carbs: 27,
                        fat: 0
                    }
                },
                {
                    title: "Lassi (Salted, 250ml)",
                    nutrition: {
                        calories: 125,
                        protein: 8,
                        carbs: 12,
                        fat: 5
                    }
                }
            ],
            'fruits': [{
                    title: "Mango (100g)",
                    nutrition: {
                        calories: 60,
                        protein: 0.8,
                        carbs: 15,
                        fat: 0.4
                    }
                },
                {
                    title: "Orange (100g)",
                    nutrition: {
                        calories: 47,
                        protein: 0.9,
                        carbs: 12,
                        fat: 0.1
                    }
                },
                {
                    title: "Apple (100g)",
                    nutrition: {
                        calories: 52,
                        protein: 0.3,
                        carbs: 14,
                        fat: 0.2
                    }
                },
                {
                    title: "Banana (100g)",
                    nutrition: {
                        calories: 89,
                        protein: 1.1,
                        carbs: 23,
                        fat: 0.3
                    }
                },
                {
                    title: "Guava (100g)",
                    nutrition: {
                        calories: 68,
                        protein: 2.6,
                        carbs: 14,
                        fat: 1
                    }
                },
                {
                    title: "Dates (2-3 pieces, 20g)",
                    nutrition: {
                        calories: 70,
                        protein: 0.6,
                        carbs: 18,
                        fat: 0
                    }
                }
            ],
            'desserts': [{
                    title: "Gulab Jamun (2 pieces)",
                    nutrition: {
                        calories: 300,
                        protein: 5,
                        carbs: 52,
                        fat: 12
                    }
                },
                {
                    title: "Gajar ka Halwa (1 small bowl)",
                    nutrition: {
                        calories: 375,
                        protein: 6,
                        carbs: 50,
                        fat: 20
                    }
                },
                {
                    title: "Kheer/Rice Pudding (1 bowl)",
                    nutrition: {
                        calories: 300,
                        protein: 8,
                        carbs: 45,
                        fat: 10
                    }
                },
                {
                    title: "Jalebi (100g)",
                    nutrition: {
                        calories: 400,
                        protein: 3,
                        carbs: 87,
                        fat: 8
                    }
                },
                {
                    title: "Barfi (1 piece)",
                    nutrition: {
                        calories: 175,
                        protein: 4,
                        carbs: 23,
                        fat: 9
                    }
                }
            ],
            'healthy': [{
                    title: "Grilled Chicken Breast (100g)",
                    nutrition: {
                        calories: 165,
                        protein: 31,
                        carbs: 0,
                        fat: 3.6
                    }
                },
                {
                    title: "Small Cake Slice (Vanilla)",
                    nutrition: {
                        calories: 250,
                        protein: 3,
                        carbs: 38,
                        fat: 10
                    }
                },
                {
                    title: "Normal White Bread (1 slice)",
                    nutrition: {
                        calories: 75,
                        protein: 2.5,
                        carbs: 14,
                        fat: 1
                    }
                },
                {
                    title: "Normal White Bread (2 slices)",
                    nutrition: {
                        calories: 150,
                        protein: 5,
                        carbs: 28,
                        fat: 2
                    }
                },
                {
                    title: "Standard White Bread Slice",
                    nutrition: {
                        calories: 80,
                        protein: 3,
                        carbs: 15,
                        fat: 1
                    }
                },
                {
                    title: "Chocolate Cake Slice",
                    nutrition: {
                        calories: 280,
                        protein: 4,
                        carbs: 42,
                        fat: 12
                    }
                },
                {
                    title: "Red Velvet Cake Slice",
                    nutrition: {
                        calories: 300,
                        protein: 4,
                        carbs: 45,
                        fat: 13
                    }
                },
                {
                    title: "Toasted White Bread (1 slice)",
                    nutrition: {
                        calories: 85,
                        protein: 3,
                        carbs: 16,
                        fat: 1
                    }
                },
                {
                    title: "Egg White Only (from 1 large egg)",
                    nutrition: {
                        calories: 17,
                        protein: 3.6,
                        carbs: 0.2,
                        fat: 0
                    }
                },
                {
                    title: "Egg Whites (100g)",
                    nutrition: {
                        calories: 52,
                        protein: 11,
                        carbs: 0.7,
                        fat: 0.2
                    }
                },
                {
                    title: "Egg White Omelette (from 2 eggs)",
                    nutrition: {
                        calories: 34,
                        protein: 7,
                        carbs: 0.4,
                        fat: 0
                    }
                },
                {
                    title: "Talbina (Barley Porridge, 1 bowl)",
                    nutrition: {
                        calories: 180,
                        protein: 6,
                        carbs: 35,
                        fat: 2
                    }
                },
                {
                    title: "Talbina with Honey (1 bowl)",
                    nutrition: {
                        calories: 220,
                        protein: 6,
                        carbs: 45,
                        fat: 2
                    }
                },
                {
                    title: "Talbina (Barley flour porridge, 1 serving)",
                    nutrition: {
                        calories: 150,
                        protein: 5,
                        carbs: 30,
                        fat: 1
                    }
                },
                {
                    title: "Toasted White Bread (2 slices)",
                    nutrition: {
                        calories: 170,
                        protein: 6,
                        carbs: 32,
                        fat: 2
                    }
                },
                {
                    title: "Toasted White Bread with Butter (1 slice)",
                    nutrition: {
                        calories: 120,
                        protein: 3,
                        carbs: 16,
                        fat: 5
                    }
                },
                {
                    title: "Cupcake (1 piece)",
                    nutrition: {
                        calories: 180,
                        protein: 2,
                        carbs: 28,
                        fat: 7
                    }
                },
                {
                    title: "Milk Malt Cake (1 piece)",
                    nutrition: {
                        calories: 280,
                        protein: 4,
                        carbs: 42,
                        fat: 10
                    }
                },
                {
                    title: "Milk Malt Cake (1 slice)",
                    nutrition: {
                        calories: 320,
                        protein: 5,
                        carbs: 48,
                        fat: 12
                    }
                },
                {
                    title: "Brown Rice (1 cup cooked)",
                    nutrition: {
                        calories: 216,
                        protein: 5,
                        carbs: 45,
                        fat: 2
                    }
                },
                {
                    title: "Broccoli (100g)",
                    nutrition: {
                        calories: 34,
                        protein: 2.8,
                        carbs: 7,
                        fat: 0.4
                    }
                },
                {
                    title: "Boiled Egg (1 large)",
                    nutrition: {
                        calories: 78,
                        protein: 6,
                        carbs: 0.6,
                        fat: 5
                    }
                },
                {
                    title: "Half Fried Egg (1 egg)",
                    nutrition: {
                        calories: 110,
                        protein: 7,
                        carbs: 1,
                        fat: 9
                    }
                },
                {
                    title: "Full Fried Egg (1 egg, sunny side up)",
                    nutrition: {
                        calories: 95,
                        protein: 6,
                        carbs: 1,
                        fat: 7
                    }
                },
                {
                    title: "Anda Bhurji (Scrambled Egg, 1 serving)",
                    nutrition: {
                        calories: 180,
                        protein: 12,
                        carbs: 3,
                        fat: 14
                    }
                },
                {
                    title: "Egg Omelette (2 eggs)",
                    nutrition: {
                        calories: 180,
                        protein: 12,
                        carbs: 2,
                        fat: 14
                    }
                },
                {
                    title: "Masala Omelette (2 eggs with veggies)",
                    nutrition: {
                        calories: 210,
                        protein: 14,
                        carbs: 5,
                        fat: 15
                    }
                },
                {
                    title: "Egg Curry (1 serving with gravy)",
                    nutrition: {
                        calories: 220,
                        protein: 14,
                        carbs: 8,
                        fat: 15
                    }
                },
                {
                    title: "Egg White Only (from 1 egg)",
                    nutrition: {
                        calories: 17,
                        protein: 3.6,
                        carbs: 0.2,
                        fat: 0
                    }
                },
                {
                    title: "Egg Yolk Only (from 1 egg)",
                    nutrition: {
                        calories: 55,
                        protein: 2.7,
                        carbs: 0.6,
                        fat: 4.5
                    }
                },
                {
                    title: "Egg Paratha (1 piece, stuffed)",
                    nutrition: {
                        calories: 380,
                        protein: 15,
                        carbs: 42,
                        fat: 18
                    }
                },
                {
                    title: "Anda Shami (1 piece)",
                    nutrition: {
                        calories: 150,
                        protein: 10,
                        carbs: 5,
                        fat: 11
                    }
                },
                {
                    title: "Salmon (100g grilled)",
                    nutrition: {
                        calories: 208,
                        protein: 20,
                        carbs: 0,
                        fat: 13
                    }
                },
                {
                    title: "White Bread (2 slices)",
                    nutrition: {
                        calories: 160,
                        protein: 6,
                        carbs: 30,
                        fat: 2
                    }
                },
                {
                    title: "White Bread (1 slice)",
                    nutrition: {
                        calories: 80,
                        protein: 3,
                        carbs: 15,
                        fat: 1
                    }
                },
                {
                    title: "Greek Yogurt (150g)",
                    nutrition: {
                        calories: 150,
                        protein: 15,
                        carbs: 8,
                        fat: 4
                    }
                },
                {
                    title: "Protein Bar",
                    nutrition: {
                        calories: 220,
                        protein: 20,
                        carbs: 22,
                        fat: 7
                    }
                },
                {
                    title: "Avocado (1/2 medium)",
                    nutrition: {
                        calories: 120,
                        protein: 1.5,
                        carbs: 6.5,
                        fat: 11
                    }
                },
                {
                    title: "Almonds (28g)",
                    nutrition: {
                        calories: 164,
                        protein: 6,
                        carbs: 6,
                        fat: 14
                    }
                },
                {
                    title: "Oatmeal (1 cup cooked)",
                    nutrition: {
                        calories: 158,
                        protein: 6,
                        carbs: 27,
                        fat: 3
                    }
                },
                {
                    title: "Sweet Potato (medium baked)",
                    nutrition: {
                        calories: 112,
                        protein: 2,
                        carbs: 26,
                        fat: 0
                    }
                },
                {
                    title: "Tuna (canned in water, 100g)",
                    nutrition: {
                        calories: 116,
                        protein: 26,
                        carbs: 0,
                        fat: 0.5
                    }
                }
            ],
            'custom': []
        };

        // Handle custom food addition
        document.getElementById('saveCustomFoodBtn').addEventListener('click', saveCustomFood);

        function saveCustomFood() {
            const foodName = document.getElementById('customFoodName').value.trim();
            const calories = parseInt(document.getElementById('customCalories').value);
            const protein = parseFloat(document.getElementById('customProtein').value) || 0;
            const carbs = parseFloat(document.getElementById('customCarbs').value) || 0;
            const fat = parseFloat(document.getElementById('customFat').value) || 0;
            const serving = document.getElementById('customServing').value.trim();
            const category = document.getElementById('customCategory').value;

            // Validation
            if (!foodName) {
                showNotification('Please enter a food name', 'error');
                return;
            }

            if (!calories || calories <= 0) {
                showNotification('Please enter valid calories', 'error');
                return;
            }

            // Format food name with serving size if provided
            const displayName = serving ? `${foodName} (${serving})` : foodName;

            // Show saving indicator
            const saveBtn = document.getElementById('saveCustomFoodBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            saveBtn.disabled = true;

            // First save to database
            const formData = new FormData();
            formData.append('save_custom_food', '1');
            formData.append('food_name', displayName);
            formData.append('calories', calories);
            formData.append('protein', protein);
            formData.append('carbs', carbs);
            formData.append('fat', fat);
            formData.append('category', category);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('customFoodModal'));
                        modal.hide();

                        // Reset form
                        document.getElementById('customFoodForm').reset();
                        document.getElementById('customCategory').value = 'custom';

                        // Save to localStorage for immediate access
                        saveCustomFoodToLocalDB({
                            title: displayName,
                            nutrition: {
                                calories,
                                protein,
                                carbs,
                                fat
                            },
                            category: category,
                            id: data.id // Save the database ID
                        });

                        // Show meal selection modal with the custom food
                        showMealSelection(displayName, calories, protein, carbs, fat);

                        showNotification('Custom food saved to your database!', 'success');
                    } else {
                        showNotification(data.message, 'error');
                        saveBtn.innerHTML = originalText;
                        saveBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error saving custom food: ' + error.message, 'error');
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        }

        function saveCustomFoodToLocalDB(food) {
            // Save custom foods to localStorage for future use
            let customFoods = JSON.parse(localStorage.getItem('customFoods') || '[]');

            // Check if food already exists
            const exists = customFoods.some(f => f.title === food.title);
            if (!exists) {
                customFoods.push(food);
                localStorage.setItem('customFoods', JSON.stringify(customFoods));

                // Add to in-memory database for current session
                if (!pakistaniFoodDatabase.custom) {
                    pakistaniFoodDatabase.custom = [];
                }
                pakistaniFoodDatabase.custom.push(food);

                // Update category button if needed
                updateCategoryButtons();
            }
        }

        // Load custom foods from database and localStorage
        function loadCustomFoods() {
            // First load from localStorage for immediate display
            const localCustomFoods = JSON.parse(localStorage.getItem('customFoods') || '[]');

            // Add localStorage foods to in-memory database
            if (localCustomFoods.length > 0) {
                if (!pakistaniFoodDatabase.custom) {
                    pakistaniFoodDatabase.custom = [];
                }

                // Only add foods that don't already exist
                localCustomFoods.forEach(food => {
                    const exists = pakistaniFoodDatabase.custom.some(f =>
                        f.title === food.title &&
                        f.nutrition.calories === food.nutrition.calories
                    );
                    if (!exists) {
                        pakistaniFoodDatabase.custom.push(food);
                    }
                });

                // Update category button
                updateCategoryButtons();

                // If custom category is active, refresh search results
                if (currentCategory === 'custom') {
                    displaySearchResults(pakistaniFoodDatabase.custom);
                }
            }

            // Then load from database to sync (updates will happen in background)
            fetch('?get_custom_foods=1')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.foods && data.foods.length > 0) {
                        let customFoods = JSON.parse(localStorage.getItem('customFoods') || '[]');
                        let updated = false;

                        data.foods.forEach(food => {
                            const customFood = {
                                title: food.food_name,
                                nutrition: {
                                    calories: parseFloat(food.calories),
                                    protein: parseFloat(food.protein),
                                    carbs: parseFloat(food.carbs),
                                    fat: parseFloat(food.fat)
                                },
                                category: food.category || 'custom',
                                id: food.id
                            };

                            // Add to in-memory database if not already there
                            if (!pakistaniFoodDatabase.custom) {
                                pakistaniFoodDatabase.custom = [];
                            }

                            const existsInMemory = pakistaniFoodDatabase.custom.some(f =>
                                f.title === customFood.title &&
                                f.nutrition.calories === customFood.nutrition.calories
                            );

                            if (!existsInMemory) {
                                pakistaniFoodDatabase.custom.push(customFood);
                                updated = true;
                            }

                            // Check if exists in localStorage
                            const existsInLocal = customFoods.some(f =>
                                f.title === customFood.title &&
                                f.nutrition.calories === customFood.nutrition.calories
                            );

                            if (!existsInLocal) {
                                customFoods.push(customFood);
                                updated = true;
                            }
                        });

                        // Save updated list to localStorage if changed
                        if (updated) {
                            localStorage.setItem('customFoods', JSON.stringify(customFoods));
                        }

                        // Update category button if needed
                        updateCategoryButtons();

                        // Refresh search results if custom category is selected
                        if (currentCategory === 'custom') {
                            displaySearchResults(pakistaniFoodDatabase.custom);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading custom foods from database:', error);
                    // Continue with localStorage data only
                });
        }

        function updateCategoryButtons() {
            const categoriesContainer = document.getElementById('foodCategories');
            let customBtn = categoriesContainer.querySelector('[data-category="custom"]');

            // Check if we have custom foods
            const hasCustomFoods = (pakistaniFoodDatabase.custom && pakistaniFoodDatabase.custom.length > 0) ||
                (localStorage.getItem('customFoods') && JSON.parse(localStorage.getItem('customFoods')).length > 0);

            if (!customBtn && hasCustomFoods) {
                // Create custom category button
                customBtn = document.createElement('button');
                customBtn.className = 'food-category-btn';
                customBtn.setAttribute('data-category', 'custom');
                customBtn.innerHTML = '<i class="fas fa-user-edit me-1"></i>My Foods';

                customBtn.addEventListener('click', function(e) {
                    const category = this.getAttribute('data-category');
                    searchByCategory(category, e);
                });

                categoriesContainer.appendChild(customBtn);
            } else if (customBtn && !hasCustomFoods) {
                // Remove custom category button if no custom foods
                customBtn.remove();
            }
        }

        // Get foods by category
        function getFoodsByCategory(category) {
            if (category === 'all') {
                return getAllFoods();
            }

            // For custom category, make sure it exists
            if (category === 'custom') {
                if (!pakistaniFoodDatabase.custom) {
                    pakistaniFoodDatabase.custom = [];
                }
                // Also check localStorage for any custom foods
                const localCustomFoods = JSON.parse(localStorage.getItem('customFoods') || '[]');
                if (localCustomFoods.length > 0 && pakistaniFoodDatabase.custom.length === 0) {
                    localCustomFoods.forEach(food => {
                        const exists = pakistaniFoodDatabase.custom.some(f =>
                            f.title === food.title &&
                            f.nutrition.calories === food.nutrition.calories
                        );
                        if (!exists) {
                            pakistaniFoodDatabase.custom.push(food);
                        }
                    });
                }
            }

            return pakistaniFoodDatabase[category] || [];
        }

        // Get all foods from all categories
        function getAllFoods() {
            let allFoods = [];
            for (const category in pakistaniFoodDatabase) {
                if (pakistaniFoodDatabase[category]) {
                    allFoods = allFoods.concat(pakistaniFoodDatabase[category]);
                }
            }
            return allFoods;
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadWaterIntake();
            loadCalorieGoal();
            updateWaterDisplay();
            updateCalorieDisplay();
            updateMacros();
            initializeMacroChart();
            renderWaterCups();
            updateCalorieProgressRing();
            // Load custom foods
            loadCustomFoods();

            // Add event listener for save button
            document.getElementById('saveMealPlanBtn').addEventListener('click', saveMealPlan);

            // Event listeners for search
            document.getElementById('searchBtn').addEventListener('click', function(e) {
                e.preventDefault();
                searchFood();
            });

            document.getElementById('foodSearch').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchFood();
                }
            });

            // Add event listeners for category buttons
            document.querySelectorAll('.food-category-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    const category = this.getAttribute('data-category');
                    searchByCategory(category, e);
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

            // Initialize Pakistan time display
            updatePakistanTime();
            setInterval(updatePakistanTime, 60000); // Update every minute
        });

        // Function to update Pakistan time display
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
        }

        // Search by category
        function searchByCategory(category, event) {
            currentCategory = category;

            // Update the category button activation
            document.querySelectorAll('.food-category-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            if (event) {
                event.target.classList.add('active');
            } else {
                const button = document.querySelector(`.food-category-btn[data-category="${category}"]`);
                if (button) {
                    button.classList.add('active');
                }
            }

            // If custom category doesn't exist in database yet, create it
            if (category === 'custom' && !pakistaniFoodDatabase.custom) {
                pakistaniFoodDatabase.custom = [];
            }

            // Load custom foods if selecting custom category
            if (category === 'custom') {
                // Ensure custom foods are loaded
                if (!pakistaniFoodDatabase.custom || pakistaniFoodDatabase.custom.length === 0) {
                    loadCustomFoods();
                }
            }

            // Search with current query or show all foods in category
            const query = document.getElementById('foodSearch').value.trim();
            if (query) {
                searchFood();
            } else {
                // If no query, show all foods in category
                const foods = getFoodsByCategory(category);
                if (foods && foods.length > 0) {
                    displaySearchResults(foods);
                } else if (category === 'custom') {
                    // Show empty state for custom foods
                    const results = document.getElementById('searchResults');
                    results.style.display = 'block';
                    results.innerHTML = `
            <div class="text-center p-3">
                <div class="mb-2"><i class="fas fa-user-edit fa-lg text-muted"></i></div>
                <div class="text-muted">No custom foods yet</div>
                <div class="text-muted small mt-1">Click "Add Custom" to create your first food</div>
                <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#customFoodModal">
                    <i class="fas fa-plus-circle me-2"></i>Add Custom Food
                </button>
            </div>
        `;
                }
            }
        }

        function searchFood() {
            const query = document.getElementById('foodSearch').value.trim();

            if (query.length < 2 && currentCategory === 'all') {
                showNotification('Please enter at least 2 characters to search', 'warning');
                return;
            }

            const results = document.getElementById('searchResults');
            results.style.display = 'block';

            // Show loading message
            results.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin me-2"></i>Searching...</div>';

            // Use setTimeout to simulate async search (and allow UI to update)
            setTimeout(() => {
                try {
                    const foundFoods = searchFoods(query);
                    displaySearchResults(foundFoods);

                    // If no results and user typed something, show helpful message
                    if ((!foundFoods || foundFoods.length === 0) && query.length > 0) {
                        results.innerHTML = `
                    <div class="text-center p-3">
                        <div class="mb-2"><i class="fas fa-search fa-lg text-muted"></i></div>
                        <div class="text-muted">No foods found for "${query}"</div>
                        <div class="text-muted small mt-1">Try a different search term or change category</div>
                    </div>
                `;
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    results.innerHTML = '<div class="text-center p-3 text-danger">Error searching foods. Please try again.</div>';
                }
            }, 100); // Reduced timeout for better responsiveness
        }

        // Search foods based on query and category
        function searchFoods(query) {
            const foods = getFoodsByCategory(currentCategory);

            if (!query || query.trim() === '') {
                // If no query, show all foods in current category
                return foods;
            }

            const searchTerm = query.toLowerCase().trim();

            return foods.filter(food => {
                // Check if title contains the search term
                const titleMatch = food.title.toLowerCase().includes(searchTerm);

                // You could also add search by food type/description if available
                return titleMatch;
            });
        }

        // Display search results
        function displaySearchResults(foods) {
            const results = document.getElementById('searchResults');
            results.innerHTML = '';

            if (!foods || foods.length === 0) {
                results.innerHTML = '<div class="text-center p-3 text-muted">No foods found. Try a different search or category.</div>';
                return;
            }

            foods.forEach(food => {
                const nutrition = food.nutrition;
                const resultDiv = document.createElement('div');
                resultDiv.className = 'food-result';
                resultDiv.innerHTML = `
                    <div>
                        <div class="food-name">${food.title}</div>
                        <div class="food-macros">
                            ${nutrition.calories} kcal • 
                            P: ${nutrition.protein}g • 
                            C: ${nutrition.carbs}g • 
                            F: ${nutrition.fat}g
                        </div>
                    </div>
                    <button class="add-food-btn" onclick="showMealSelection('${food.title.replace(/'/g, "\\'")}', ${nutrition.calories}, ${nutrition.protein}, ${nutrition.carbs}, ${nutrition.fat})">
                        <i class="fas fa-plus me-1"></i>Add
                    </button>
                `;
                results.appendChild(resultDiv);
            });
        }

        // Show meal selection modal
        function showMealSelection(title, calories, protein, carbs, fat) {
            currentFoodToAdd = {
                title,
                calories,
                protein,
                carbs,
                fat
            };
            selectedMealKey = null;

            const container = document.getElementById('mealOptionsContainer');
            container.innerHTML = '';

            // Meal data
            const mealsData = {
                'breakfast': {
                    name: 'Breakfast',
                    icon: 'fa-sun',
                    color: 'linear-gradient(135deg, #FF9A9E 0%, #FAD0C4 100%)'
                },
                'lunch': {
                    name: 'Lunch',
                    icon: 'fa-utensils',
                    color: 'linear-gradient(135deg, #A1C4FD 0%, #C2E9FB 100%)'
                },
                'dinner': {
                    name: 'Dinner',
                    icon: 'fa-moon',
                    color: 'linear-gradient(135deg, #84FAB0 0%, #8FD3F4 100%)'
                },
                'snack': {
                    name: 'Snacks',
                    icon: 'fa-apple-alt',
                    color: 'linear-gradient(135deg, #FBC2EB 0%, #A6C1EE 100%)'
                }
            };

            // Create meal options
            for (const [key, meal] of Object.entries(mealsData)) {
                const mealCalories = getMealCalories(key);
                const option = document.createElement('div');
                option.className = 'meal-option';
                option.dataset.mealKey = key;
                option.innerHTML = `
                    <div class="meal-option-icon" style="background: ${meal.color};">
                        <i class="fas ${meal.icon}"></i>
                    </div>
                    <div class="meal-option-info">
                        <div class="meal-option-name">${meal.name}</div>
                        <div class="meal-option-calories">${mealCalories} kcal currently</div>
                    </div>
                    <i class="fas fa-check text-primary" style="opacity: 0;"></i>
                `;

                option.addEventListener('click', function() {
                    document.querySelectorAll('.meal-option').forEach(opt => {
                        opt.classList.remove('selected');
                        opt.querySelector('.fa-check').style.opacity = '0';
                    });

                    this.classList.add('selected');
                    this.querySelector('.fa-check').style.opacity = '1';
                    selectedMealKey = this.dataset.mealKey;
                });

                container.appendChild(option);
            }

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('mealSelectModal'));
            modal.show();
        }

        // Confirm meal selection and add food
        document.getElementById('confirmMealSelection').addEventListener('click', function() {
            if (!selectedMealKey || !currentFoodToAdd) {
                alert('Please select a meal first');
                return;
            }

            addFoodToMeal(
                currentFoodToAdd.title,
                currentFoodToAdd.calories,
                currentFoodToAdd.protein,
                currentFoodToAdd.carbs,
                currentFoodToAdd.fat,
                selectedMealKey
            );

            // Hide modal
            bootstrap.Modal.getInstance(document.getElementById('mealSelectModal')).hide();

            // Clear current food
            currentFoodToAdd = null;
            selectedMealKey = null;
        });

        // Add food to specific meal
        function addFoodToMeal(title, calories, protein, carbs, fat, mealKey) {
            // Check if this food already exists in this meal
            const existingFoods = document.querySelectorAll(`#meal-${mealKey}-items .food-item`);
            for (const food of existingFoods) {
                const foodName = food.querySelector('.food-name').textContent.trim();

                if (foodName === title && food.dataset.id.startsWith('temp_')) {
                    if (confirm('This food is already in this meal. Do you want to add it again?')) {
                        // User wants to add duplicate
                        break;
                    } else {
                        return; // User canceled
                    }
                }
            }

            const container = document.getElementById(`meal-${mealKey}-items`);
            const emptyState = container.querySelector('.empty-state');

            if (emptyState) {
                emptyState.remove();
            }

            // For new items (not from DB), use a temporary ID
            const foodId = 'temp_' + Date.now();
            const foodItem = document.createElement('div');
            foodItem.className = 'food-item';
            foodItem.dataset.id = foodId;
            foodItem.dataset.calories = calories;
            foodItem.dataset.protein = protein;
            foodItem.dataset.carbs = carbs;
            foodItem.dataset.fat = fat;
            foodItem.dataset.mealType = mealKey;

            foodItem.innerHTML = `
                <div class="food-info">
                    <div class="food-name">${title}</div>
                    <div class="food-macros">
                        Protein: ${protein}g • Carbs: ${carbs}g • Fat: ${fat}g
                    </div>
                </div>
                <div class="food-calories">${calories} kcal</div>
                <button class="delete-btn" onclick="deleteMealItem('${foodId}', this)">
                    <i class="fas fa-times"></i>
                </button>
            `;

            container.appendChild(foodItem);

            // Add to temporary items array
            tempFoodItems.push(foodId);

            // Update totals
            totalCalories += calories;
            totalProtein += protein;
            totalCarbs += carbs;
            totalFat += fat;

            updateMealCalories(mealKey);
            updateCalorieDisplay();
            updateCalorieProgressRing();
            updateMacros();
            updateMacroChart();

            // Show success notification
            showNotification(`Added ${title} to meal`, 'success');

            // Close search results
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('foodSearch').value = '';
        }

        // Get current calories for a meal
        function getMealCalories(mealKey) {
            const container = document.getElementById(`meal-${mealKey}-items`);
            let total = 0;

            container.querySelectorAll('.food-item').forEach(item => {
                total += parseFloat(item.dataset.calories) || 0;
            });

            return total;
        }

        // Delete meal item from database and UI
        function deleteSavedMeal(mealId, btn) {
            if (!confirm('Are you sure you want to delete this meal?')) return;

            // Show loading
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            // Send delete request
            const formData = new FormData();
            formData.append('delete_meal', '1');
            formData.append('meal_id', mealId);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Remove from UI
                        const foodItem = btn.closest('.food-item');
                        const calories = parseFloat(foodItem.dataset.calories) || 0;
                        const protein = parseFloat(foodItem.dataset.protein) || 0;
                        const carbs = parseFloat(foodItem.dataset.carbs) || 0;
                        const fat = parseFloat(foodItem.dataset.fat) || 0;

                        // Find and remove from all sections
                        const mealIdAttr = `[data-id="${mealId}"]`;
                        document.querySelectorAll(mealIdAttr).forEach(item => item.remove());

                        // Update totals
                        totalCalories = Math.max(0, totalCalories - calories);
                        totalProtein = Math.max(0, totalProtein - protein);
                        totalCarbs = Math.max(0, totalCarbs - carbs);
                        totalFat = Math.max(0, totalFat - fat);

                        updateCalorieDisplay();
                        updateCalorieProgressRing();
                        updateMacros();
                        updateMacroChart();

                        // Check if all meals section is now empty
                        const allMealsContainer = document.getElementById('all-meals-items');
                        if (allMealsContainer.children.length === 0) {
                            allMealsContainer.innerHTML = `
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                    <h4>No meals saved yet</h4>
                                    <p>Search and add foods above to start tracking your nutrition</p>
                                </div>
                            `;
                        }

                        // Update individual meal sections
                        updateAllMealSections();

                        showNotification('Meal deleted successfully!', 'success');
                    } else {
                        showNotification(data.message || 'Error deleting meal', 'error');
                        btn.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error deleting meal: ' + error.message, 'error');
                    btn.innerHTML = originalHTML;
                });
        }

        // Update all meal sections
        function updateAllMealSections() {
            const mealKeys = ['breakfast', 'lunch', 'dinner', 'snack'];
            mealKeys.forEach(key => {
                updateMealCalories(key);
                const container = document.getElementById(`meal-${key}-items`);
                if (container.children.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <h4>No foods added yet</h4>
                            <p>Search and add foods above to this meal</p>
                        </div>
                    `;
                }
            });
        }

        // Delete meal item (handles both temporary and saved items)
        function deleteMealItem(mealId, btn) {
            if (!confirm('Are you sure you want to remove this food item?')) return;

            // Check if it's a temporary item or a saved item
            if (mealId.startsWith('temp_')) {
                // Handle temporary item
                const foodItem = btn.closest('.food-item');
                const calories = parseFloat(foodItem.dataset.calories) || 0;
                const protein = parseFloat(foodItem.dataset.protein) || 0;
                const carbs = parseFloat(foodItem.dataset.carbs) || 0;
                const fat = parseFloat(foodItem.dataset.fat) || 0;
                const mealKey = foodItem.dataset.mealType;

                // Remove from specific meal section
                foodItem.remove();

                // Remove from tempFoodItems array
                const index = tempFoodItems.indexOf(mealId);
                if (index > -1) {
                    tempFoodItems.splice(index, 1);
                }

                // Update totals
                totalCalories -= calories;
                totalProtein -= protein;
                totalCarbs -= carbs;
                totalFat -= fat;

                // Update meal calories if we found the meal
                if (mealKey) {
                    updateMealCalories(mealKey);

                    // Check if meal is now empty
                    const container = document.getElementById(`meal-${mealKey}-items`);
                    if (container.children.length === 0) {
                        container.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <h4>No foods added yet</h4>
                                <p>Search and add foods above to this meal</p>
                            </div>
                        `;
                    }
                }

                // Remove from "Today's Saved Meals" section if it exists there
                const allMealsItem = document.querySelector(`[data-id="${mealId}"]`);
                if (allMealsItem) {
                    allMealsItem.remove();
                }

                // Check if all meals section is now empty
                const allMealsContainer = document.getElementById('all-meals-items');
                if (allMealsContainer.children.length === 0) {
                    allMealsContainer.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h4>No meals saved yet</h4>
                            <p>Search and add foods above to start tracking your nutrition</p>
                        </div>
                    `;
                }

                updateCalorieDisplay();
                updateCalorieProgressRing();
                updateMacros();
                updateMacroChart();

                showNotification('Food item removed', 'info');
            } else {
                // Handle saved item - call deleteSavedMeal function
                deleteSavedMeal(mealId, btn);
            }
        }

        function updateMealCalories(mealKey) {
            const container = document.getElementById(`meal-${mealKey}-items`);
            const foods = container.querySelectorAll('.food-item');
            let mealCalories = 0;

            foods.forEach(food => {
                mealCalories += parseFloat(food.dataset.calories) || 0;
            });

            document.getElementById(`meal-${mealKey}-calories`).textContent = `${Math.round(mealCalories)} kcal`;
        }

        function updateCalorieDisplay() {
            document.getElementById('caloriesConsumed').textContent = Math.round(totalCalories);
            document.getElementById('total-calories').textContent = `${Math.round(totalCalories)} kcal`;
        }

        function updateCalorieProgressRing() {
            const progress = Math.min(100, (totalCalories / calorieGoal) * 100);
            const progressRing = document.getElementById('calorieProgress');
            const radius = 50;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (progress / 100) * circumference;

            progressRing.style.strokeDasharray = `${circumference} ${circumference}`;
            progressRing.style.strokeDashoffset = offset;

            document.getElementById('caloriePercent').textContent = `${Math.round(progress)}%`;
        }

        function updateMacros() {
            document.getElementById('proteinAmount').textContent = `${totalProtein.toFixed(1)}g`;
            document.getElementById('carbsAmount').textContent = `${totalCarbs.toFixed(1)}g`;
            document.getElementById('fatAmount').textContent = `${totalFat.toFixed(1)}g`;

            document.getElementById('proteinDisplay').textContent = `${totalProtein.toFixed(1)}g`;
            document.getElementById('carbsDisplay').textContent = `${totalCarbs.toFixed(1)}g`;
            document.getElementById('fatDisplay').textContent = `${totalFat.toFixed(1)}g`;
        }

        function initializeMacroChart() {
            const ctx = document.getElementById('macroChart').getContext('2d');
            macroChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Protein', 'Carbs', 'Fats'],
                    datasets: [{
                        data: [totalProtein, totalCarbs, totalFat],
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
                                    return `${context.label}: ${context.parsed.toFixed(1)}g`;
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }

        function updateMacroChart() {
            if (macroChart) {
                macroChart.data.datasets[0].data = [totalProtein, totalCarbs, totalFat];
                macroChart.update();
            }
        }

        // Save meal plan to database
        function saveMealPlan() {
            // Collect ONLY temporary food items (new items not yet in database)
            const mealsToSave = [];

            // Get all temporary items (those with IDs starting with "temp_")
            const tempItems = document.querySelectorAll('.food-item[data-id^="temp_"]');

            if (tempItems.length === 0) {
                showNotification('No new meals to save! Add some foods first.', 'warning');
                return;
            }

            tempItems.forEach(item => {
                const foodNameElement = item.querySelector('.food-name');
                const foodName = foodNameElement.textContent.trim();

                const calories = parseFloat(item.dataset.calories) || 0;
                const protein = parseFloat(item.dataset.protein) || 0;
                const carbs = parseFloat(item.dataset.carbs) || 0;
                const fat = parseFloat(item.dataset.fat) || 0;
                const mealKey = item.dataset.mealType;

                // Only add if we have valid data
                if (foodName && mealKey) {
                    mealsToSave.push({
                        meal_time: mealKey,
                        food_name: foodName,
                        calories: calories,
                        protein: protein,
                        carbs: carbs,
                        fat: fat
                    });
                }
            });

            // Show saving indicator
            const saveBtn = document.getElementById('saveMealPlanBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            saveBtn.disabled = true;

            // Prepare data for submission
            document.getElementById('mealsDataInput').value = JSON.stringify(mealsToSave);

            // Submit form via AJAX
            const form = document.getElementById('saveMealForm');
            const formData = new FormData(form);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showNotification(data.message, 'success');

                        // Reload the page to get fresh data from database
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);

                    } else {
                        showNotification(data.message, 'error');
                        saveBtn.innerHTML = originalText;
                        saveBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error saving meals: ' + error.message, 'error');
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        }

        function clearAllMeals() {
            if (!confirm('Are you sure you want to clear all temporary meals? This cannot be undone.')) return;

            // Get all temporary items
            const tempItems = document.querySelectorAll('.food-item[data-id^="temp_"]');

            if (tempItems.length === 0) {
                showNotification('No temporary meals to clear!', 'warning');
                return;
            }

            // Remove all temporary items
            tempItems.forEach(item => {
                const calories = parseFloat(item.dataset.calories) || 0;
                const protein = parseFloat(item.dataset.protein) || 0;
                const carbs = parseFloat(item.dataset.carbs) || 0;
                const fat = parseFloat(item.dataset.fat) || 0;

                // Update totals
                totalCalories -= calories;
                totalProtein -= protein;
                totalCarbs -= carbs;
                totalFat -= fat;

                item.remove();
            });

            // Clear tempFoodItems array
            tempFoodItems = [];

            // Update UI
            updateCalorieDisplay();
            updateCalorieProgressRing();
            updateMacros();
            updateMacroChart();

            // Update all meal sections
            updateAllMealSections();

            // Check if all meals section is now empty
            const allMealsContainer = document.getElementById('all-meals-items');
            const hasSavedMeals = allMealsContainer.querySelector('.food-item:not([data-id^="temp_"])');

            if (!hasSavedMeals) {
                allMealsContainer.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h4>No meals saved yet</h4>
                        <p>Search and add foods above to start tracking your nutrition</p>
                    </div>
                `;
            }

            showNotification('All temporary meals cleared!', 'info');
        }

        // Utility functions
        function showNotification(message, type = 'info') {
            // Remove any existing notifications
            document.querySelectorAll('.alert-notification').forEach(n => n.remove());

            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed alert-notification`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        function adjustCalorieGoal(amount) {
            calorieGoal = Math.max(1000, Math.min(5000, calorieGoal + amount));
            document.getElementById('calorieGoal').textContent = calorieGoal;
            updateCalorieProgressRing();
            saveCalorieGoal();
        }

        function renderWaterCups() {
            const container = document.getElementById('waterCups');
            container.innerHTML = '';

            for (let i = 0; i < maxWater; i++) {
                const cup = document.createElement('div');
                cup.className = `water-cup ${i < waterGlasses ? 'filled' : ''}`;
                cup.innerHTML = i < waterGlasses ? '<i class="fas fa-tint"></i>' : '<i class="fas fa-glass-empty"></i>';
                cup.onclick = () => {
                    waterGlasses = i + 1;
                    renderWaterCups();
                    updateWaterDisplay();
                    saveWaterIntake();
                };
                container.appendChild(cup);
            }
        }

        function adjustWater(amount) {
            waterGlasses = Math.max(0, Math.min(maxWater, waterGlasses + amount));
            renderWaterCups();
            updateWaterDisplay();
            saveWaterIntake();
        }

        function updateWaterDisplay() {
            document.getElementById('waterAmount').textContent = `${waterGlasses}/${maxWater}`;
        }

        function simulateBarcodeScan() {
            // Add some quick healthy options
            const quickFoods = [{
                    name: "Protein Shake (1 scoop)",
                    calories: 120,
                    protein: 24,
                    carbs: 3,
                    fat: 1
                },
                {
                    name: "Boiled Egg (1 large)",
                    calories: 78,
                    protein: 6,
                    carbs: 0.6,
                    fat: 5
                },
                {
                    name: "Greek Yogurt (150g)",
                    calories: 150,
                    protein: 15,
                    carbs: 8,
                    fat: 4
                },
                {
                    name: "Apple (medium)",
                    calories: 95,
                    protein: 0.5,
                    carbs: 25,
                    fat: 0.3
                }
            ];

            const randomFood = quickFoods[Math.floor(Math.random() * quickFoods.length)];

            // Directly ask for meal selection
            showMealSelection(randomFood.name, randomFood.calories, randomFood.protein, randomFood.carbs, randomFood.fat);
        }

        function saveWaterIntake() {
            localStorage.setItem('water_<?php echo $today; ?>', waterGlasses);
        }

        function loadWaterIntake() {
            const saved = localStorage.getItem('water_<?php echo $today; ?>');
            if (saved !== null) {
                waterGlasses = parseInt(saved);
            }
        }

        function saveCalorieGoal() {
            localStorage.setItem('calorieGoal', calorieGoal);
        }

        function loadCalorieGoal() {
            const saved = localStorage.getItem('calorieGoal');
            if (saved !== null) {
                calorieGoal = parseInt(saved);
                document.getElementById('calorieGoal').textContent = calorieGoal;
            }
        }
    </script>
</body>

</html>