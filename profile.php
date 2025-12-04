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
        
        // First check if columns exist
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'goal_weight'");
        $goal_weight_exists = $stmt->fetch();
        
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'goal_type'");
        $goal_type_exists = $stmt->fetch();
        
        if ($goal_weight_exists && $goal_type_exists) {
            // Columns exist, update normally
            $stmt = $pdo->prepare("UPDATE users SET goal_weight = ?, goal_type = ? WHERE id = ?");
            $stmt->execute([$goal_weight, $goal_type, $user_id]);
        } else {
            // Columns don't exist, try to create them first
            if (!$goal_weight_exists) {
                $pdo->exec("ALTER TABLE users ADD COLUMN goal_weight DECIMAL(5,2) DEFAULT NULL");
            }
            if (!$goal_type_exists) {
                $pdo->exec("ALTER TABLE users ADD COLUMN goal_type VARCHAR(20) DEFAULT 'lose'");
            }
            
            // Now update
            $stmt = $pdo->prepare("UPDATE users SET goal_weight = ?, goal_type = ? WHERE id = ?");
            $stmt->execute([$goal_weight, $goal_type, $user_id]);
        }
        
        // Update local user data
        $user['goal_weight'] = $goal_weight;
        $user['goal_type'] = $goal_type;
        
        // Set success message
        $_SESSION['success_message'] = "Goal updated successfully!";
        
        // Redirect to avoid form resubmission
        header("Location: profile.php");
        exit();
        
    } catch (PDOException $e) {
        // More specific error handling
        $error_message = "Database update error: " . $e->getMessage();
        
        // Check if it's a column missing error
        if (strpos($e->getMessage(), 'goal_weight') !== false) {
            try {
                // Try to add the missing column
                $pdo->exec("ALTER TABLE users ADD COLUMN goal_weight DECIMAL(5,2) DEFAULT NULL");
                $pdo->exec("ALTER TABLE users ADD COLUMN goal_type VARCHAR(20) DEFAULT 'lose'");
                
                // Retry the update
                $stmt = $pdo->prepare("UPDATE users SET goal_weight = ?, goal_type = ? WHERE id = ?");
                $stmt->execute([$goal_weight, $goal_type, $user_id]);
                
                $_SESSION['success_message'] = "Goal updated successfully!";
                header("Location: profile.php");
                exit();
                
            } catch (PDOException $e2) {
                $error_message .= " - Also failed to add columns: " . $e2->getMessage();
                die($error_message);
            }
        } else {
            die($error_message);
        }
    }
}

// Get achievements - with error handling
$earned_badges = [];
try {
    $stmt = $pdo->prepare("SELECT badge FROM achievements WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Extract badge names
    $earned_badges = array_column($badges, 'badge');
    
} catch (PDOException $e) {
    // If achievements table doesn't exist or has issues, use empty array
    $earned_badges = [];
}
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
            --gradient-warning: linear-gradient(135deg, #ffc107 0%, #ff9100 100%);
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
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(157, 78, 221, 0.1));
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.3);
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, var(--primary) 50%, var(--accent) 100%);
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
        
        /* User Profile Card */
        .user-profile-card {
            background: linear-gradient(145deg, rgba(0, 212, 255, 0.05), rgba(0, 212, 255, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.1);
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
            border: 5px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
        }
        
        .user-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .user-email {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        .user-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .user-stat {
            text-align: center;
            padding: 1rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .user-stat:hover {
            background: rgba(0, 212, 255, 0.08);
            transform: translateY(-3px);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Goal Card */
        .goal-card {
            background: linear-gradient(145deg, rgba(157, 78, 221, 0.05), rgba(157, 78, 221, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(157, 78, 221, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(157, 78, 221, 0.1);
        }
        
        .goal-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .goal-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-accent);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-right: 1rem;
        }
        
        .goal-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            flex: 1;
        }
        
        /* Form Elements */
        .form-label {
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: white;
            display: block;
            font-size: 1.1rem;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .input-unit {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-weight: 500;
            pointer-events: none;
        }
        
        .form-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: block;
        }
        
        /* Goal Type Selector */
        .goal-type-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .goal-type-option {
            text-align: center;
            padding: 1.5rem 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.03);
        }
        
        .goal-type-option:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            background: rgba(0, 212, 255, 0.08);
        }
        
        .goal-type-option.selected {
            border-color: var(--primary);
            background: rgba(0, 212, 255, 0.1);
            box-shadow: 0 10px 20px rgba(0, 212, 255, 0.2);
        }
        
        .goal-type-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: var(--primary);
        }
        
        .goal-type-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: white;
        }
        
        .goal-type-desc {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
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
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            color: white;
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
        
        .btn-w-100 {
            width: 100%;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 8px;
        }
        
        .btn-light {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .btn-light:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Badges Card */
        .badges-card {
            background: linear-gradient(145deg, rgba(255, 45, 117, 0.05), rgba(255, 45, 117, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 45, 117, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(255, 45, 117, 0.1);
        }
        
        .badges-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .badges-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-secondary);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-right: 1rem;
        }
        
        .badges-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
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
        
        /* Badges Grid */
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .badge-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .badge-card.earned {
            border-color: var(--success);
            background: rgba(0, 230, 118, 0.05);
        }
        
        .badge-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 15px 30px rgba(0, 212, 255, 0.15);
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
            box-shadow: 0 5px 15px rgba(0, 230, 118, 0.3);
        }
        
        .badge-icon.locked {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            color: rgba(255, 255, 255, 0.3);
        }
        
        .badge-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            color: white;
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
            background: rgba(0, 230, 118, 0.1);
            color: var(--success);
        }
        
        .status-locked {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* Empty State */
        .empty-badges {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-badges i {
            font-size: 4rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .empty-badges h4 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: white;
        }
        
        .empty-badges p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Tips Card */
        .tips-card {
            background: linear-gradient(145deg, rgba(255, 193, 7, 0.05), rgba(255, 193, 7, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 193, 7, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(255, 193, 7, 0.1);
        }
        
        /* Goal Preview */
        .goal-preview {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(157, 78, 221, 0.1));
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            text-align: center;
        }
        
        .goal-preview-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: white;
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
            color: white;
        }
        
        .goal-arrow {
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        /* Alerts */
        .alert {
            position: relative;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            border: 1px solid;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .alert-success {
            background: rgba(0, 230, 118, 0.1);
            border-color: rgba(0, 230, 118, 0.2);
            color: var(--success);
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
        
        /* Mobile Navigation */
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(10, 15, 35, 0.98);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.75rem;
            display: none;
            z-index: 1000;
            box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.3);
        }
        
        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .mobile-nav-item.active {
            background: rgba(0, 212, 255, 0.1);
            color: var(--primary);
        }
        
        .mobile-nav-item i {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
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
        
        /* Achievement Banner */
        .achievement-banner {
            background: linear-gradient(135deg, rgba(0, 230, 118, 0.1), rgba(0, 230, 118, 0.05));
            border: 1px solid rgba(0, 230, 118, 0.2);
            color: var(--success);
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
        
        /* Text Colors */
        .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        .text-success {
            color: var(--success) !important;
        }
        
        .small {
            color: rgba(255, 255, 255, 0.7);
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
                padding-bottom: 70px;
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
            
            .user-profile-card,
            .goal-card,
            .badges-card,
            .tips-card {
                padding: 1.5rem;
                border-radius: 24px;
                margin-bottom: 1.5rem;
            }
            
            .user-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
            
            .goal-icon,
            .badges-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .goal-title,
            .badges-title {
                font-size: 1.5rem;
            }
            
            .goal-type-selector {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .goal-type-option {
                padding: 1.25rem;
            }
            
            .user-stats {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.75rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .badges-grid {
                grid-template-columns: 1fr;
            }
            
            .goal-preview-values {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .form-control {
                padding: 0.875rem;
            }
            
            .btn {
                padding: 0.75rem 1.5rem;
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
            
            .user-profile-card,
            .goal-card,
            .badges-card,
            .tips-card {
                padding: 1.25rem;
                margin-bottom: 1.25rem;
            }
            
            .user-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            
            .user-name {
                font-size: 1.3rem;
            }
            
            .user-email {
                font-size: 0.9rem;
            }
            
            .goal-icon,
            .badges-icon {
                width: 45px;
                height: 45px;
                font-size: 1.3rem;
            }
            
            .goal-title,
            .badges-title {
                font-size: 1.3rem;
            }
            
            .user-stats {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .goal-type-option {
                padding: 1rem;
            }
            
            .goal-type-icon {
                font-size: 1.5rem;
            }
            
            .badge-icon {
                width: 60px;
                height: 60px;
                font-size: 1.8rem;
            }
            
            .form-control {
                padding: 0.75rem;
                font-size: 0.95rem;
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
        }
        
        /* Very small screens (under 400px) */
        @media (max-width: 399.98px) {
            .page-header h1 {
                font-size: 1.3rem;
            }
            
            .user-profile-card h4,
            .goal-card h4,
            .badges-card h4 {
                font-size: 1.1rem;
            }
            
            .goal-type-title {
                font-size: 1rem;
            }
            
            .goal-type-desc {
                font-size: 0.8rem;
            }
            
            .stat-value {
                font-size: 1.3rem;
            }
            
            .badge-name {
                font-size: 1rem;
            }
            
            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
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
                    // Define all badges
                    $all_badges = [
                        'First Workout' => ['icon' => 'fa-dumbbell', 'color' => '#00d4ff', 'desc' => 'Complete your first workout'],
                        '1000 Calories Logged' => ['icon' => 'fa-fire', 'color' => '#ff2d75', 'desc' => 'Log 1000 calories in meals'],
                        '5kg Lost' => ['icon' => 'fa-weight-scale', 'color' => '#00e676', 'desc' => 'Lose 5kg from starting weight'],
                        '30 Day Streak' => ['icon' => 'fa-calendar-check', 'color' => '#ffc107', 'desc' => 'Maintain a 30-day workout streak']
                    ];
                    
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
        });
    </script>
</body>
</html>