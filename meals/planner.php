<?php
// Start output buffering to catch any stray output
ob_start();

require '../includes/functions.php';
require_login();

$today = date('Y-m-d');

require '../includes/db.php';
$user_id = get_user_id();

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

// Get existing meals for display (only for GET requests) - FIXED: Removed created_at
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
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.8rem 1.8rem;
            border-radius: 25px;
            font-weight: 600;
        }

        .date-display i {
            color: var(--accent);
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
            top: 11%;
            left: 11%;
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

        /* Search Results */
        .search-results {
            max-height: 300px;
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
                <span><?php echo date('F j, Y'); ?></span>
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
                        <div class="stats-goal">Goal: <span id="calorieGoal">2000</span> kcal</div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-drumstick-bite"></i>
                        </div>
                        <div class="stats-value" id="proteinAmount"><?php echo $totalProtein; ?>g</div>
                        <div class="stats-label">Protein</div>
                        <div class="stats-goal">Essential for muscle</div>
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
                            <div class="progress-percent" id="caloriePercent"><?php echo round(($totalCalories / 2000) * 100); ?>%</div>
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
                    <h5><i class="fas fa-search me-2"></i>Search & Add Food</h5>
                    <div class="search-input-group">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="foodSearch" class="search-input" placeholder="e.g., chicken breast, apple, rice...">
                    </div>
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-primary flex-grow-1" id="searchBtn">
                            <i class="fas fa-search me-2"></i>Search Food
                        </button>
                        <button class="barcode-simulator" onclick="simulateBarcodeScan()">
                            <i class="fas fa-barcode me-2"></i>Barcode Scan
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
        // Global variables - INITIALIZE WITH PHP DATA
        let totalCalories = <?php echo $totalCalories; ?>;
        let totalProtein = <?php echo $totalProtein; ?>;
        let totalCarbs = <?php echo $totalCarbs; ?>;
        let totalFat = <?php echo $totalFat; ?>;
        let waterGlasses = 0;
        const maxWater = 8;
        let calorieGoal = 2000;
        let macroChart = null;
        let selectedMealKey = null;
        let currentFoodToAdd = null;
        let tempFoodItems = []; // Track temporary food items

        // Store the meals from database in JavaScript
        const savedMealsFromDB = <?php echo json_encode($existingMeals); ?>;

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

        // Initialize page - LOAD SAVED MEALS INTO JAVASCRIPT STATE
        document.addEventListener('DOMContentLoaded', function() {
            loadWaterIntake();
            loadCalorieGoal();
            updateWaterDisplay();
            updateCalorieDisplay();
            updateMacros();
            initializeMacroChart();
            renderWaterCups();

            // Update progress ring initially
            updateCalorieProgressRing();

            // Add event listener for save button
            document.getElementById('saveMealPlanBtn').addEventListener('click', saveMealPlan);

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
        });

        // Food database
        const foodDatabase = [{
                title: "Chicken Breast (100g)",
                nutrition: {
                    calories: 165,
                    protein: 31,
                    carbs: 0,
                    fat: 3.6
                }
            },
            {
                title: "Brown Rice (1 cup)",
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
                title: "Egg (large)",
                nutrition: {
                    calories: 78,
                    protein: 6,
                    carbs: 0.6,
                    fat: 5
                }
            },
            {
                title: "Salmon (100g)",
                nutrition: {
                    calories: 208,
                    protein: 20,
                    carbs: 0,
                    fat: 13
                }
            },
            {
                title: "Apple (medium)",
                nutrition: {
                    calories: 95,
                    protein: 0.5,
                    carbs: 25,
                    fat: 0.3
                }
            },
            {
                title: "Banana (medium)",
                nutrition: {
                    calories: 105,
                    protein: 1,
                    carbs: 27,
                    fat: 0
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
                title: "Avocado (medium)",
                nutrition: {
                    calories: 240,
                    protein: 3,
                    carbs: 13,
                    fat: 22
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
                title: "Oatmeal (1 cup)",
                nutrition: {
                    calories: 158,
                    protein: 6,
                    carbs: 27,
                    fat: 3
                }
            },
            {
                title: "Sweet Potato (medium)",
                nutrition: {
                    calories: 112,
                    protein: 2,
                    carbs: 26,
                    fat: 0
                }
            }
        ];

        // Search functionality
        document.getElementById('searchBtn').addEventListener('click', searchFood);
        document.getElementById('foodSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') searchFood();
        });

        function searchFood() {
            const query = document.getElementById('foodSearch').value.trim();
            if (query.length < 2) {
                alert('Please enter at least 2 characters to search');
                return;
            }

            const results = document.getElementById('searchResults');
            results.style.display = 'block';
            results.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin me-2"></i>Searching...</div>';

            setTimeout(() => {
                displaySearchResults(searchFoods(query));
            }, 500);
        }

        function searchFoods(query) {
            return foodDatabase.filter(food =>
                food.title.toLowerCase().includes(query.toLowerCase())
            );
        }

        function displaySearchResults(foods) {
            const results = document.getElementById('searchResults');
            results.innerHTML = '';

            if (!foods || foods.length === 0) {
                results.innerHTML = '<div class="text-center p-3 text-muted">No foods found. Try a different search.</div>';
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
                    // Remove selection from all options
                    document.querySelectorAll('.meal-option').forEach(opt => {
                        opt.classList.remove('selected');
                        opt.querySelector('.fa-check').style.opacity = '0';
                    });

                    // Select this option
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

            // DO NOT add to "Today's Saved Meals" section - that's only for saved items

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
            showNotification(`Added ${title} to ${mealsData[mealKey].name}`, 'success');

            // Close search results
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('foodSearch').value = '';
        }

        // Get current calories for a meal (includes both saved and temporary items)
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
            if (!confirm('Are you sure you want to delete this meal from the database?')) return;

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

                        showNotification('Meal deleted from database!', 'success');
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

            document.getElementById(`meal-${mealKey}-calories`).textContent = `${mealCalories} kcal`;
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
                                    return `${context.label}: ${context.parsed}g`;
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

        // Save meal plan to database - FIXED: Now only saves temporary items, doesn't duplicate existing ones
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

                        // IMPORTANT: Reload the page to get fresh data from database
                        // This ensures no duplicates
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

        // Helper function to update temporary IDs after successful save
        function updateTempIdsAfterSave(data) {
            // This would be called if you wanted to update the temporary IDs
            // to actual database IDs after saving. For now, we'll just reload.

            // For a better UX, you could update the IDs in place, but 
            // reloading is simpler and ensures consistency
            setTimeout(() => {
                window.location.reload();
            }, 1500);
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
            const barcodeFoods = [{
                    name: "Greek Yogurt (150g)",
                    calories: 150,
                    protein: 15,
                    carbs: 8,
                    fat: 4
                },
                {
                    name: "Protein Bar",
                    calories: 220,
                    protein: 20,
                    carbs: 22,
                    fat: 7
                },
                {
                    name: "Chicken Breast (100g)",
                    calories: 165,
                    protein: 31,
                    carbs: 0,
                    fat: 3.6
                },
                {
                    name: "Almond Milk (250ml)",
                    calories: 30,
                    protein: 1,
                    carbs: 1,
                    fat: 2.5
                },
                {
                    name: "Whole Wheat Bread (slice)",
                    calories: 80,
                    protein: 3,
                    carbs: 15,
                    fat: 1
                }
            ];

            const randomFood = barcodeFoods[Math.floor(Math.random() * barcodeFoods.length)];

            // Directly ask for meal selection after barcode scan
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