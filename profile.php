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

        /* ============================================
   PREMIUM MOBILE RESPONSIVE DESIGN - PROFILE
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
        border-radius: 0 0 32px 32px;
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
    
    /* Main content row */
    .row {
        margin: 0 !important;
    }
    
    .col-lg-8,
    .col-lg-4 {
        padding: 0 !important;
    }
    
    /* User Profile Card - Mobile Optimized */
    .user-profile-card {
        margin: 0 0.75rem 1.5rem 0.75rem !important;
        padding: 1.5rem !important;
        border-radius: 20px !important;
    }
    
    .user-avatar {
        width: 100px !important;
        height: 100px !important;
        font-size: 2.5rem !important;
        margin-bottom: 1rem !important;
        border-width: 4px !important;
    }
    
    .user-name {
        font-size: 1.3rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .user-email {
        font-size: 0.9rem !important;
        margin-bottom: 1rem !important;
    }
    
    .user-stats {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 0.75rem !important;
        margin-top: 1rem !important;
    }
    
    .stat-value {
        font-size: 1.3rem !important;
        margin-bottom: 0.125rem !important;
    }
    
    .stat-label {
        font-size: 0.75rem !important;
    }
    
    /* Goal Card - Mobile Optimized */
    .goal-card {
        margin: 0 0.75rem 1.5rem 0.75rem !important;
        padding: 1.5rem !important;
        border-radius: 20px !important;
    }
    
    .goal-header {
        flex-direction: column;
        align-items: flex-start;
        text-align: center;
        gap: 1rem;
        margin-bottom: 1.25rem !important;
        padding-bottom: 1rem !important;
    }
    
    .goal-icon {
        width: 50px !important;
        height: 50px !important;
        font-size: 1.5rem !important;
        margin: 0 auto !important;
    }
    
    .goal-title {
        font-size: 1.2rem !important;
        width: 100%;
        text-align: center;
    }
    
    /* Achievement Banner */
    .achievement-banner {
        border-radius: 16px !important;
        padding: 0.875rem !important;
        margin-bottom: 1.25rem !important;
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .achievement-banner .btn-sm {
        align-self: flex-end;
        margin-top: -0.5rem;
    }
    
    /* Form Styles */
    .mb-4 {
        margin-bottom: 1.25rem !important;
    }
    
    .form-label {
        font-size: 0.95rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    .form-control {
        padding: 0.875rem !important;
        border-radius: 12px !important;
        font-size: 1rem !important;
        min-height: 56px !important;
    }
    
    .input-unit {
        right: 0.875rem !important;
        font-size: 0.95rem !important;
    }
    
    .form-text {
        font-size: 0.85rem !important;
        margin-top: 0.25rem !important;
    }
    
    /* Goal Type Selector - Mobile Optimized */
    .goal-type-selector {
        grid-template-columns: 1fr !important;
        gap: 0.75rem !important;
        margin-bottom: 1.25rem !important;
    }
    
    .goal-type-option {
        padding: 1.25rem 1rem !important;
        border-radius: 16px !important;
        text-align: left !important;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .goal-type-option:hover {
        transform: translateY(-2px) !important;
    }
    
    .goal-type-icon {
        font-size: 1.8rem !important;
        margin-bottom: 0 !important;
        flex-shrink: 0;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(58, 134, 255, 0.1);
        border-radius: 12px;
    }
    
    .goal-type-title {
        font-size: 1.1rem !important;
        margin-bottom: 0.125rem !important;
    }
    
    .goal-type-desc {
        font-size: 0.85rem !important;
    }
    
    /* Goal Preview */
    .goal-preview {
        border-radius: 16px !important;
        padding: 1.25rem !important;
        margin-top: 1.25rem !important;
    }
    
    .goal-preview-title {
        font-size: 1.1rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .goal-preview-values {
        flex-direction: column !important;
        gap: 0.5rem !important;
        margin: 0.75rem 0 !important;
    }
    
    .goal-value {
        font-size: 1.6rem !important;
    }
    
    .goal-arrow {
        transform: rotate(90deg);
        font-size: 1.25rem !important;
    }
    
    /* Submit Button */
    .btn-primary {
        padding: 0.875rem !important;
        font-size: 1rem !important;
        border-radius: 14px !important;
        min-height: 56px !important;
        margin-top: 1rem !important;
    }
    
    .btn-primary i {
        font-size: 1.1rem !important;
        margin-right: 0.5rem !important;
    }
    
    /* Badges Card - Mobile Optimized */
    .badges-card {
        margin: 0 0.75rem 1.5rem 0.75rem !important;
        padding: 1.5rem !important;
        border-radius: 20px !important;
    }
    
    .badges-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 1rem;
        margin-bottom: 1.5rem !important;
        padding-bottom: 1rem !important;
    }
    
    .badges-icon {
        width: 50px !important;
        height: 50px !important;
        font-size: 1.5rem !important;
        margin: 0 auto !important;
    }
    
    .badges-title {
        font-size: 1.2rem !important;
        text-align: center;
    }
    
    .badge-count {
        font-size: 0.85rem !important;
        padding: 0.25rem 0.625rem !important;
    }
    
    /* Badges Grid */
    .badges-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
    
    .badge-card {
        padding: 1.25rem !important;
        border-radius: 16px !important;
    }
    
    .badge-icon {
        width: 70px !important;
        height: 70px !important;
        font-size: 2rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .badge-name {
        font-size: 1.1rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .badge-status {
        font-size: 0.8rem !important;
        padding: 0.25rem 0.625rem !important;
    }
    
    /* Empty Badges */
    .empty-badges {
        padding: 2rem 1rem !important;
    }
    
    .empty-badges i {
        font-size: 3rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .empty-badges h4 {
        font-size: 1.2rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    .empty-badges p {
        font-size: 0.9rem !important;
        max-width: 250px;
        margin: 0 auto;
    }
    
    /* Tips Card */
    .badges-card.mt-4 {
        margin-top: 0 !important;
        margin-bottom: 1.5rem !important;
    }
    
    .d-flex.align-items-start {
        margin-bottom: 1rem !important;
    }
    
    .d-flex.align-items-start i {
        margin-top: 0.125rem;
    }
    
    /* Buttons - Mobile Optimized */
    .btn {
        padding: 0.625rem 1rem !important;
        font-size: 0.9rem !important;
        border-radius: 12px !important;
        min-height: 48px !important;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem !important;
        min-height: 36px !important;
        font-size: 0.8rem !important;
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
    
    .user-profile-card,
    .goal-card,
    .badge-card,
    .badges-card {
        animation: fadeInUp 0.5s ease-out;
    }
    
    /* Stagger animations */
    .user-profile-card { animation-delay: 0.1s; }
    .goal-card { animation-delay: 0.2s; }
    .badge-card:nth-child(1) { animation-delay: 0.3s; }
    .badge-card:nth-child(2) { animation-delay: 0.4s; }
    .badge-card:nth-child(3) { animation-delay: 0.5s; }
    .badge-card:nth-child(4) { animation-delay: 0.6s; }
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
    
    .user-profile-card,
    .goal-card,
    .badges-card {
        margin: 0 0.5rem 1rem 0.5rem !important;
        padding: 1.25rem !important;
    }
    
    .user-avatar {
        width: 90px !important;
        height: 90px !important;
        font-size: 2.2rem !important;
    }
    
    .user-name {
        font-size: 1.2rem !important;
    }
    
    .user-email {
        font-size: 0.85rem !important;
    }
    
    .user-stats {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
    
    .stat-value {
        font-size: 1.4rem !important;
    }
    
    .goal-type-option {
        flex-direction: column;
        text-align: center !important;
        gap: 0.75rem;
    }
    
    .goal-type-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .goal-value {
        font-size: 1.4rem !important;
    }
    
    .badge-icon {
        width: 60px !important;
        height: 60px !important;
        font-size: 1.8rem !important;
    }
    
    .btn-primary {
        font-size: 0.95rem !important;
        padding: 0.75rem !important;
    }
    
    .empty-badges {
        padding: 1.5rem 1rem !important;
    }
    
    .empty-badges i {
        font-size: 2.5rem !important;
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
    
    .goal-type-selector {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    .goal-type-option:nth-child(3) {
        grid-column: span 2;
    }
    
    .badges-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    .user-stats {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 0.75rem;
    }
}

/* Landscape mode optimization */
@media (max-height: 700px) and (orientation: landscape) {
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
    
    .user-profile-card,
    .goal-card,
    .badges-card {
        margin-bottom: 1rem !important;
        padding: 1rem !important;
    }
    
    .user-avatar {
        width: 80px !important;
        height: 80px !important;
        font-size: 2rem !important;
    }
    
    .goal-type-option {
        padding: 1rem !important;
    }
    
    .goal-type-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .badge-card {
        padding: 1rem !important;
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

.btn-primary:disabled {
    animation: buttonPulse 1s infinite;
}

/* Touch feedback for mobile */
@media (hover: none) and (pointer: coarse) {
    .goal-type-option:hover,
    .badge-card:hover {
        transform: none !important;
    }
    
    .goal-type-option:active,
    .badge-card:active {
        transform: scale(0.98) !important;
    }
    
    .btn-primary:hover {
        transform: none !important;
    }
    
    .btn-primary:active {
        transform: scale(0.98) !important;
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
        
        .user-profile-card,
        .goal-card,
        .badges-card {
            background: #1e293b;
            color: #e2e8f0;
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
        
        .form-control {
            background: #2d3748;
            border-color: #374151;
            color: #e2e8f0;
        }
        
        .form-control:focus {
            background: #2d3748;
            border-color: #3a86ff;
        }
        
        .input-unit {
            background: #2d3748;
            color: #94a3b8;
        }
        
        .goal-type-option {
            background: #2d3748;
            border-color: #374151;
            color: #e2e8f0;
        }
        
        .goal-type-option.selected {
            background: rgba(58, 134, 255, 0.2);
            border-color: #3a86ff;
        }
        
        .goal-type-icon {
            background: rgba(58, 134, 255, 0.2);
        }
        
        .goal-preview {
            background: rgba(58, 134, 255, 0.15);
        }
        
        .badge-card {
            background: #2d3748;
            border-color: #374151;
        }
        
        .badge-card.earned {
            background: rgba(56, 176, 0, 0.15);
            border-color: #38b000;
        }
        
        .badge-icon.locked {
            background: #374151;
            color: #64748b;
        }
        
        .stat-label,
        .user-email,
        .goal-type-desc,
        .form-text,
        .small,
        .empty-badges,
        .badge-status.status-locked {
            color: #94a3b8 !important;
        }
        
        .stat-value,
        .goal-value,
        .badge-name {
            color: #f1f5f9;
        }
        
        .achievement-banner {
            background: rgba(56, 176, 0, 0.2);
            color: #e2e8f0;
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

/* Column animations */
.col-lg-8 .user-profile-card,
.col-lg-8 .goal-card {
    animation: slideInFromLeft 0.5s ease-out;
}

.col-lg-4 .badges-card {
    animation: slideInFromRight 0.5s ease-out;
}

/* Achievement banner animation */
@keyframes achievementBounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

.achievement-banner {
    animation: achievementBounce 0.8s ease-out, slideIn 0.5s ease-out;
}

/* Form focus states */
.form-control:focus,
.goal-type-option.selected {
    box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
}

/* Responsive input improvements */
.input-group {
    position: relative;
}

.input-group:focus-within {
    transform: translateY(-2px);
    transition: transform 0.3s ease;
}

/* Interactive badge hover states */
@media (hover: hover) and (pointer: fine) {
    .badge-card:hover .badge-icon.earned {
        transform: scale(1.1);
        transition: transform 0.3s ease;
    }
}

/* Loading state for form submission */
.btn-primary.loading i.fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Responsive spacing for better mobile readability */
@media (max-width: 767.98px) {
    h1, h2, h3, h4, h5, h6 {
        margin-bottom: 0.75rem !important;
    }
    
    p, div, span {
        margin-bottom: 0.5rem !important;
    }
    
    .mb-4 {
        margin-bottom: 1rem !important;
    }
    
    .mb-5 {
        margin-bottom: 1.5rem !important;
    }
    
    .mt-4 {
        margin-top: 1rem !important;
    }
    
    .mt-5 {
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
    .container.mt-4 {
        padding-top: 0.5rem !important;
    }
    
    /* Prevent horizontal scrolling */
    body {
        overflow-x: hidden;
        width: 100%;
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
    
    .form-control {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
}

/* Accessibility improvements for mobile */
@media (max-width: 767.98px) {
    .btn, a, input[type="submit"], button {
        min-height: 44px !important;
        min-width: 44px !important;
    }
    
    .form-control, select, textarea {
        min-height: 44px !important;
    }
    
    /* Focus styles for better accessibility */
    *:focus {
        outline: 2px solid #3a86ff !important;
        outline-offset: 2px !important;
    }
}

/* Smooth transitions */
* {
    transition: background-color 0.3s ease, color 0.3s ease;
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