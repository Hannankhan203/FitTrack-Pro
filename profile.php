<?php 
// Start session
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'includes/functions.php'; 
require_login(); 

$user_id = get_user_id(); 
require 'includes/db.php';

// Include achievements functions
require 'includes/achievements.php';

// Check if columns exist, if not create them
try {
    // Check table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add missing columns
    if (!in_array('goal_weight', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN goal_weight DECIMAL(5,2) DEFAULT NULL");
    }
    
    if (!in_array('goal_type', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN goal_type VARCHAR(20) DEFAULT 'lose'");
    }
    
    if (!in_array('weight', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN weight DECIMAL(5,2) DEFAULT NULL");
    }
    
} catch (PDOException $e) {
    // If alter fails, continue anyway
    error_log("Column check error: " . $e->getMessage());
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("User not found");
    }
    
    // Set default values for missing fields
    $user['weight'] = $user['weight'] ?? 0;
    $user['goal_weight'] = $user['goal_weight'] ?? 0;
    $user['goal_type'] = $user['goal_type'] ?? 'lose';
    $user['name'] = $user['name'] ?? 'User';
    $user['email'] = $user['email'] ?? '';
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['goal_weight']) && isset($_POST['goal_type'])) {
    try {
        $goal_weight = floatval($_POST['goal_weight']);
        $goal_type = $_POST['goal_type'];
        
        // Update user goal
        $stmt = $pdo->prepare("UPDATE users SET goal_weight = ?, goal_type = ? WHERE id = ?");
        $stmt->execute([$goal_weight, $goal_type, $user_id]);
        
        // Update local user data
        $user['goal_weight'] = $goal_weight;
        $user['goal_type'] = $goal_type;
        
        // Set success message
        $_SESSION['success_message'] = "Goal updated successfully!";
        
        // Check for achievements after goal update
        check_and_award_achievements($pdo, $user_id);
        
        // Redirect to avoid form resubmission
        header("Location: profile.php");
        exit();
        
    } catch (PDOException $e) {
        die("Database update error: " . $e->getMessage());
    }
}

// Check for achievements when visiting profile
$new_badges = check_and_award_achievements($pdo, $user_id);

// Get earned badges for display
$earned_badges = get_user_badges($pdo, $user_id);

// Define badge information for display
$all_badges = [
    'First Workout' => [
        'icon' => 'fa-dumbbell', 
        'color' => '#00d4ff', 
        'desc' => 'Complete your first workout'
    ],
    '1000 Calories Logged' => [
        'icon' => 'fa-fire', 
        'color' => '#ff2d75', 
        'desc' => 'Log 1000 calories in meals'
    ],
    '5kg Lost' => [
        'icon' => 'fa-weight-scale', 
        'color' => '#00e676', 
        'desc' => 'Lose 5kg from starting weight'
    ],
    '30 Day Streak' => [
        'icon' => 'fa-calendar-check', 
        'color' => '#ffc107', 
        'desc' => 'Maintain a 30-day workout streak'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Profile & Goals - FitTrack Pro</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">

</head>
<body>
    <!-- Achievement Celebration Modal -->
    <div id="achievementModal" class="achievement-modal">
        <div class="achievement-modal-content">
            <div class="celebration-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <h2 class="celebration-title">Achievement Unlocked!</h2>
            <div class="celebration-badge-name" id="achievementBadgeName"></div>
            <div class="celebration-message" id="achievementMessage">Congratulations! You've earned a new badge.</div>
            <button class="btn btn-success" onclick="closeAchievementModal()">
                <i class="fas fa-check me-2"></i>Awesome!
            </button>
        </div>
    </div>

    <!-- Desktop Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand logo" href="dashboard.php">FitTrack Pro</a>
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="workouts/log.php">
                            <i class="fas fa-dumbbell"></i> Workouts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="meals/planner.php">
                            <i class="fas fa-utensils"></i> Meals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress/charts.php">
                            <i class="fas fa-chart-line"></i> Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress/photos.php">
                            <i class="fas fa-camera"></i> Photos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
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

    <div class="container mt-4 mb-5">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-user-circle me-2"></i>Profile & Goals</h1>
            <p>Manage your fitness goals and track your achievements</p>
        </div>

        <div class="row">
            <!-- Left Column - Profile & Goals -->
            <div class="col-lg-8">
                <!-- User Profile Card -->
                <div class="user-profile-card">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                    
                    <div class="user-stats">
                        <div class="user-stat">
                            <div class="stat-value"><?= $user['weight'] > 0 ? htmlspecialchars($user['weight']) : '--' ?></div>
                            <div class="stat-label">Current Weight (kg)</div>
                        </div>
                        <div class="user-stat">
                            <div class="stat-value"><?= $user['goal_weight'] > 0 ? htmlspecialchars($user['goal_weight']) : '--' ?></div>
                            <div class="stat-label">Goal Weight (kg)</div>
                        </div>
                        <div class="user-stat">
                            <div class="stat-value"><?= htmlspecialchars(ucfirst($user['goal_type'])) ?></div>
                            <div class="stat-label">Goal Type</div>
                        </div>
                    </div>
                </div>

                <!-- Set Your Goal -->
                <div class="goal-card">
                    <div class="goal-header">
                        <div class="goal-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="goal-title">Set Your Fitness Goal</div>
                    </div>
                    
                    <?php if(isset($_SESSION['success_message'])): ?>
                    <div class="achievement-banner">
                        <div>
                            <i class="fas fa-check-circle me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['success_message']) ?></strong>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php unset($_SESSION['success_message']); endif; ?>
                    
                    <?php if(isset($_SESSION['new_badge'])): ?>
                    <div class="achievement-banner">
                        <div>
                            <i class="fas fa-trophy me-2"></i>
                            <strong>New Achievement: <?= htmlspecialchars($_SESSION['new_badge']) ?>!</strong>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php 
                    // Store badge name for JavaScript celebration
                    $new_badge_name = $_SESSION['new_badge'];
                    unset($_SESSION['new_badge']); 
                    endif; 
                    ?>
                    
                    <form method="POST" id="goalForm">
                        <!-- Current Weight -->
                        <div class="mb-4">
                            <label class="form-label">Current Weight</label>
                            <div class="input-group">
                                <input type="number" step="0.1" value="<?= htmlspecialchars($user['weight']) ?>" class="form-control" readonly>
                                <span class="input-unit">kg</span>
                            </div>
                            <div class="form-text">Update your current weight on the Progress page</div>
                        </div>
                        
                        <!-- Goal Weight -->
                        <div class="mb-4">
                            <label class="form-label">Goal Weight</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="goal_weight" value="<?= htmlspecialchars($user['goal_weight']) ?>" class="form-control" required>
                                <span class="input-unit">kg</span>
                            </div>
                        </div>
                        
                        <!-- Goal Type Selection -->
                        <div class="mb-4">
                            <label class="form-label mb-3">Goal Type</label>
                            <div class="goal-type-selector">
                                <div class="goal-type-option <?= $user['goal_type'] == 'lose' ? 'selected' : '' ?>" onclick="selectGoalType('lose')">
                                    <div class="goal-type-icon">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                    <div class="goal-type-title">Lose Weight</div>
                                    <div class="goal-type-desc">Burn fat, get lean</div>
                                </div>
                                <div class="goal-type-option <?= $user['goal_type'] == 'gain' ? 'selected' : '' ?>" onclick="selectGoalType('gain')">
                                    <div class="goal-type-icon">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                    <div class="goal-type-title">Gain Muscle</div>
                                    <div class="goal-type-desc">Build strength, add mass</div>
                                </div>
                                <div class="goal-type-option <?= $user['goal_type'] == 'maintain' ? 'selected' : '' ?>" onclick="selectGoalType('maintain')">
                                    <div class="goal-type-icon">
                                        <i class="fas fa-balance-scale"></i>
                                    </div>
                                    <div class="goal-type-title">Maintain</div>
                                    <div class="goal-type-desc">Stay fit, maintain weight</div>
                                </div>
                            </div>
                            <input type="hidden" name="goal_type" id="goal_type" value="<?= htmlspecialchars($user['goal_type']) ?>">
                        </div>
                        
                        <!-- Goal Preview -->
                        <?php if($user['weight'] > 0 && $user['goal_weight'] > 0): 
                            $difference = $user['goal_weight'] - $user['weight'];
                        ?>
                        <div class="goal-preview">
                            <div class="goal-preview-title">Your Goal Progress</div>
                            <div class="goal-preview-values">
                                <div class="goal-value"><?= $user['weight'] ?> kg</div>
                                <div class="goal-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="goal-value"><?= $user['goal_weight'] ?> kg</div>
                            </div>
                            <div class="small">
                                <?php 
                                if($user['goal_type'] == 'lose' && $difference < 0) {
                                    echo "You need to lose " . abs($difference) . " kg to reach your goal";
                                } elseif($user['goal_type'] == 'gain' && $difference > 0) {
                                    echo "You need to gain " . $difference . " kg to reach your goal";
                                } elseif($user['goal_type'] == 'maintain') {
                                    echo "Goal: Maintain your current weight";
                                } else {
                                    echo "Your current weight matches your goal weight";
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 mt-4">
                            <i class="fas fa-save me-2"></i>Save Goal
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column - Badges -->
            <div class="col-lg-4">
                <div class="badges-card">
                    <div class="badges-header">
                        <div class="badges-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="badges-title">Your Badges</div>
                        <div class="badge-count" id="badgeCount"><?= count($earned_badges) ?>/4</div>
                    </div>
                    
                    <?php
                    $earned_count = count($earned_badges);
                    ?>
                    
                    <div class="badges-grid" id="badgesContainer">
                        <?php foreach($all_badges as $badge_name => $badge_info): 
                            $earned = in_array($badge_name, $earned_badges);
                        ?>
                        <div class="badge-card <?= $earned ? 'earned' : '' ?>">
                            <div class="badge-icon <?= $earned ? 'earned' : 'locked' ?>">
                                <i class="fas <?= $badge_info['icon'] ?>"></i>
                                <?php if(!$earned): ?>
                                <i class="fas fa-lock" style="position: absolute; bottom: 5px; right: 5px; font-size: 1rem;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="badge-name"><?= htmlspecialchars($badge_name) ?></div>
                            <div class="small text-muted mb-2"><?= htmlspecialchars($badge_info['desc']) ?></div>
                            <div class="badge-status <?= $earned ? 'status-earned' : 'status-locked' ?>">
                                <?php if($earned): ?>
                                <i class="fas fa-check-circle me-1"></i> Earned
                                <?php else: ?>
                                <i class="fas fa-lock me-1"></i> Locked
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if($earned_count == 0): ?>
                    <div class="empty-badges">
                        <i class="fas fa-trophy"></i>
                        <h4>No Badges Yet</h4>
                        <p>Complete challenges to earn badges and track your achievements!</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tips Card -->
                <div class="tips-card">
                    <div class="badges-header">
                        <div class="badges-icon" style="background: var(--gradient-warning);">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="badges-title">Goal Setting Tips</div>
                    </div>
                    <div class="small">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                            <div>
                                <strong>Set Realistic Goals</strong>
                                <p class="mb-0 text-muted">Aim for 0.5-1kg per week for sustainable weight loss</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                            <div>
                                <strong>Track Progress</strong>
                                <p class="mb-0 text-muted">Log weight weekly and take monthly progress photos</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                            <div>
                                <strong>Celebrate Milestones</strong>
                                <p class="mb-0 text-muted">Reward yourself when you reach important milestones</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <div class="mobile-nav">
        <a href="dashboard.php" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="workouts/" class="mobile-nav-item">
            <i class="fas fa-dumbbell"></i>
            <span>Workouts</span>
        </a>
        <a href="meals/" class="mobile-nav-item">
            <i class="fas fa-utensils"></i>
            <span>Meals</span>
        </a>
        <a href="progress/" class="mobile-nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Progress</span>
        </a>
        <a href="profile.php" class="mobile-nav-item active">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Wait for the page to fully load
        document.addEventListener('DOMContentLoaded', function() {
            function selectGoalType(type) {
                // Update hidden input
                document.getElementById('goal_type').value = type;
                
                // Update UI
                document.querySelectorAll('.goal-type-option').forEach(option => {
                    option.classList.remove('selected');
                });
                
                const selectedOption = document.querySelector(`[onclick="selectGoalType('${type}')"]`);
                if(selectedOption) {
                    selectedOption.classList.add('selected');
                }
            }
            
            // Make selectGoalType globally accessible
            window.selectGoalType = selectGoalType;
            
            // Achievement celebration
            function showAchievementModal(badgeName) {
                const modal = document.getElementById('achievementModal');
                const badgeNameElement = document.getElementById('achievementBadgeName');
                const messageElement = document.getElementById('achievementMessage');
                
                if (modal && badgeNameElement) {
                    badgeNameElement.textContent = badgeName;
                    
                    // Set different messages based on badge
                    let message = "Congratulations! You've earned a new badge.";
                    if (badgeName === 'First Workout') {
                        message = "Great start! Your first workout is complete.";
                    } else if (badgeName === '1000 Calories Logged') {
                        message = "You've logged 1000 calories! Keep tracking your nutrition.";
                    } else if (badgeName === '5kg Lost') {
                        message = "Incredible progress! You've lost 5kg.";
                    } else if (badgeName === '30 Day Streak') {
                        message = "Amazing consistency! 30 days of workouts complete.";
                    }
                    
                    messageElement.textContent = message;
                    modal.classList.add('show');
                    
                    // Play celebration sound (optional)
                    try {
                        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/286/286-preview.mp3');
                        audio.volume = 0.3;
                        audio.play().catch(e => console.log("Audio play failed:", e));
                    } catch (e) {
                        console.log("Audio error:", e);
                    }
                }
            }
            
            function closeAchievementModal() {
                const modal = document.getElementById('achievementModal');
                if (modal) {
                    modal.classList.remove('show');
                }
            }
            
            // Make functions globally accessible
            window.showAchievementModal = showAchievementModal;
            window.closeAchievementModal = closeAchievementModal;
            
            // Show achievement modal if there's a new badge
            <?php if(isset($new_badge_name)): ?>
            setTimeout(() => {
                showAchievementModal("<?= $new_badge_name ?>");
            }, 1000);
            <?php endif; ?>
            
            // Form submission animation
            document.getElementById('goalForm').addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                submitBtn.disabled = true;
            });
            
            // Mobile navigation handling (same as other pages)
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
            
            // Close achievement modal when clicking outside
            document.addEventListener('click', function(event) {
                const modal = document.getElementById('achievementModal');
                if (modal && modal.classList.contains('show') && event.target === modal) {
                    closeAchievementModal();
                }
            });
            
            // Close achievement modal with Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeAchievementModal();
                }
            });
        });
    </script>
</body>
</html>