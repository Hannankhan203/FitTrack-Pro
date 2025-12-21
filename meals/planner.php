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
                        <div class="stats-goal">Goal: <span id="calorieGoal">3000</span> kcal</div>
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
                        <div class="stats-goal">Target: 180g (Energy source)</div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-oil-can"></i>
                        </div>
                        <div class="stats-value" id="fatAmount"><?php echo $totalFat; ?>g</div>
                        <div class="stats-label">Fats</div>
                        <div class="stats-goal">Target: 80g (Healthy fats)</div>
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
                        <input type="text" id="foodSearch" class="search-input" placeholder="Search foods (e.g. Chicken, Whey, Egg Whites ...)">
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

        // Food Database
        const pakistaniFoodDatabase = {
            'pakistani': [{
                    title: "Half-Fried Egg (1 egg)",
                    nutrition: {
                        calories: 100,
                        protein: 6,
                        carbs: 0.5,
                        fat: 7
                    }
                },
                {
                    title: "Half-Fried Eggs (2 eggs)",
                    nutrition: {
                        calories: 200,
                        protein: 12,
                        carbs: 1,
                        fat: 15
                    }
                },
                {
                    title: "White Bread (1 slice)",
                    nutrition: {
                        calories: 75,
                        protein: 2.5,
                        carbs: 13.5,
                        fat: 1
                    }
                },
                {
                    title: "White Bread (2 slices)",
                    nutrition: {
                        calories: 150,
                        protein: 5,
                        carbs: 27,
                        fat: 2
                    }
                },
                {
                    title: "Toasted White Bread (1 slice)",
                    nutrition: {
                        calories: 76,
                        protein: 2.5,
                        carbs: 13.5,
                        fat: 1
                    }
                },
                {
                    title: "Toasted White Bread (2 slices)",
                    nutrition: {
                        calories: 152,
                        protein: 5,
                        carbs: 27,
                        fat: 2
                    }
                },
                {
                    title: "White Bun (1 medium)",
                    nutrition: {
                        calories: 150,
                        protein: 5,
                        carbs: 28,
                        fat: 2
                    }
                },
                {
                    title: "Foil Baked Chicken (250g)",
                    nutrition: {
                        calories: 400,
                        protein: 80,
                        carbs: 2,
                        fat: 7
                    }
                },
                {
                    title: "1 Plate Cooked Rice (200g)",
                    nutrition: {
                        calories: 270,
                        protein: 6,
                        carbs: 55,
                        fat: 0.5
                    }
                },
                {
                    title: "Moong Daal Chewra (100g)",
                    nutrition: {
                        calories: 480,
                        protein: 22,
                        carbs: 45,
                        fat: 22
                    }
                },
                {
                    title: "Aalu ka Samosa",
                    nutrition: {
                        calories: 270,
                        protein: 6,
                        carbs: 30,
                        fat: 16
                    }
                },
                {
                    title: "Chapati (Medium) (40g)",
                    nutrition: {
                        calories: 125,
                        protein: 4,
                        carbs: 20,
                        fat: 1
                    }
                },
                {
                    title: "Pulao (1 Plate 250g)",
                    nutrition: {
                        calories: 460,
                        protein: 22,
                        carbs: 60,
                        fat: 15
                    }
                },
            ],
            'fastfood': [{
                title: "Zinger Burger",
                nutrition: {
                    calories: 450,
                    protein: 25,
                    carbs: 40,
                    fat: 25
                }
            }, ],
            'beverages': [{
                    title: "Homemade Whey (1 cup)",
                    nutrition: {
                        calories: 60,
                        protein: 8,
                        carbs: 8,
                        fat: 1
                    }
                },
                {
                    title: "Homemade Whey (Half cup)",
                    nutrition: {
                        calories: 30,
                        protein: 4,
                        carbs: 4,
                        fat: 0.5
                    }
                },
                {
                    title: "Tea with Gur + Powdered Milk (1 cup)",
                    nutrition: {
                        calories: 110,
                        protein: 4,
                        carbs: 20,
                        fat: 3
                    }
                },
                {
                    title: "Green Tea (1 cup)",
                    nutrition: {
                        calories: 2,
                        protein: 0,
                        carbs: 0,
                        fat: 0
                    }
                },
                {
                    title: "Tea with Sugar & Powdered Milk (1 cup)",
                    nutrition: {
                        calories: 130,
                        protein: 2,
                        carbs: 18,
                        fat: 5
                    }
                },
                {
                    title: "Tea with Powdered Milk (no sugar, 1 cup)",
                    nutrition: {
                        calories: 65,
                        protein: 2,
                        carbs: 7,
                        fat: 3.5
                    }
                },
                {
                    title: "Black Coffee (1 cup)",
                    nutrition: {
                        calories: 5,
                        protein: 0,
                        carbs: 0,
                        fat: 0
                    }
                },
                {
                    title: "Coffee with Sugar (1 tsp, no milk)",
                    nutrition: {
                        calories: 16,
                        protein: 0,
                        carbs: 4,
                        fat: 0
                    }
                },
                {
                    title: "Coffee with Milk (2 tbsp powdered milk, no sugar)",
                    nutrition: {
                        calories: 25,
                        protein: 1,
                        carbs: 4,
                        fat: 1.5
                    }
                },
                {
                    title: "Coffee with Milk & Sugar (2 tbsp powdered milk + 1 tsp sugar)",
                    nutrition: {
                        calories: 40,
                        protein: 1,
                        carbs: 8,
                        fat: 1.5
                    }
                },
                {
                    title: "Whole Milk (1 cup)",
                    nutrition: {
                        calories: 150,
                        protein: 8,
                        carbs: 12,
                        fat: 8
                    }
                },
                {
                    title: "Gatorade (500 ml)",
                    nutrition: {
                        calories: 120,
                        protein: 0,
                        carbs: 29,
                        fat: 0
                    }
                },

            ],
            'fruits': [{
                    title: "Apple (medium)",
                    nutrition: {
                        calories: 80,
                        protein: 0.4,
                        carbs: 21,
                        fat: 0.3
                    }
                },
                {
                    title: "Orange (medium)",
                    nutrition: {
                        calories: 60,
                        protein: 1,
                        carbs: 15,
                        fat: 0.2
                    }
                },
                {
                    title: "Pomegranate (1 medium)",
                    nutrition: {
                        calories: 140,
                        protein: 3,
                        carbs: 32,
                        fat: 1.2
                    }
                },
                {
                    title: "Grapes (5 grapes)",
                    nutrition: {
                        calories: 10,
                        protein: 0.1,
                        carbs: 2.6,
                        fat: 0
                    }
                },
                {
                    title: "Mango (medium)",
                    nutrition: {
                        calories: 135,
                        protein: 1,
                        carbs: 35,
                        fat: 0.6
                    }
                },
                {
                    title: "Almonds (10 pieces)",
                    nutrition: {
                        calories: 70,
                        protein: 2.5,
                        carbs: 2,
                        fat: 6
                    }
                },
                {
                    title: "Cashews (10 pieces)",
                    nutrition: {
                        calories: 65,
                        protein: 2,
                        carbs: 4,
                        fat: 5
                    }
                },
                {
                    title: "Walnuts (10 halves)",
                    nutrition: {
                        calories: 100,
                        protein: 2.5,
                        carbs: 1.5,
                        fat: 9
                    }
                },
                {
                    title: "Raisins (10 pieces)",
                    nutrition: {
                        calories: 15,
                        protein: 0.2,
                        carbs: 4,
                        fat: 0
                    }
                },
                {
                    title: "Pistachios (10 pieces)",
                    nutrition: {
                        calories: 65,
                        protein: 2.5,
                        carbs: 3,
                        fat: 5
                    }
                },
                {
                    title: "Mixed Nuts & Raisins (10 almonds + 10 cashews + 10 walnuts + 10 raisins + 10 pistachios)",
                    nutrition: {
                        calories: 315,
                        protein: 9.7,
                        carbs: 14.5,
                        fat: 25
                    }
                },
            ],
            'desserts': [],
            'healthy': [{
                    title: "Egg White Only (from 1 large egg)",
                    nutrition: {
                        calories: 17,
                        protein: 3.6,
                        carbs: 0.2,
                        fat: 0
                    }
                },
                {
                    title: "Egg Whites (2 boiled)",
                    nutrition: {
                        calories: 34,
                        protein: 8,
                        carbs: 0.6,
                        fat: 0
                    }
                },
                {
                    title: "1 Boiled Egg (large)",
                    nutrition: {
                        calories: 70,
                        protein: 6,
                        carbs: 0.5,
                        fat: 5
                    }
                },
                {
                    title: "2 Boiled Egg",
                    nutrition: {
                        calories: 140,
                        protein: 12,
                        carbs: 1,
                        fat: 10
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
                    title: "Red Lobia (Rajma/Kidney Beans, 1 bowl)",
                    nutrition: {
                        calories: 220,
                        protein: 15,
                        carbs: 40,
                        fat: 1
                    }
                },
                {
                    title: "Boiled Chicken (1 piece / ~25 g)",
                    nutrition: {
                        calories: 50,
                        protein: 9,
                        carbs: 0,
                        fat: 1.5
                    }
                },
                {
                    title: "Boiled Chicken (2 pieces / ~50 g)",
                    nutrition: {
                        calories: 100,
                        protein: 18,
                        carbs: 0,
                        fat: 3
                    }
                },
                {
                    title: "Daliya + Milk + Dry Milk (1 bowl)",
                    nutrition: {
                        calories: 340,
                        protein: 22,
                        carbs: 55,
                        fat: 6
                    }
                },
                {
                    title: "Daliya + Milk + Dry Milk (Half bowl)",
                    nutrition: {
                        calories: 170,
                        protein: 11,
                        carbs: 27.5,
                        fat: 3
                    }
                },
                {
                    title: "Daliya + Milk + Dry Milk + 2 tsp Honey (1 bowl)",
                    nutrition: {
                        calories: 380,
                        protein: 22,
                        carbs: 63,
                        fat: 6
                    }
                },
                {
                    title: "Daliya + Milk + Dry Milk + 2 tsp Honey (Half bowl)",
                    nutrition: {
                        calories: 190,
                        protein: 11,
                        carbs: 31.5,
                        fat: 3
                    }
                },
                {
                    title: "Black Chana (1 bowl, boiled)",
                    nutrition: {
                        calories: 250,
                        protein: 15,
                        carbs: 40,
                        fat: 3
                    }
                },
                {
                    title: "Daal Masoor/Moong (1 cup cooked)",
                    nutrition: {
                        calories: 180,
                        protein: 12,
                        carbs: 27,
                        fat: 3
                    }
                },
                {
                    title: "Bread + Peanut Butter",
                    nutrition: {
                        calories: 250,
                        protein: 10,
                        carbs: 26,
                        fat: 12
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
                    showNotification('Error saving meals: '.error.message, 'error');
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