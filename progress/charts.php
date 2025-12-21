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

// Goal progress - COMPLETELY FIXED VERSION
try {
    $stmt = $pdo->prepare("SELECT goal_weight, goal_type, weight FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $goal_info = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching goal info: " . $e->getMessage());
    $goal_info = false;
}

$goal_progress = 0;

// First, check if user has a goal weight set
if ($goal_info && $goal_info['goal_weight'] > 0) {
    // Get starting weight: use the first entry from progress table if available
    $starting_weight = $goal_info['weight']; // Default to weight in users table
    
    // Try to get the first weight entry from progress table
    try {
        $stmt = $pdo->prepare("SELECT weight FROM progress WHERE user_id = ? ORDER BY created_at ASC, date ASC LIMIT 1");
        $stmt->execute([$user_id]);
        $firstWeight = $stmt->fetch();
        if ($firstWeight && $firstWeight['weight'] > 0) {
            $starting_weight = $firstWeight['weight'];
        }
    } catch (PDOException $e) {
        // If no progress entries or error, use weight from users table
        error_log("Error fetching first weight: " . $e->getMessage());
    }
    
    // Get current weight - prefer from progress table (most recent), fallback to users table weight
    $currentWeight = $current_weight ?: $goal_info['weight'];
    
    if ($goal_info['goal_type'] == 'lose') {
        // For weight loss: starting weight should be higher than goal weight
        if ($starting_weight > $goal_info['goal_weight']) {
            $total_to_lose = $starting_weight - $goal_info['goal_weight'];
            $current_lost = $starting_weight - $currentWeight;
            
            // Calculate progress, ensure it's between 0-100%
            if ($total_to_lose > 0) {
                $goal_progress = ($current_lost / $total_to_lose) * 100;
                $goal_progress = min(100, max(0, $goal_progress));
            }
        }
    } elseif ($goal_info['goal_type'] == 'gain') {
        // For weight gain: starting weight should be lower than goal weight
        if ($starting_weight < $goal_info['goal_weight']) {
            $total_to_gain = $goal_info['goal_weight'] - $starting_weight;
            $current_gain = $currentWeight - $starting_weight;
            
            // Calculate progress, ensure it's between 0-100%
            if ($total_to_gain > 0) {
                $goal_progress = ($current_gain / $total_to_gain) * 100;
                $goal_progress = min(100, max(0, $goal_progress));
            }
        }
    } else {
        // Maintenance goal - always 100%
        $goal_progress = 100;
    }
    
    // Round for display
    $goal_progress = round($goal_progress);
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
        <div class="stats-value"><?= $goal_progress ?>%</div>
        <div class="stats-label">Goal Progress</div>
        <div class="stats-subtitle">
            <?php
            if ($goal_info && $goal_info['goal_weight'] > 0) {
                echo ucfirst($goal_info['goal_type']) . ' to ' . $goal_info['goal_weight'] . 'kg';
            } else {
                echo 'No goal set';
            }
            ?>
        </div>
        <?php if ($goal_info && $goal_info['goal_weight'] > 0): ?>
            <div class="small text-muted mt-2">
                <?php
                if ($goal_info['goal_type'] == 'lose') {
                    $remaining = max(0, ($current_weight ?: $goal_info['weight']) - $goal_info['goal_weight']);
                    echo number_format($remaining, 1) . "kg to go";
                } elseif ($goal_info['goal_type'] == 'gain') {
                    $remaining = max(0, $goal_info['goal_weight'] - ($current_weight ?: $goal_info['weight']));
                    echo number_format($remaining, 1) . "kg to go";
                }
                ?>
            </div>
        <?php endif; ?>
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