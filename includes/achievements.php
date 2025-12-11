<?php
// includes/achievements.php

/**
 * Check and award achievements for a user
 */
function check_and_award_achievements($pdo, $user_id) {
    // Define all badges with their conditions
    $all_badges = [
        'First Workout' => [
            'icon' => 'fa-dumbbell', 
            'color' => '#00d4ff', 
            'desc' => 'Complete your first workout',
            'check_function' => 'check_first_workout'
        ],
        '1000 Calories Logged' => [
            'icon' => 'fa-fire', 
            'color' => '#ff2d75', 
            'desc' => 'Log 1000 calories in meals',
            'check_function' => 'check_calories_logged'
        ],
        '5kg Lost' => [
            'icon' => 'fa-weight-scale', 
            'color' => '#00e676', 
            'desc' => 'Lose 5kg from starting weight',
            'check_function' => 'check_weight_loss'
        ],
        '30 Day Streak' => [
            'icon' => 'fa-calendar-check', 
            'color' => '#ffc107', 
            'desc' => 'Maintain a 30-day workout streak',
            'check_function' => 'check_streak'
        ]
    ];

    // Function to check and award the "First Workout" badge
    function check_first_workout($pdo, $user_id) {
        try {
            // Check if user has any completed workouts
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM workouts WHERE user_id = ? AND completed = 1");
            $stmt->execute([$user_id]);
            $workoutCount = $stmt->fetchColumn();
            
            return $workoutCount >= 1; // Changed to >= 1 to be more reliable
        } catch (PDOException $e) {
            error_log("First workout check error: " . $e->getMessage());
            return false;
        }
    }

    // Function to check and award the "1000 Calories Logged" badge
    function check_calories_logged($pdo, $user_id) {
        try {
            // Check total calories logged
            $stmt = $pdo->prepare("SELECT SUM(calories) FROM meals WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $totalCalories = $stmt->fetchColumn();
            
            return $totalCalories >= 1000;
        } catch (PDOException $e) {
            error_log("Calories check error: " . $e->getMessage());
            return false;
        }
    }

    // Function to check and award the "5kg Lost" badge
    function check_weight_loss($pdo, $user_id) {
        try {
            // Get user's starting weight (first weight entry)
            $stmt = $pdo->prepare("SELECT weight FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $startingWeight = $stmt->fetchColumn();
            
            // Get current weight from users table
            $currentWeight = $startingWeight; // For now, use starting weight
            
            // Check if weight_logs table exists and has data
            $tableExists = $pdo->query("SHOW TABLES LIKE 'weight_logs'")->fetch();
            if ($tableExists) {
                $stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$user_id]);
                $latestWeight = $stmt->fetchColumn();
                if ($latestWeight) {
                    $currentWeight = $latestWeight;
                }
            }
            
            if ($startingWeight && $currentWeight && $startingWeight > 0) {
                $weightLost = $startingWeight - $currentWeight;
                return $weightLost >= 5;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Weight loss check error: " . $e->getMessage());
            return false;
        }
    }

    // Function to check and award the "30 Day Streak" badge
    function check_streak($pdo, $user_id) {
        try {
            // Count distinct days with completed workouts
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(completed_at)) FROM workouts WHERE user_id = ? AND completed = 1");
            $stmt->execute([$user_id]);
            $daysWithWorkouts = $stmt->fetchColumn();
            
            return $daysWithWorkouts >= 30;
        } catch (PDOException $e) {
            error_log("Streak check error: " . $e->getMessage());
            return false;
        }
    }

    // Ensure achievements table exists
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            badge VARCHAR(50) NOT NULL,
            earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_badge (user_id, badge),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    } catch (PDOException $e) {
        error_log("Achievements table creation error: " . $e->getMessage());
    }

    $newly_awarded = [];
    
    foreach ($all_badges as $badge_name => $badge_info) {
        // Check if user already has this badge
        $stmt = $pdo->prepare("SELECT id FROM achievements WHERE user_id = ? AND badge = ?");
        $stmt->execute([$user_id, $badge_name]);
        $already_has = $stmt->fetch();
        
        if (!$already_has) {
            // Check if user qualifies for the badge
            $function_name = $badge_info['check_function'];
            if (function_exists($function_name)) {
                $qualifies = $function_name($pdo, $user_id);
                
                if ($qualifies) {
                    // Award the badge
                    try {
                        $stmt = $pdo->prepare("INSERT INTO achievements (user_id, badge, earned_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$user_id, $badge_name]);
                        $newly_awarded[] = $badge_name;
                        
                        // Set session message for newly awarded badge
                        $_SESSION['new_badge'] = $badge_name;
                        
                    } catch (PDOException $e) {
                        // Might be duplicate due to race condition, ignore
                        error_log("Award badge error: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    return $newly_awarded;
}

/**
 * Get user's earned badges
 */
function get_user_badges($pdo, $user_id) {
    $earned_badges = [];
    try {
        $stmt = $pdo->prepare("SELECT badge FROM achievements WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Extract badge names
        $earned_badges = array_column($badges, 'badge');
        
    } catch (PDOException $e) {
        error_log("Get badges error: " . $e->getMessage());
    }
    
    return $earned_badges;
}
?>