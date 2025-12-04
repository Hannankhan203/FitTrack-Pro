<?php
require '../includes/functions.php';
require_login();

// Fetch saved exercises for the current user
require_once '../includes/db.php';
$user_id = get_user_id();
$date = date('Y-m-d');

// Get saved exercises for today
$stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? AND DATE(date) = CURDATE() ORDER BY id DESC");
$stmt->execute([$user_id]);
$saved_exercises = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log Workout - FitTrack Pro</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <!-- Select2 for better dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(157, 78, 221, 0.1));
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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
            color: var(--primary);
        }

        /* Saved Exercises Container */
        .saved-exercises-container {
            background: linear-gradient(145deg, rgba(0, 212, 255, 0.05), rgba(0, 212, 255, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.1);
        }

        .saved-exercises-container h4 {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
        }

        /* Workout Form Container */
        .workout-form-container {
            background: linear-gradient(145deg, rgba(255, 45, 117, 0.05), rgba(255, 45, 117, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 45, 117, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(255, 45, 117, 0.1);
        }

        .workout-form-container h4 {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
        }

        /* Exercise Card */
        .exercise-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 2rem;
            border-left: 5px solid var(--primary);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .exercise-card:hover {
            transform: translateY(-5px);
            background: rgba(0, 212, 255, 0.08);
            border-left-color: var(--secondary);
            box-shadow: 0 15px 40px rgba(0, 212, 255, 0.15);
        }

        .exercise-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .exercise-number {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .exercise-badge {
            background: var(--gradient-primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .exercise-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: white;
            margin: 0;
        }

        .exercise-type-indicator {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            /* border: 10px solid black; */
        }

        .type-strength {
            background: rgba(0, 212, 255, 0.1);
            color: var(--primary);
        }

        .type-cardio {
            background: rgba(255, 45, 117, 0.1);
            color: var(--secondary);
        }

        .remove-btn {
            background: var(--gradient-secondary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 20px rgba(255, 45, 117, 0.3);
        }

        /* Saved Exercise Card */
        .saved-exercise-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 1.8rem;
            border-left: 5px solid var(--primary);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            margin-bottom: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .saved-exercise-card:hover {
            transform: translateY(-5px);
            background: rgba(0, 212, 255, 0.08);
            border-left-color: var(--secondary);
            box-shadow: 0 15px 40px rgba(0, 212, 255, 0.15);
        }

        .saved-exercise-info {
            flex: 1;
        }

        .saved-exercise-name {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 0.8rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .saved-exercise-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .saved-exercise-detail {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .saved-exercise-detail i {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .saved-exercise-detail span {
            display: block;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: white;
        }

        .saved-exercise-detail .detail-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .saved-exercise-actions {
            margin-left: 1.5rem;
        }

        .delete-btn {
            background: var(--gradient-secondary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .delete-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 20px rgba(255, 45, 117, 0.3);
        }

        /* Form Elements */
        .form-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.8rem;
            display: block;
        }

        .exercise-select {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .exercise-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
        }

        .input-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .input-field {
            position: relative;
        }

        .input-field input {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-field input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
        }

        .unit-label {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            pointer-events: none;
        }

        .small.text-muted {
            color: rgba(255, 255, 255, 0.6) !important;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: block;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        /* Quick Tips */
        .quick-tips-container {
            background: linear-gradient(145deg, rgba(157, 78, 221, 0.05), rgba(157, 78, 221, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(157, 78, 221, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(157, 78, 221, 0.1);
        }

        .quick-tips-container h5 {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .tip-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .tip-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-3px);
        }

        .tip-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: rgba(0, 212, 255, 0.1);
            color: var(--primary);
        }

        .tip-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .tip-desc {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Empty State */
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

        /* Floating Action Button */
        .floating-action {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .floating-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--gradient-secondary);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(255, 45, 117, 0.3);
        }

        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 30px rgba(255, 45, 117, 0.4);
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

        /* Select2 Customization */
        .select2-container--default .select2-selection--single {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 2px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            height: 54px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: white !important;
            line-height: 54px !important;
            padding-left: 16px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 52px !important;
        }

        .select2-dropdown {
            background: var(--dark) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
        }

        .select2-results__option {
            color: white !important;
            padding: 12px 16px !important;
        }

        .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary) !important;
        }

        .select2-results__option[aria-selected=true] {
            background-color: rgba(0, 212, 255, 0.2) !important;
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

            .saved-exercises-container,
            .workout-form-container,
            .quick-tips-container {
                padding: 1.5rem;
                border-radius: 24px;
                margin-bottom: 1.5rem;
            }

            .exercise-card,
            .saved-exercise-card {
                padding: 1.2rem;
                border-radius: 18px;
            }

            .exercise-header {
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
            }

            .exercise-title {
                font-size: 1.2rem;
            }

            .exercise-badge {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .input-row {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }

            .saved-exercise-details {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }

            .form-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .form-actions button {
                width: 100%;
            }

            .floating-action {
                bottom: 20px;
                right: 20px;
            }

            .navbar-collapse {
                top: 70px;
            }

            .navbar-collapse.show::before {
                top: 70px;
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

            .saved-exercises-container h4,
            .workout-form-container h4 {
                font-size: 1.2rem;
            }

            .exercise-card,
            .saved-exercise-card {
                padding: 1rem;
            }

            .exercise-title {
                font-size: 1.1rem;
            }

            .exercise-badge,
            .remove-btn {
                width: 25px;
                height: 25px;
                font-size: 0.8rem;
            }

            .btn-primary,
            .btn-success {
                flex-direction: column !important;
                font-size: 0.7rem !important;
                padding: 0.4rem 0.6rem !important;
                text-align: center !important;
            }

            .saved-exercise-name {
                font-size: 1.1rem;
                flex-wrap: wrap;
            }

            .saved-exercise-details {
                grid-template-columns: 1fr;
            }

            .saved-exercise-actions {
                margin-left: 0;
                margin-top: 1rem;
                align-self: flex-end;
            }

            .saved-exercise-card {
                flex-direction: column;
                align-items: stretch;
            }

            .input-field input {
                padding: 0.875rem;
                font-size: 0.7rem;
                /* border: 10px solid black; */
            }

            .btn {
                padding: 0.75rem 1.25rem;
                font-size: 0.95rem;
            }

            .floating-btn {
                width: 56px;
                height: 56px;
                font-size: 1.4rem;
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
                        <a class="nav-link active" href="log.php">
                            <i class="fas fa-dumbbell"></i> Workouts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../meals/planner.php">
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

    <div class="container mt-4 mb-5">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-dumbbell me-2"></i>Log Your Workout</h1>
            <p>Track your exercises and monitor your progress for today's session</p>
            <div class="date-display">
                <i class="fas fa-calendar-alt"></i>
                <span><?php echo date('F j, Y'); ?></span>
            </div>
        </div>

        <!-- Saved Exercises Section -->
        <?php if (!empty($saved_exercises)): ?>
            <div class="saved-exercises-container">
                <h4><i class="fas fa-history me-2 text-primary"></i>Today's Saved Exercises</h4>
                <p class="text-muted mb-3">Exercises you've saved today. Click delete to remove them.</p>

                <?php foreach ($saved_exercises as $exercise): ?>
                    <div class="saved-exercise-card" id="saved-exercise-<?= $exercise['id'] ?>">
                        <div class="saved-exercise-info">
                            <div class="saved-exercise-name">
                                <?php
                                $icon = '';
                                $exercise_name = $exercise['exercise'];

                                // Determine exercise type based on name
                                $exercise_type = 'strength'; // Default
                                $cardio_exercises = ['Running', 'Cycling', 'Swimming', 'Jump Rope', 'Elliptical', 'Burpees', 'Rowing', 'Stair Climber'];
                                if (in_array($exercise_name, $cardio_exercises)) {
                                    $exercise_type = 'cardio';
                                }

                                // Set icon based on exercise name
                                if ($exercise_name === 'Push-ups') $icon = 'fa-person-burst';
                                elseif ($exercise_name === 'Tricep Extensions') $icon = 'fa-arrow-up-from-bracket';
                                elseif ($exercise_name === 'Bench Press') $icon = 'fa-weight-hanging';
                                elseif ($exercise_name === 'Squats') $icon = 'fa-person';
                                elseif ($exercise_name === 'Deadlifts') $icon = 'fa-dumbbell';
                                elseif ($exercise_name === 'Pull-ups') $icon = 'fa-arrow-up';
                                elseif ($exercise_name === 'Running') $icon = 'fa-person-running';
                                elseif ($exercise_name === 'Cycling') $icon = 'fa-bicycle';
                                elseif ($exercise_name === 'Swimming') $icon = 'fa-person-swimming';
                                elseif ($exercise_name === 'Jump Rope') $icon = 'fa-arrow-rotate-right';
                                elseif ($exercise_name === 'Elliptical') $icon = 'fa-person-walking';
                                elseif ($exercise_name === 'Bicep Curls') $icon = 'fa-hand-fist';
                                elseif ($exercise_name === 'Shoulder Press') $icon = 'fa-up-long';
                                elseif ($exercise_name === 'Lunges') $icon = 'fa-shoe-prints';
                                elseif ($exercise_name === 'Plank') $icon = 'fa-ruler-horizontal';
                                elseif ($exercise_name === 'Burpees') $icon = 'fa-fire';
                                elseif ($exercise_name === 'Rowing') $icon = 'fa-water';
                                elseif ($exercise_name === 'Stair Climber') $icon = 'fa-stairs';
                                else $icon = 'fa-dumbbell';
                                ?>
                                <i class="fas <?= $icon ?> me-2 text-<?= $exercise_type === 'strength' ? 'primary' : 'danger' ?>"></i>
                                <?= htmlspecialchars($exercise['exercise']) ?>
                                <span class="exercise-type-indicator <?= $exercise_type === 'strength' ? 'type-strength' : 'type-cardio' ?>">
                                    <?= ucfirst($exercise_type) ?>
                                </span>
                            </div>
                            <div class="saved-exercise-details">
                                <?php if ($exercise_type === 'strength'): ?>
                                    <?php if (isset($exercise['sets']) && $exercise['sets']): ?>
                                        <div class="saved-exercise-detail">
                                            <i class="fas fa-redo text-primary"></i>
                                            <span><?= $exercise['sets'] ?></span>
                                            <div class="detail-label">Sets</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($exercise['reps']) && $exercise['reps']): ?>
                                        <div class="saved-exercise-detail">
                                            <i class="fas fa-sync-alt text-primary"></i>
                                            <span><?= $exercise['reps'] ?></span>
                                            <div class="detail-label">Reps</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($exercise['weight']) && $exercise['weight'] && !in_array($exercise['exercise'], ['Pull-ups', 'Push-ups', 'Lunges', 'Plank'])): ?>
                                        <div class="saved-exercise-detail">
                                            <i class="fas fa-weight text-primary"></i>
                                            <span><?= $exercise['weight'] ?> kg</span>
                                            <div class="detail-label">Weight</div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (isset($exercise['duration']) && $exercise['duration']): ?>
                                        <div class="saved-exercise-detail">
                                            <i class="fas fa-clock text-danger"></i>
                                            <span><?= $exercise['duration'] ?> min</span>
                                            <div class="detail-label">Duration</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($exercise['distance']) && $exercise['distance']): ?>
                                        <div class="saved-exercise-detail">
                                            <i class="fas fa-road text-danger"></i>
                                            <span><?= $exercise['distance'] ?> km</span>
                                            <div class="detail-label">Distance</div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="saved-exercise-detail">
                                    <i class="fas fa-calendar text-muted"></i>
                                    <span>Today</span>
                                    <div class="detail-label">Date</div>
                                </div>
                            </div>
                        </div>
                        <div class="saved-exercise-actions">
                            <button type="button" class="delete-btn" onclick="deleteSavedExercise(<?= $exercise['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Workout Form -->
        <div class="workout-form-container">
            <h4><i class="fas fa-plus-circle me-2 text-primary"></i>Add New Exercises</h4>
            <form id="workoutForm">
                <div id="exercisesContainer">
                    <!-- Exercise cards will be added here -->
                </div>

                <!-- Empty State (shown when no exercises) -->
                <div id="emptyState" class="empty-state" style="display: none;">
                    <div class="empty-state-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h4>No Exercises Added</h4>
                    <p>Click the button below to add your first exercise</p>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='../dashboard.php'">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="addExerciseBtn">
                            <i class="fas fa-plus me-2"></i>Add Exercise
                        </button>
                        <button type="button" class="btn btn-success" id="saveBtn">
                            <i class="fas fa-save me-2"></i>Save Workout
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Quick Tips -->
        <div class="quick-tips-container">
            <h5><i class="fas fa-lightbulb me-2 text-warning"></i>Workout Tips</h5>
            <div class="row mt-3">
                <div class="col-md-4 mb-3">
                    <div class="tip-item">
                        <div class="tip-icon">
                            <i class="fas fa-weight-hanging"></i>
                        </div>
                        <h6 class="tip-title">Progressive Overload</h6>
                        <p class="tip-desc">Gradually increase weight or reps to continue making progress.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="tip-item">
                        <div class="tip-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h6 class="tip-title">Rest Periods</h6>
                        <p class="tip-desc">Take 60-90 seconds rest between sets for optimal recovery.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="tip-item">
                        <div class="tip-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h6 class="tip-title">Form First</h6>
                        <p class="tip-desc">Always prioritize proper form over heavier weights to prevent injury.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button (Mobile) -->
    <div class="floating-action d-lg-none">
        <button type="button" class="floating-btn" id="addExerciseFloat">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Initialize exercises array
        const exercises = <?php
                            $exercises = [
                                ['name' => 'Bench Press', 'type' => 'strength', 'icon' => 'fa-weight-hanging'],
                                ['name' => 'Squats', 'type' => 'strength', 'icon' => 'fa-person'],
                                ['name' => 'Deadlifts', 'type' => 'strength', 'icon' => 'fa-dumbbell'],
                                ['name' => 'Pull-ups', 'type' => 'strength', 'icon' => 'fa-arrow-up'],
                                ['name' => 'Push-ups', 'type' => 'strength', 'icon' => 'fa-person-burst'],
                                ['name' => 'Running', 'type' => 'cardio', 'icon' => 'fa-person-running'],
                                ['name' => 'Cycling', 'type' => 'cardio', 'icon' => 'fa-bicycle'],
                                ['name' => 'Swimming', 'type' => 'cardio', 'icon' => 'fa-person-swimming'],
                                ['name' => 'Jump Rope', 'type' => 'cardio', 'icon' => 'fa-arrow-rotate-right'],
                                ['name' => 'Elliptical', 'type' => 'cardio', 'icon' => 'fa-person-walking'],
                                ['name' => 'Bicep Curls', 'type' => 'strength', 'icon' => 'fa-hand-fist'],
                                ['name' => 'Tricep Extensions', 'type' => 'strength', 'icon' => 'fa-arrow-up-from-bracket'],
                                ['name' => 'Shoulder Press', 'type' => 'strength', 'icon' => 'fa-up-long'],
                                ['name' => 'Lunges', 'type' => 'strength', 'icon' => 'fa-shoe-prints'],
                                ['name' => 'Plank', 'type' => 'strength', 'icon' => 'fa-ruler-horizontal'],
                                ['name' => 'Burpees', 'type' => 'cardio', 'icon' => 'fa-fire'],
                                ['name' => 'Rowing', 'type' => 'cardio', 'icon' => 'fa-water'],
                                ['name' => 'Stair Climber', 'type' => 'cardio', 'icon' => 'fa-stairs']
                            ];
                            echo json_encode($exercises);
                            ?>;

        // Exercises that don't require weight input
        const noWeightExercises = ['Pull-ups', 'Push-ups', 'Lunges', 'Plank'];

        let exerciseCounter = 0;

        // Create exercise card HTML
        function createExerciseCard() {
            exerciseCounter++;
            const cardId = `exercise-${exerciseCounter}`;

            const options = exercises.map(ex =>
                `<option value="${ex.name}" data-type="${ex.type}" data-icon="${ex.icon}">${ex.name}</option>`
            ).join('');

            return `
            <div class="exercise-card" id="${cardId}">
                <div class="exercise-header">
                    <div class="exercise-number">
                        <span class="exercise-badge">${exerciseCounter}</span>
                        <h3 class="exercise-title">Exercise ${exerciseCounter}</h3>
                        <span class="exercise-type-indicator type-strength" style="display:none;">
                            <i class="fas fa-dumbbell me-1"></i>Strength
                        </span>
                        <span class="exercise-type-indicator type-cardio" style="display:none;">
                            <i class="fas fa-running me-1"></i>Cardio
                        </span>
                    </div>
                    <button type="button" class="remove-btn" onclick="removeExercise('${cardId}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-3">
                    <label for="exercise-select-${exerciseCounter}" class="form-label">Select Exercise</label>
                    <select class="exercise-select" id="exercise-select-${exerciseCounter}" required>
                        <option value="">Choose an exercise...</option>
                        ${options}
                    </select>
                </div>
                
                <div class="strength-fields">
                    <label class="form-label">Strength Details</label>
                    <div class="input-row">
                        <div class="input-field">
                            <input type="number" class="sets-input" placeholder="Sets" min="1" required>
                            <div class="small text-muted mt-1">Number of sets</div>
                        </div>
                        <div class="input-field">
                            <input type="number" class="reps-input" placeholder="Reps" min="1" required>
                            <div class="small text-muted mt-1">Reps per set</div>
                        </div>
                        <div class="input-field weight-field">
                            <div class="unit-container">
                                <input type="number" step="0.5" class="weight-input" placeholder="Weight">
                                <span class="unit-label">kg</span>
                            </div>
                            <div class="small text-muted mt-1">Weight per rep (optional)</div>
                        </div>
                    </div>
                </div>
                
                <div class="duration-field" style="display:none;">
                    <label class="form-label">Cardio Duration</label>
                    <div class="input-row">
                        <div class="input-field">
                            <div class="unit-container">
                                <input type="number" class="duration-input" placeholder="Duration" min="1" required>
                                <span class="unit-label">min</span>
                            </div>
                            <div class="small text-muted mt-1">Duration in minutes</div>
                        </div>
                        <div class="input-field">
                            <div class="unit-container">
                                <input type="number" step="0.1" class="distance-input" placeholder="Distance">
                                <span class="unit-label">km</span>
                            </div>
                            <div class="small text-muted mt-1">Distance (optional)</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        }

        // Initialize Select2 on exercise selects
        function initializeSelect2(selectElement) {
            $(selectElement).select2({
                placeholder: "Choose an exercise...",
                allowClear: false,
                width: '100%',
                templateResult: formatExercise,
                templateSelection: formatExercise
            }).on('select2:select', function(e) {
                toggleFields(this);
            });
        }

        function formatExercise(exercise) {
            if (!exercise.id) return exercise.text;

            const ex = exercises.find(e => e.name === exercise.id);
            if (!ex) return exercise.text;

            return $(`
            <div>
                <i class="fas ${ex.icon} me-2 text-${ex.type === 'strength' ? 'primary' : 'danger'}"></i>
                ${exercise.text}
                <span class="badge bg-${ex.type === 'strength' ? 'primary' : 'danger'} float-end">${ex.type}</span>
            </div>
        `);
        }

        // Toggle fields based on exercise type and hide weight for bodyweight exercises
        function toggleFields(select) {
            const row = $(select).closest('.exercise-card')[0];
            const selectedOption = $(select).select2('data')[0];
            const exerciseName = selectedOption ? selectedOption.id : '';
            const type = selectedOption ? selectedOption.element.dataset.type : '';

            const strengthFields = row.querySelector('.strength-fields');
            const durationField = row.querySelector('.duration-field');
            const typeStrength = row.querySelector('.type-strength');
            const typeCardio = row.querySelector('.type-cardio');
            const weightField = row.querySelector('.weight-field');

            if (type === 'cardio') {
                strengthFields.style.display = 'none';
                durationField.style.display = 'block';
                typeStrength.style.display = 'none';
                typeCardio.style.display = 'inline-flex';
            } else {
                strengthFields.style.display = 'block';
                durationField.style.display = 'none';
                typeStrength.style.display = 'inline-flex';
                typeCardio.style.display = 'none';

                // Hide weight field for bodyweight exercises
                if (noWeightExercises.includes(exerciseName)) {
                    weightField.style.display = 'none';
                } else {
                    weightField.style.display = 'block';
                }
            }
        }

        // Add new exercise
        function addExercise() {
            const container = document.getElementById('exercisesContainer');
            const emptyState = document.getElementById('emptyState');

            container.insertAdjacentHTML('beforeend', createExerciseCard());

            const newCard = container.lastElementChild;
            const selectElement = newCard.querySelector('.exercise-select');

            initializeSelect2(selectElement);
            updateExerciseNumbers();

            // Hide empty state
            emptyState.style.display = 'none';

            // Scroll to new exercise
            newCard.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        // Remove exercise
        function removeExercise(cardId) {
            const container = document.getElementById('exercisesContainer');
            const card = document.getElementById(cardId);

            if (container.children.length > 1) {
                card.remove();
                updateExerciseNumbers();
            } else {
                // Show empty state if no exercises left
                const emptyState = document.getElementById('emptyState');
                emptyState.style.display = 'block';
                card.remove();
            }
        }

        // Update exercise numbers
        function updateExerciseNumbers() {
            const cards = document.querySelectorAll('.exercise-card');
            cards.forEach((card, index) => {
                const badge = card.querySelector('.exercise-badge');
                const title = card.querySelector('.exercise-title');

                if (badge) badge.textContent = index + 1;
                if (title) title.textContent = `Exercise ${index + 1}`;
            });

            exerciseCounter = cards.length;
        }

        // Delete saved exercise
        async function deleteSavedExercise(exerciseId) {
            if (!confirm('Are you sure you want to delete this exercise?')) {
                return;
            }

            try {
                const response = await fetch('../api/workout-delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: exerciseId
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    // Remove from UI
                    const exerciseElement = document.getElementById(`saved-exercise-${exerciseId}`);
                    if (exerciseElement) {
                        exerciseElement.remove();
                    }

                    // Show success message
                    showAlert('Exercise deleted successfully!', 'success');

                    // Reload the page after a delay to refresh data
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(result.message || 'Error deleting exercise.', 'danger');
                }
            } catch (err) {
                console.error('Delete error:', err);
                showAlert('Network error. Please try again.', 'danger');
            }
        }

        // Show alert message
        function showAlert(message, type) {
            // Remove existing alerts
            document.querySelectorAll('.alert').forEach(alert => alert.remove());

            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

            document.body.insertAdjacentHTML('afterbegin', alertHTML);

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }

        // Form submission
        async function submitWorkout() {
            const btn = document.getElementById('saveBtn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

            const workouts = [];
            let hasErrors = false;

            // Clear previous error states
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            document.querySelectorAll('.exercise-card').forEach(row => {
                const select = row.querySelector('.exercise-select');
                const exerciseName = $(select).select2('data')[0]?.id;

                if (!exerciseName) {
                    hasErrors = true;
                    select.classList.add('is-invalid');
                    return;
                }

                const selectedOption = $(select).select2('data')[0];
                const type = selectedOption ? selectedOption.element.dataset.type : '';
                const data = {
                    exercise: exerciseName
                };

                if (type === 'cardio') {
                    const durationInput = row.querySelector('.duration-input');
                    const duration = durationInput ? durationInput.value : null;
                    const distanceInput = row.querySelector('.distance-input');
                    const distance = distanceInput ? distanceInput.value : null;

                    if (!duration || duration <= 0) {
                        hasErrors = true;
                        if (durationInput) durationInput.classList.add('is-invalid');
                        return;
                    }

                    data.duration = parseFloat(duration);
                    if (distance && distance > 0) data.distance = parseFloat(distance);
                } else {
                    const setsInput = row.querySelector('.sets-input');
                    const repsInput = row.querySelector('.reps-input');
                    const weightInput = row.querySelector('.weight-input');

                    const sets = setsInput ? setsInput.value : null;
                    const reps = repsInput ? repsInput.value : null;
                    const weight = weightInput ? weightInput.value : null;

                    if (!sets || sets <= 0) {
                        hasErrors = true;
                        if (setsInput) setsInput.classList.add('is-invalid');
                        return;
                    }

                    if (!reps || reps <= 0) {
                        hasErrors = true;
                        if (repsInput) repsInput.classList.add('is-invalid');
                        return;
                    }

                    data.sets = parseInt(sets);
                    data.reps = parseInt(reps);

                    // Only include weight if it's not a bodyweight exercise
                    if (!noWeightExercises.includes(exerciseName) && weight && weight > 0) {
                        data.weight = parseFloat(weight);
                    }
                }

                workouts.push(data);
            });

            if (hasErrors || workouts.length === 0) {
                if (hasErrors) {
                    showAlert('Please fix the errors in the form.', 'danger');
                } else if (workouts.length === 0) {
                    showAlert('Please add at least one exercise!', 'danger');
                }
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }

            try {
                const response = await fetch('../api/workout-save.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(workouts)
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.status === 'success') {
                    showAlert(result.message, 'success');

                    // Clear form
                    document.getElementById('exercisesContainer').innerHTML = '';
                    document.getElementById('emptyState').style.display = 'block';

                    // Reload to show saved exercises
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(result.message || 'Error saving workout.', 'danger');
                }

            } catch (err) {
                console.error('Fetch error details:', err);
                showAlert('Error saving workout. Please check console for details.', 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // Initialize page
        $(document).ready(function() {
            // Add first exercise
            addExercise();

            // Event listeners
            $('#addExerciseBtn').on('click', addExercise);
            $('#addExerciseFloat').on('click', addExercise);
            $('#saveBtn').on('click', submitWorkout);

            // Prevent form submission on enter
            $('#workoutForm').on('submit', function(e) {
                e.preventDefault();
                submitWorkout();
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
        });
    </script>
</body>

</html>