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

        .date-display {
            background: rgba(255, 255, 255, 0.15);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            margin-top: 1rem;
        }

        .date-display i {
            margin-right: 10px;
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

        .progress-ring {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }

        .progress-ring-circle {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .progress-ring-bg {
            fill: none;
            stroke: #e9ecef;
            stroke-width: 10;
        }

        .progress-ring-fill {
            fill: none;
            stroke: var(--primary-color);
            stroke-width: 10;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease-in-out;
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .progress-percent {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .progress-label {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .macronutrient-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }

        .macronutrient-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 12px;
            background: #f8f9fa;
            transition: all 0.3s;
        }

        .macronutrient-item:hover {
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
            margin-bottom: 0.25rem;
        }

        .macro-value {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .macro-amount {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .water-tracker {
            background: linear-gradient(135deg, #a1c4fd, #c2e9fb);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            color: white;
            margin-bottom: 2rem;
        }

        .water-cups {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 1rem;
        }

        .water-cup {
            width: 60px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .water-cup.filled {
            background: rgba(255, 255, 255, 0.8);
            color: #3a86ff;
        }

        .water-cup:hover {
            transform: scale(1.1);
        }

        .meal-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 1.5rem;
        }

        .meal-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .meal-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .meal-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            flex: 1;
        }

        .meal-calories {
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .food-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 0.75rem;
            transition: all 0.3s;
            position: relative;
        }

        .food-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .food-info {
            flex: 1;
        }

        .food-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .food-macros {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .food-calories {
            font-weight: 700;
            color: var(--primary-color);
            margin-left: 1rem;
        }

        .delete-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            margin-left: 1rem;
            cursor: pointer;
            border: none;
        }

        .delete-btn:hover {
            background: #ff5252;
            transform: scale(1.1);
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
            transition: all 0.3s;
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

        .search-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }

        .search-input-group {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 134, 255, 0.25);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .search-results {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 1rem;
            border-radius: 10px;
            background: #f8f9fa;
            padding: 1rem;
        }

        .food-result {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            border-radius: 8px;
            background: white;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
            cursor: pointer;
        }

        .food-result:hover {
            background: #e9ecef;
        }

        .add-food-btn {
            background: var(--gradient-success);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }

        .add-food-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(56, 176, 0, 0.3);
        }

        .barcode-simulator {
            background: var(--gradient-warning);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .barcode-simulator:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 190, 11, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 15px rgba(58, 134, 255, 0.3);
        }

        .btn-danger {
            background: var(--gradient-secondary);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 15px rgba(255, 0, 110, 0.3);
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

        .empty-meal {
            text-align: center;
            padding: 2rem 1rem;
            color: #6c757d;
        }

        .empty-meal i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        /* Modal Styling */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
        }

        .meal-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .meal-option:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .meal-option.selected {
            border-color: var(--primary-color);
            background: rgba(58, 134, 255, 0.1);
        }

        .meal-option-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
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
            margin-bottom: 0.25rem;
        }

        .meal-option-calories {
            color: #6c757d;
            font-size: 0.9rem;
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

            .water-cup {
                width: 50px;
                height: 70px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .delete-meal-btn {
                opacity: 1;
            }
        }

        .macro-chart-container {
            height: 200px;
            margin-top: 1rem;
        }

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

        /* ============================================
   PREMIUM MOBILE RESPONSIVE DESIGN - MEAL PLANNER
   ============================================ */

/* Base mobile styles */
@media (max-width: 767.98px) {
    /* Reset container spacing */
    .container.mt-4 {
        padding-left: 0 !important;
        padding-right: 0 !important;
        max-width: 100%;
        margin-top: 0 !important;
    }
    
    /* Body background and padding */
    body {
        background: #f5f9ff;
        padding-bottom: 70px; /* Space for mobile nav */
    }
    
    /* Mobile Navigation - Enhanced */
    .mobile-nav {
        display: flex !important;
        background: white;
        border-radius: 25px 25px 0 0;
        padding: 0.75rem 0.5rem;
        box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.15);
        position: fixed;
        bottom: 0;
        left: 0.5rem;
        right: 0.5rem;
        margin: 0 auto;
        max-width: 500px;
        z-index: 1000;
    }
    
    .mobile-nav-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: #94a3b8;
        font-size: 0.75rem;
        padding: 0.5rem 0.25rem;
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .mobile-nav-item:hover,
    .mobile-nav-item.active {
        color: #3a86ff;
        background: rgba(58, 134, 255, 0.08);
        transform: translateY(-3px);
    }
    
    .mobile-nav-item i {
        font-size: 1.3rem;
        margin-bottom: 0.25rem;
        transition: all 0.3s ease;
    }
    
    .mobile-nav-item.active i {
        transform: scale(1.1);
    }
    
    /* Hide desktop navbar on mobile */
    .navbar-nav {
        display: none !important;
    }
    
    .navbar-toggler {
        border: none;
        padding: 0.5rem;
    }
    
    .navbar-toggler:focus {
        box-shadow: none;
    }
    
    /* Page Header - Redesigned for mobile */
    .page-header {
        border-radius: 0 0 32px 32px !important;
        padding: 1.5rem !important;
        margin: 0 0 1.5rem 0 !important;
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        display: none;
    }
    
    .page-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 40px;
        background: linear-gradient(to top, rgba(0,0,0,0.05), transparent);
    }
    
    .page-header h1 {
        font-size: 1.5rem !important;
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }
    
    .page-header p {
        font-size: 0.95rem;
        margin-bottom: 0;
    }
    
    /* Date Display */
    .date-display {
        padding: 0.5rem 1rem !important;
        font-size: 0.9rem !important;
        margin-top: 0.75rem !important;
    }
    
    .date-display i {
        font-size: 0.9rem !important;
        margin-right: 0.5rem !important;
    }
    
    /* Stats Cards - Mobile Optimized */
    .row.mb-4 {
        margin: 0 0.75rem 1rem 0.75rem !important;
        padding: 0 !important;
    }
    
    .col-md-3.col-sm-6.mb-4 {
        width: 50% !important;
        padding: 0.25rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    .stats-card {
        padding: 1rem !important;
        border-radius: 16px !important;
        margin-bottom: 0.5rem !important;
    }
    
    .stats-icon {
        width: 45px !important;
        height: 45px !important;
        font-size: 1.2rem !important;
        margin-bottom: 0.75rem !important;
        border-radius: 12px !important;
    }
    
    .stats-value {
        font-size: 1.5rem !important;
        margin-bottom: 0.125rem !important;
    }
    
    .stats-label {
        font-size: 0.8rem !important;
    }
    
    .stats-card .small.text-muted {
        font-size: 0.75rem !important;
        margin-top: 0.25rem !important;
    }
    
    /* Macronutrient Cards */
    .macronutrient-card {
        margin: 0 0.75rem 1rem 0.75rem !important;
        padding: 1.25rem !important;
        border-radius: 20px !important;
    }
    
    .macronutrient-card h5 {
        font-size: 1.1rem !important;
        margin-bottom: 1rem !important;
    }
    
    .macronutrient-card h5 i {
        font-size: 1rem !important;
        margin-right: 0.5rem !important;
    }
    
    /* Progress Ring */
    .progress-ring {
        width: 100px !important;
        height: 100px !important;
        margin: 0 auto 1rem auto !important;
    }
    
    .progress-percent {
        font-size: 1.5rem !important;
    }
    
    .progress-label {
        font-size: 0.7rem !important;
    }
    
    .macronutrient-card .text-center .btn {
        padding: 0.375rem 0.75rem !important;
        font-size: 0.8rem !important;
    }
    
    /* Macronutrient Items */
    .macronutrient-item {
        padding: 0.75rem !important;
        margin-bottom: 0.75rem !important;
        border-radius: 10px !important;
    }
    
    .macro-color {
        width: 10px !important;
        height: 10px !important;
        margin-right: 10px !important;
    }
    
    .macro-name {
        font-size: 0.9rem !important;
    }
    
    .macro-value {
        font-size: 0.8rem !important;
    }
    
    .macro-amount {
        font-size: 1rem !important;
    }
    
    /* Chart Container */
    .macro-chart-container {
        height: 150px !important;
        margin-top: 0.5rem !important;
    }
    
    /* Water Tracker */
    .water-tracker {
        margin: 0 0.75rem 1rem 0.75rem !important;
        padding: 1.25rem !important;
        border-radius: 20px !important;
    }
    
    .water-tracker h5 {
        font-size: 1.1rem !important;
        margin-bottom: 1rem !important;
    }
    
    .water-cups {
        gap: 6px !important;
        justify-content: center !important;
    }
    
    .water-cup {
        width: 45px !important;
        height: 60px !important;
        font-size: 1.2rem !important;
        border-radius: 8px !important;
    }
    
    /* Search Section */
    .search-section {
        margin: 0 0.75rem 1rem 0.75rem !important;
        padding: 1.25rem !important;
        border-radius: 20px !important;
    }
    
    .search-section h5 {
        font-size: 1.1rem !important;
        margin-bottom: 1rem !important;
    }
    
    .search-input {
        padding: 0.875rem 0.875rem 0.875rem 2.5rem !important;
        font-size: 0.9rem !important;
        border-radius: 10px !important;
    }
    
    .search-icon {
        left: 0.875rem !important;
        font-size: 0.9rem !important;
    }
    
    /* Search Buttons */
    .d-flex.gap-2.mb-3 {
        flex-direction: column !important;
        gap: 0.75rem !important;
    }
    
    #searchBtn,
    .barcode-simulator {
        padding: 0.875rem !important;
        font-size: 0.95rem !important;
        border-radius: 12px !important;
        min-height: 52px !important;
    }
    
    .barcode-simulator {
        justify-content: center !important;
    }
    
    /* Search Results */
    .search-results {
        max-height: 200px !important;
        padding: 0.75rem !important;
        border-radius: 10px !important;
    }
    
    .food-result {
        padding: 0.75rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    .food-result .food-name {
        font-size: 0.9rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .food-result .food-macros {
        font-size: 0.8rem !important;
    }
    
    .add-food-btn {
        padding: 0.375rem 0.75rem !important;
        font-size: 0.8rem !important;
        border-radius: 8px !important;
    }
    
    /* Meal Sections */
    .meal-section {
        margin: 0 0.75rem 1rem 0.75rem !important;
        padding: 1.25rem !important;
        border-radius: 20px !important;
    }
    
    .meal-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.75rem;
        margin-bottom: 1.25rem !important;
        padding-bottom: 1rem !important;
    }
    
    .meal-icon {
        width: 45px !important;
        height: 45px !important;
        font-size: 1.2rem !important;
        margin-right: 0.75rem !important;
        border-radius: 10px !important;
    }
    
    .meal-title {
        font-size: 1.1rem !important;
        flex: 0 0 100% !important;
        margin-bottom: 0.5rem !important;
    }
    
    .meal-calories {
        padding: 0.375rem 0.875rem !important;
        font-size: 0.9rem !important;
        border-radius: 15px !important;
    }
    
    /* Food Items */
    .food-item {
        padding: 0.875rem !important;
        margin-bottom: 0.75rem !important;
        border-radius: 10px !important;
    }
    
    .food-name {
        font-size: 0.95rem !important;
        margin-bottom: 0.375rem !important;
    }
    
    .food-macros {
        font-size: 0.8rem !important;
    }
    
    .food-calories {
        font-size: 0.9rem !important;
        margin-left: 0.75rem !important;
        font-weight: 600 !important;
    }
    
    /* Delete Buttons */
    .delete-btn {
        width: 32px !important;
        height: 32px !important;
        margin-left: 0.75rem !important;
        font-size: 0.8rem !important;
    }
    
    .delete-meal-btn {
        opacity: 1 !important;
        width: 28px !important;
        height: 28px !important;
        font-size: 0.8rem !important;
        top: 8px !important;
        right: 8px !important;
    }
    
    /* Empty States */
    .empty-meal {
        padding: 1.5rem 0.5rem !important;
    }
    
    .empty-meal i {
        font-size: 2.5rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .empty-meal p {
        font-size: 1rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .empty-meal .text-muted {
        font-size: 0.85rem !important;
    }
    
    /* Action Buttons */
    .action-buttons {
        margin: 0 0.75rem 2rem 0.75rem !important;
        padding: 0 !important;
        flex-direction: column !important;
        gap: 0.75rem !important;
    }
    
    .btn-primary,
    .btn-danger {
        padding: 1rem !important;
        font-size: 1rem !important;
        border-radius: 12px !important;
        min-height: 56px !important;
    }
    
    .btn-danger {
        order: 2;
    }
    
    /* Modal Adjustments */
    .modal-content {
        margin: 1rem !important;
        border-radius: 20px !important;
    }
    
    .meal-option {
        padding: 0.875rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .meal-option-icon {
        width: 45px !important;
        height: 45px !important;
        font-size: 1.1rem !important;
        margin-right: 0.75rem !important;
    }
    
    .meal-option-name {
        font-size: 0.95rem !important;
    }
    
    .meal-option-calories {
        font-size: 0.8rem !important;
    }
    
    /* Meal Badges */
    .meal-badge {
        padding: 0.2rem 0.5rem !important;
        font-size: 0.7rem !important;
        margin-right: 0.375rem !important;
    }
    
    /* Animation for mobile */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .stats-card,
    .macronutrient-card,
    .water-tracker,
    .search-section,
    .meal-section {
        animation: fadeInUp 0.5s ease-out;
    }
    
    /* Stagger animations */
    .stats-card:nth-child(1) { animation-delay: 0.1s; }
    .stats-card:nth-child(2) { animation-delay: 0.15s; }
    .stats-card:nth-child(3) { animation-delay: 0.2s; }
    .stats-card:nth-child(4) { animation-delay: 0.25s; }
    .macronutrient-card { animation-delay: 0.3s; }
    .water-tracker { animation-delay: 0.35s; }
    .search-section { animation-delay: 0.4s; }
    
    /* Food item entrance animation */
    @keyframes foodItemEntrance {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .food-item {
        animation: foodItemEntrance 0.3s ease-out;
    }
}

/* Extra small devices (phones under 400px) */
@media (max-width: 399.98px) {
    .mobile-nav {
        left: 0.25rem;
        right: 0.25rem;
        padding: 0.5rem;
    }
    
    .page-header {
        padding: 1.25rem !important;
    }
    
    .page-header h1 {
        font-size: 1.3rem !important;
    }
    
    .page-header p {
        font-size: 0.9rem;
    }
    
    .col-md-3.col-sm-6.mb-4 {
        width: 50% !important;
        padding: 0.125rem !important;
    }
    
    .stats-card {
        padding: 0.875rem !important;
    }
    
    .stats-value {
        font-size: 1.3rem !important;
    }
    
    .stats-label {
        font-size: 0.75rem !important;
    }
    
    .macronutrient-card,
    .water-tracker,
    .search-section,
    .meal-section {
        margin: 0 0.5rem 0.75rem 0.5rem !important;
        padding: 1rem !important;
    }
    
    .water-cup {
        width: 40px !important;
        height: 55px !important;
        font-size: 1.1rem !important;
    }
    
    .action-buttons {
        margin: 0 0.5rem 1.5rem 0.5rem !important;
    }
    
    .btn-primary,
    .btn-danger {
        padding: 0.875rem !important;
        font-size: 0.95rem !important;
        min-height: 52px !important;
    }
    
    .food-name {
        font-size: 0.9rem !important;
    }
    
    .food-macros {
        font-size: 0.75rem !important;
    }
    
    .empty-meal {
        padding: 1rem 0.5rem !important;
    }
    
    .empty-meal i {
        font-size: 2rem !important;
    }
}

/* Tablet portrait mode (768px - 991px) */
@media (min-width: 768px) and (max-width: 991.98px) {
    .container.mt-4 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    .mobile-nav {
        display: flex !important;
        left: 1rem;
        right: 1rem;
    }
    
    .navbar-nav {
        display: none !important;
    }
    
    .col-md-3.col-sm-6.mb-4 {
        width: 50% !important;
    }
    
    .food-item:hover .delete-meal-btn {
        opacity: 1 !important;
    }
}

/* Landscape mode optimization */
@media (max-height: 600px) and (orientation: landscape) {
    .mobile-nav {
        padding: 0.5rem;
    }
    
    .mobile-nav-item {
        font-size: 0.7rem;
        padding: 0.25rem 0.125rem;
    }
    
    .mobile-nav-item i {
        font-size: 1.1rem;
        margin-bottom: 0.125rem;
    }
    
    .page-header {
        padding: 1rem !important;
        margin-bottom: 1rem !important;
    }
    
    .page-header h1 {
        font-size: 1.2rem !important;
    }
    
    .stats-card,
    .macronutrient-card,
    .water-tracker,
    .search-section,
    .meal-section {
        margin-bottom: 0.75rem !important;
        padding: 1rem !important;
    }
    
    .stats-icon {
        width: 40px !important;
        height: 40px !important;
        font-size: 1rem !important;
    }
    
    .stats-value {
        font-size: 1.2rem !important;
    }
    
    .water-cup {
        width: 35px !important;
        height: 50px !important;
        font-size: 1rem !important;
    }
    
    .btn-primary,
    .btn-danger {
        min-height: 52px !important;
        padding: 0.75rem !important;
    }
    
    .food-item {
        padding: 0.75rem !important;
        margin-bottom: 0.5rem !important;
    }
}

/* iPhone notch and safe area support */
@supports (padding: max(0px)) {
    .container.mt-4 {
        padding-left: max(0.75rem, env(safe-area-inset-left)) !important;
        padding-right: max(0.75rem, env(safe-area-inset-right)) !important;
    }
    
    .mobile-nav {
        padding-bottom: max(0.75rem, env(safe-area-inset-bottom)) !important;
    }
    
    .page-header {
        padding-top: max(1.5rem, env(safe-area-inset-top)) !important;
    }
}

/* Loading animations */
@keyframes buttonPulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(0.98);
    }
}

#saveMealPlanBtn:disabled {
    animation: buttonPulse 1s infinite;
}

/* Touch feedback for mobile */
@media (hover: none) and (pointer: coarse) {
    .stats-card:hover,
    .macronutrient-item:hover,
    .food-result:hover,
    .meal-option:hover {
        transform: none !important;
    }
    
    .stats-card:active,
    .macronutrient-item:active,
    .food-result:active,
    .meal-option:active {
        transform: scale(0.98) !important;
    }
    
    .water-cup:active {
        transform: scale(1.1) !important;
    }
    
    .btn-primary:hover,
    .btn-danger:hover,
    .add-food-btn:hover,
    .barcode-simulator:hover {
        transform: none !important;
    }
    
    .btn-primary:active,
    .btn-danger:active,
    .add-food-btn:active,
    .barcode-simulator:active {
        transform: scale(0.98) !important;
    }
    
    .delete-btn:active,
    .delete-meal-btn:active {
        transform: scale(1.1) !important;
    }
}

/* Custom scrollbar for mobile webkit */
@media (max-width: 767.98px) {
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #3a86ff, #8338ec);
        border-radius: 10px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    @media (max-width: 767.98px) {
        body {
            background: #121826;
            color: #e2e8f0;
        }
        
        .stats-card,
        .macronutrient-card,
        .search-section,
        .meal-section {
            background: #1e293b;
            color: #e2e8f0;
            border-color: #374151 !important;
        }
        
        .mobile-nav {
            background: #1e293b;
        }
        
        .mobile-nav-item {
            color: #94a3b8;
        }
        
        .mobile-nav-item.active {
            color: #3a86ff;
            background: rgba(58, 134, 255, 0.15);
        }
        
        .stats-card {
            background: #2d3748 !important;
        }
        
        .macronutrient-item,
        .food-item,
        .food-result,
        .meal-option {
            background: #2d3748 !important;
        }
        
        .macronutrient-item:hover,
        .food-item:hover,
        .food-result:hover,
        .meal-option:hover {
            background: #374151 !important;
        }
        
        .text-muted {
            color: #94a3b8 !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #3730a3) !important;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #991b1b) !important;
        }
        
        .water-tracker {
            background: linear-gradient(135deg, #1e3a8a, #1e40af) !important;
        }
        
        .water-cup {
            background: rgba(255, 255, 255, 0.1) !important;
        }
        
        .water-cup.filled {
            background: rgba(59, 130, 246, 0.8) !important;
        }
        
        .search-input {
            background: #2d3748 !important;
            border-color: #374151 !important;
            color: #e2e8f0 !important;
        }
        
        .search-results {
            background: #2d3748 !important;
        }
        
        .progress-ring-bg {
            stroke: #374151 !important;
        }
        
        .progress-percent {
            color: #3a86ff !important;
        }
        
        .delete-btn {
            background: #dc2626 !important;
        }
        
        .add-food-btn {
            background: linear-gradient(135deg, #059669, #065f46) !important;
        }
        
        .barcode-simulator {
            background: linear-gradient(135deg, #d97706, #92400e) !important;
        }
    }
}

/* Enhanced animations for mobile */
@keyframes slideInFromLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInFromRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Section animations */
.macronutrient-card,
.water-tracker {
    animation: slideInFromLeft 0.5s ease-out;
}

.search-section,
.meal-section {
    animation: slideInFromRight 0.5s ease-out;
}

/* Food item animations */
@keyframes foodItemAdd {
    0% {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }
    70% {
        transform: translateY(2px);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.food-item:last-child {
    animation: foodItemAdd 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Form focus states */
.search-input:focus,
.btn-primary:focus,
.btn-danger:focus,
.add-food-btn:focus,
.barcode-simulator:focus {
    box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2) !important;
}

/* Upload button loading animation */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

#saveMealPlanBtn i.fa-spinner {
    animation: spin 1s linear infinite;
}

/* Responsive typography */
@media (max-width: 767.98px) {
    h1, h2, h3, h4, h5, h6 {
        margin-bottom: 0.75rem !important;
    }
    
    p, div, span {
        margin-bottom: 0.5rem !important;
    }
    
    .mb-3 {
        margin-bottom: 1rem !important;
    }
    
    .mb-4 {
        margin-bottom: 1.5rem !important;
    }
    
    .mt-3 {
        margin-top: 1rem !important;
    }
    
    .mt-4 {
        margin-top: 1.5rem !important;
    }
}

/* Mobile-specific utility classes */
@media (max-width: 767.98px) {
    .mobile-only {
        display: block !important;
    }
    
    .desktop-only {
        display: none !important;
    }
    
    .mobile-text-center {
        text-align: center !important;
    }
    
    .mobile-stack {
        flex-direction: column !important;
        gap: 0.75rem !important;
    }
}

/* Ensure proper content flow on mobile */
@media (max-width: 767.98px) {
    body {
        overflow-x: hidden;
        width: 100%;
    }
    
    /* Prevent horizontal scrolling */
    * {
        max-width: 100%;
        box-sizing: border-box;
    }
    
    img, video, iframe {
        max-width: 100%;
        height: auto;
    }
}

/* Fix for mobile keyboard */
@media (max-width: 767.98px) {
    input, textarea, select {
        font-size: 16px !important; /* Prevents iOS zoom */
    }
}

/* Accessibility improvements for mobile */
@media (max-width: 767.98px) {
    .btn, a, input[type="submit"], button {
        min-height: 44px !important;
        min-width: 44px !important;
    }
    
    input, select, textarea {
        min-height: 44px !important;
    }
    
    .delete-btn,
    .delete-meal-btn {
        min-height: 44px !important;
        min-width: 44px !important;
    }
    
    /* Focus styles for better accessibility */
    *:focus {
        outline: 2px solid #3a86ff !important;
        outline-offset: 2px !important;
    }
}

/* Smooth transitions */
* {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

/* Modal adjustments for mobile */
@media (max-width: 767.98px) {
    .modal-dialog {
        margin: 0.5rem !important;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .modal-body {
        max-height: 50vh;
        overflow-y: auto;
    }
}

/* Grid layout adjustments */
@media (max-width: 767.98px) {
    .row {
        margin: 0 !important;
    }
    
    .col-lg-4,
    .col-lg-8 {
        width: 100% !important;
        padding: 0 !important;
    }
}

/* Water tracker responsive adjustments */
@media (max-width: 480px) {
    .water-cup {
        width: 35px !important;
        height: 50px !important;
    }
    
    .water-cups {
        gap: 4px !important;
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

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #ff9a9e, #fad0c4); color: #ff6b6b;">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stats-value" id="caloriesConsumed"><?php echo $totalCalories; ?></div>
                    <div class="stats-label">Calories Consumed</div>
                    <div class="small text-muted mt-2">Goal: <span id="calorieGoal">2000</span> kcal</div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #a1c4fd, #c2e9fb); color: #4d96ff;">
                        <i class="fas fa-drumstick-bite"></i>
                    </div>
                    <div class="stats-value" id="proteinAmount"><?php echo $totalProtein; ?>g</div>
                    <div class="stats-label">Protein</div>
                    <div class="small text-muted mt-2">Essential for muscle</div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #84fab0, #8fd3f4); color: #38b000;">
                        <i class="fas fa-bread-slice"></i>
                    </div>
                    <div class="stats-value" id="carbsAmount"><?php echo $totalCarbs; ?>g</div>
                    <div class="stats-label">Carbohydrates</div>
                    <div class="small text-muted mt-2">Energy source</div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #fbc2eb, #a6c1ee); color: #8338ec;">
                        <i class="fas fa-oil-can"></i>
                    </div>
                    <div class="stats-value" id="fatAmount"><?php echo $totalFat; ?>g</div>
                    <div class="stats-label">Fats</div>
                    <div class="small text-muted mt-2">Healthy fats</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Nutrition & Search -->
            <div class="col-lg-4">
                <!-- Calorie Progress -->
                <div class="macronutrient-card">
                    <h5 class="mb-3"><i class="fas fa-bullseye me-2"></i>Calorie Goal Progress</h5>
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
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="adjustCalorieGoal(-100)">
                            <i class="fas fa-minus"></i> 100
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="adjustCalorieGoal(100)">
                            <i class="fas fa-plus"></i> 100
                        </button>
                    </div>
                </div>

                <!-- Macronutrient Breakdown -->
                <div class="macronutrient-card">
                    <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Macronutrient Breakdown</h5>
                    <div class="macro-chart-container">
                        <canvas id="macroChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="macronutrient-item">
                            <div class="macro-color" style="background: var(--protein-color);"></div>
                            <div class="macro-info">
                                <div class="macro-name">Protein</div>
                                <div class="macro-value">Essential for muscle repair</div>
                            </div>
                            <div class="macro-amount" id="proteinDisplay"><?php echo $totalProtein; ?>g</div>
                        </div>
                        <div class="macronutrient-item">
                            <div class="macro-color" style="background: var(--carbs-color);"></div>
                            <div class="macro-info">
                                <div class="macro-name">Carbohydrates</div>
                                <div class="macro-value">Primary energy source</div>
                            </div>
                            <div class="macro-amount" id="carbsDisplay"><?php echo $totalCarbs; ?>g</div>
                        </div>
                        <div class="macronutrient-item">
                            <div class="macro-color" style="background: var(--fat-color);"></div>
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
                    <h5 class="mb-3"><i class="fas fa-tint me-2"></i>Water Intake</h5>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <div class="stats-value" id="waterAmount">0/8</div>
                            <div class="stats-label">Glasses Today</div>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-light me-2" onclick="adjustWater(-1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button class="btn btn-sm btn-light" onclick="adjustWater(1)">
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
                    <h5 class="mb-3"><i class="fas fa-search me-2"></i>Search & Add Food</h5>
                    <div class="search-input-group mb-3">
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
                        <div class="meal-icon" style="background: linear-gradient(135deg, #3a86ff, #8338ec); color: white;">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="meal-title">Today's Saved Meals</div>
                        <div class="meal-calories" id="total-calories"><?php echo $totalCalories; ?> kcal</div>
                    </div>

                    <div id="all-meals-items">
                        <?php if (empty($existingMeals)): ?>
                            <div class="empty-meal">
                                <i class="fas fa-utensils"></i>
                                <p>No meals saved yet</p>
                                <small class="text-muted">Search and add foods above</small>
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
                    'breakfast' => ['name' => 'Breakfast', 'icon' => 'fa-sun', 'color' => '#FF9A9E'],
                    'lunch' => ['name' => 'Lunch', 'icon' => 'fa-utensils', 'color' => '#A1C4FD'],
                    'dinner' => ['name' => 'Dinner', 'icon' => 'fa-moon', 'color' => '#84FAB0'],
                    'snack' => ['name' => 'Snacks', 'icon' => 'fa-apple-alt', 'color' => '#FBC2EB']
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
                            <div class="meal-icon" style="background: <?php echo $meal['color']; ?>; color: white;">
                                <i class="fas <?php echo $meal['icon']; ?>"></i>
                            </div>
                            <div class="meal-title"><?php echo $meal['name']; ?></div>
                            <div class="meal-calories" id="meal-<?php echo $key; ?>-calories">
                                <?php echo $mealCalories; ?> kcal
                            </div>
                        </div>

                        <div id="meal-<?php echo $key; ?>-items">
                            <?php if (empty($mealItems)): ?>
                                <div class="empty-meal">
                                    <i class="fas fa-plus-circle"></i>
                                    <p>No foods added yet</p>
                                    <small class="text-muted">Search and add foods above</small>
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
                    <button class="btn btn-primary flex-grow-1" id="saveMealPlanBtn">
                        <i class="fas fa-save me-2"></i>Save Today's Meal Plan
                    </button>
                    <button class="btn btn-danger" onclick="clearAllMeals()">
                        <i class="fas fa-trash me-2"></i>Clear All
                    </button>
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
        <a href="planner.php" class="mobile-nav-item active">
            <i class="fas fa-utensils"></i>
            <span>Meals</span>
        </a>
        <a href="../progress/" class="mobile-nav-item">
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
                color: '#FF9A9E'
            },
            'lunch': {
                name: 'Lunch',
                icon: 'fa-utensils',
                color: '#A1C4FD'
            },
            'dinner': {
                name: 'Dinner',
                icon: 'fa-moon',
                color: '#84FAB0'
            },
            'snack': {
                name: 'Snacks',
                icon: 'fa-apple-alt',
                color: '#FBC2EB'
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
            const emptyState = container.querySelector('.empty-meal');

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

        // // Add to "Today's Saved Meals" section
        // function addToTodayMealsSection(title, calories, protein, carbs, fat, mealKey, foodId) {
        //     const container = document.getElementById('all-meals-items');
        //     const emptyState = container.querySelector('.empty-meal');

        //     // Only add if this is a temporary item
        //     if (emptyState || foodId.startsWith('temp_')) {
        //         if (emptyState) {
        //             emptyState.remove();
        //         }

        //         const mealInfo = mealsData[mealKey];
        //         const badgeClass = `meal-badge-${mealKey}`;

        //         const foodItem = document.createElement('div');
        //         foodItem.className = 'food-item';
        //         foodItem.dataset.id = foodId;
        //         foodItem.dataset.mealType = mealKey;
        //         foodItem.dataset.calories = calories;
        //         foodItem.dataset.protein = protein;
        //         foodItem.dataset.carbs = carbs;
        //         foodItem.dataset.fat = fat;

        //         foodItem.innerHTML = `
        //     <div class="food-info">
        //         <div class="food-name">
        //             <span class="meal-badge ${badgeClass}">${mealInfo.name}</span>
        //             ${title}
        //         </div>
        //         <div class="food-macros">
        //             Protein: ${protein}g • Carbs: ${carbs}g • Fat: ${fat}g
        //         </div>
        //     </div>
        //     <div class="food-calories">${calories} kcal</div>
        //     <button class="delete-meal-btn" onclick="deleteMealItem('${foodId}', this)">
        //         <i class="fas fa-trash"></i>
        //     </button>
        // `;

        //         container.appendChild(foodItem);
        //     }
        // }

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
                            <div class="empty-meal">
                                <i class="fas fa-utensils"></i>
                                <p>No meals saved yet</p>
                                <small class="text-muted">Search and add foods above</small>
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
                        <div class="empty-meal">
                            <i class="fas fa-plus-circle"></i>
                            <p>No foods added yet</p>
                            <small class="text-muted">Search and add foods above</small>
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
                    <div class="empty-meal">
                        <i class="fas fa-plus-circle"></i>
                        <p>No foods added yet</p>
                        <small class="text-muted">Search and add foods above</small>
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
                <div class="empty-meal">
                    <i class="fas fa-utensils"></i>
                    <p>No meals saved yet</p>
                    <small class="text-muted">Search and add foods above</small>
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
                    <div class="empty-meal">
                        <i class="fas fa-utensils"></i>
                        <p>No meals saved yet</p>
                        <small class="text-muted">Search and add foods above</small>
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