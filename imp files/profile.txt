<?php 
require 'includes/functions.php'; 
require_login(); 

$user_id = get_user_id(); 
require 'includes/db.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_POST) {
    $goal_weight = $_POST['goal_weight'];
    $goal_type = $_POST['goal_type'];
    $pdo->prepare("UPDATE users SET goal_weight = ?, goal_type = ? WHERE id = ?")
        ->execute([$goal_weight, $goal_type, $user_id]);
    $user = $pdo->query("SELECT * FROM users WHERE id = $user_id")->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile & Goals - FitTrack Pro</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    
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
        
        .nav-link:hover, .nav-link.active {
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
        
        .user-profile-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .user-avatar {
            width: 120px;
            height: 120px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 1.5rem;
            border: 5px solid white;
            box-shadow: 0 5px 20px rgba(58, 134, 255, 0.3);
        }
        
        .user-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .user-email {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        
        .user-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .user-stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .goal-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }
        
        .goal-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .goal-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-right: 1rem;
        }
        
        .goal-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            flex: 1;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
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
        
        .goal-type-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .goal-type-option {
            text-align: center;
            padding: 1.5rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .goal-type-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
        }
        
        .goal-type-option.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(58, 134, 255, 0.1), rgba(131, 56, 236, 0.1));
        }
        
        .goal-type-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: var(--primary-color);
        }
        
        .goal-type-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .goal-type-desc {
            font-size: 0.85rem;
            color: #6c757d;
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
        
        .badges-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }
        
        .badges-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .badges-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-right: 1rem;
            background: var(--gradient-warning);
            color: white;
        }
        
        .badges-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            flex: 1;
        }
        
        .badge-count {
            background: var(--gradient-secondary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .badge-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .badge-card.earned {
            border-color: var(--success-color);
            background: linear-gradient(135deg, rgba(56, 176, 0, 0.05), rgba(112, 224, 0, 0.05));
        }
        
        .badge-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .badge-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1rem;
            position: relative;
        }
        
        .badge-icon.earned {
            background: var(--gradient-success);
            color: white;
            box-shadow: 0 5px 15px rgba(56, 176, 0, 0.3);
        }
        
        .badge-icon.locked {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            color: #adb5bd;
        }
        
        .badge-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .badge-status {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .status-earned {
            background: rgba(56, 176, 0, 0.1);
            color: var(--success-color);
        }
        
        .status-locked {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
        
        .badge-progress {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 1rem;
            overflow: hidden;
        }
        
        .badge-progress-fill {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 2px;
            transition: width 0.5s;
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
        
        .goal-preview {
            background: linear-gradient(135deg, rgba(58, 134, 255, 0.1), rgba(131, 56, 236, 0.1));
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            text-align: center;
        }
        
        .goal-preview-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .goal-preview-values {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .goal-value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .goal-arrow {
            color: var(--primary-color);
            font-size: 1.5rem;
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
            
            .goal-type-selector {
                grid-template-columns: 1fr;
            }
            
            .user-stats {
                grid-template-columns: 1fr;
            }
            
            .badges-grid {
                grid-template-columns: 1fr;
            }
            
            .goal-preview-values {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
        
        .empty-badges {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        .empty-badges i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        .achievement-banner {
            background: var(--gradient-success);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
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
                    <div class="user-name"><?= htmlspecialchars($user['name'] ?? 'User') ?></div>
                    <div class="user-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                    
                    <div class="user-stats">
                        <div class="user-stat">
                            <div class="stat-value"><?= $user['weight'] ?? '--' ?></div>
                            <div class="stat-label">Current Weight (kg)</div>
                        </div>
                        <div class="user-stat">
                            <div class="stat-value"><?= $user['goal_weight'] ?? '--' ?></div>
                            <div class="stat-label">Goal Weight (kg)</div>
                        </div>
                        <div class="user-stat">
                            <div class="stat-value"><?= isset($user['goal_type']) ? ucfirst($user['goal_type']) : '--' ?></div>
                            <div class="stat-label">Goal Type</div>
                        </div>
                    </div>
                </div>

                <!-- Set Your Goal -->
                <div class="goal-card">
                    <div class="goal-header">
                        <div class="goal-icon" style="background: var(--gradient-primary); color: white;">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="goal-title">Set Your Fitness Goal</div>
                    </div>
                    
                    <?php if(isset($_POST) && !empty($_POST)): ?>
                    <div class="achievement-banner">
                        <div>
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Goal Updated!</strong> Your fitness goal has been saved successfully.
                        </div>
                        <button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="goalForm">
                        <!-- Current Weight -->
                        <div class="mb-4">
                            <label class="form-label">Current Weight</label>
                            <div class="input-group">
                                <input type="number" step="0.1" value="<?= $user['weight'] ?? '' ?>" class="form-control" readonly>
                                <span class="input-unit">kg</span>
                            </div>
                            <div class="form-text">Update your current weight on the Progress page</div>
                        </div>
                        
                        <!-- Goal Weight -->
                        <div class="mb-4">
                            <label class="form-label">Goal Weight</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="goal_weight" value="<?= $user['goal_weight'] ?? '' ?>" class="form-control" required>
                                <span class="input-unit">kg</span>
                            </div>
                        </div>
                        
                        <!-- Goal Type Selection -->
                        <div class="mb-4">
                            <label class="form-label mb-3">Goal Type</label>
                            <div class="goal-type-selector">
                                <div class="goal-type-option <?= ($user['goal_type'] ?? '') == 'lose' ? 'selected' : '' ?>" onclick="selectGoalType('lose')">
                                    <div class="goal-type-icon">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                    <div class="goal-type-title">Lose Weight</div>
                                    <div class="goal-type-desc">Burn fat, get lean</div>
                                </div>
                                <div class="goal-type-option <?= ($user['goal_type'] ?? '') == 'gain' ? 'selected' : '' ?>" onclick="selectGoalType('gain')">
                                    <div class="goal-type-icon">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                    <div class="goal-type-title">Gain Muscle</div>
                                    <div class="goal-type-desc">Build strength, add mass</div>
                                </div>
                                <div class="goal-type-option <?= ($user['goal_type'] ?? '') == 'maintain' ? 'selected' : '' ?>" onclick="selectGoalType('maintain')">
                                    <div class="goal-type-icon">
                                        <i class="fas fa-balance-scale"></i>
                                    </div>
                                    <div class="goal-type-title">Maintain</div>
                                    <div class="goal-type-desc">Stay fit, maintain weight</div>
                                </div>
                            </div>
                            <input type="hidden" name="goal_type" id="goal_type" value="<?= $user['goal_type'] ?? 'lose' ?>">
                        </div>
                        
                        <!-- Goal Preview -->
                        <?php if(($user['weight'] ?? 0) > 0 && ($user['goal_weight'] ?? 0) > 0): ?>
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
                                $difference = $user['goal_weight'] - $user['weight'];
                                if(($user['goal_type'] ?? 'lose') == 'lose' && $difference < 0) {
                                    echo "You need to lose " . abs($difference) . " kg to reach your goal";
                                } elseif(($user['goal_type'] ?? 'gain') == 'gain' && $difference > 0) {
                                    echo "You need to gain " . $difference . " kg to reach your goal";
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
                        <div class="badge-count" id="badgeCount">0/4</div>
                    </div>
                    
                    <?php
                    $badges = $pdo->query("SELECT badge FROM achievements WHERE user_id = $user_id")->fetchAll();
                    $all_badges = [
                        'First Workout' => ['icon' => 'fa-dumbbell', 'color' => '#3a86ff', 'desc' => 'Complete your first workout'],
                        '1000 Calories Logged' => ['icon' => 'fa-fire', 'color' => '#ff006e', 'desc' => 'Log 1000 calories in meals'],
                        '5kg Lost' => ['icon' => 'fa-weight-scale', 'color' => '#38b000', 'desc' => 'Lose 5kg from starting weight'],
                        '30 Day Streak' => ['icon' => 'fa-calendar-check', 'color' => '#ffbe0b', 'desc' => 'Maintain a 30-day workout streak']
                    ];
                    
                    $earned_badges = array_column($badges, 'badge');
                    $earned_count = count(array_intersect($earned_badges, array_keys($all_badges)));
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
                            <div class="badge-name"><?= $badge_name ?></div>
                            <div class="small text-muted mb-2"><?= $badge_info['desc'] ?></div>
                            <div class="badge-status <?= $earned ? 'status-earned' : 'status-locked' ?>">
                                <?php if($earned): ?>
                                <i class="fas fa-check-circle me-1"></i> Earned
                                <?php else: ?>
                                <i class="fas fa-lock me-1"></i> Locked
                                <?php endif; ?>
                            </div>
                            <?php if(!$earned): ?>
                            <div class="badge-progress">
                                <div class="badge-progress-fill" style="width: 0%"></div>
                            </div>
                            <?php endif; ?>
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
                <div class="badges-card mt-4">
                    <div class="badges-header">
                        <div class="badges-icon" style="background: var(--gradient-secondary);">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="badges-title">Goal Setting Tips</div>
                    </div>
                    <div class="small">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-2">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                            <div>
                                <strong>Set Realistic Goals</strong>
                                <p class="mb-0">Aim for 0.5-1kg per week for sustainable weight loss</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-2">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                            <div>
                                <strong>Track Progress</strong>
                                <p class="mb-0">Log weight weekly and take monthly progress photos</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-2">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                            <div>
                                <strong>Celebrate Milestones</strong>
                                <p class="mb-0">Reward yourself when you reach important milestones</p>
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
        // Update badge count
        document.addEventListener('DOMContentLoaded', function() {
            const earnedBadges = document.querySelectorAll('.badge-card.earned');
            const totalBadges = document.querySelectorAll('.badge-card').length;
            document.getElementById('badgeCount').textContent = `${earnedBadges.length}/${totalBadges}`;
            
            // Initialize goal type selection
            const currentGoalType = "<?= $user['goal_type'] ?? 'lose' ?>";
            selectGoalType(currentGoalType);
            
            // Add animation to earned badges
            earnedBadges.forEach((badge, index) => {
                setTimeout(() => {
                    badge.style.animation = 'pulse 0.5s ease-out';
                    setTimeout(() => {
                        badge.style.animation = '';
                    }, 500);
                }, index * 200);
            });
            
            // Add hover effect to badges
            document.querySelectorAll('.badge-card').forEach(badge => {
                badge.addEventListener('mouseenter', function() {
                    if(this.classList.contains('earned')) {
                        const icon = this.querySelector('.badge-icon i.fa-dumbbell, .badge-icon i.fa-fire, .badge-icon i.fa-weight-scale, .badge-icon i.fa-calendar-check');
                        if(icon) {
                            icon.style.transform = 'scale(1.2)';
                            icon.style.transition = 'transform 0.3s';
                        }
                    }
                });
                
                badge.addEventListener('mouseleave', function() {
                    const icon = this.querySelector('.badge-icon i.fa-dumbbell, .badge-icon i.fa-fire, .badge-icon i.fa-weight-scale, .badge-icon i.fa-calendar-check');
                    if(icon) {
                        icon.style.transform = 'scale(1)';
                    }
                });
            });
        });
        
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
            
            // Update icons based on selection
            const icons = document.querySelectorAll('.goal-type-icon');
            icons.forEach(icon => {
                icon.style.color = '';
            });
            
            if(selectedOption) {
                const selectedIcon = selectedOption.querySelector('.goal-type-icon');
                if(selectedIcon) {
                    selectedIcon.style.color = 'var(--primary-color)';
                }
            }
        }
        
        // Form submission animation
        document.getElementById('goalForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            submitBtn.disabled = true;
            
            // Form will submit normally, this is just for visual feedback
        });
        
        // Add CSS animation for pulse effect
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            .badge-card.earned:hover .badge-icon {
                animation: pulse 1s infinite;
            }
        `;
        document.head.appendChild(style);
        
        // Simulate badge progress (in real app, this would come from backend)
        document.querySelectorAll('.badge-card:not(.earned)').forEach(badge => {
            const progressBar = badge.querySelector('.badge-progress-fill');
            if(progressBar) {
                // Random progress for demo (0-60%)
                const randomProgress = Math.floor(Math.random() * 60);
                progressBar.style.width = randomProgress + '%';
            }
        });
    </script>
</body>
</html>