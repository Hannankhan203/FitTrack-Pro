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
        }

        .type-strength {
            background: rgba(0, 212, 255, 0.1);
            color: var(--primary);
        }

        .type-cardio {
            background: rgba(255, 45, 117, 0.1);
            color: var(--secondary);
        }

        .type-core {
            background: rgba(157, 78, 221, 0.1);
            color: var(--accent);
        }

        .type-fullbody {
            background: rgba(0, 230, 118, 0.1);
            color: var(--success);
        }

        .type-mobility {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
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

        .select2-results__group {
            color: var(--primary) !important;
            font-weight: 700 !important;
            padding: 8px 16px !important;
            background: rgba(0, 212, 255, 0.05) !important;
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
                                $cardio_exercises = ['Running', 'Cycling', 'Swimming', 'Jump Rope', 'Elliptical', 'Burpees', 'Rowing', 'Stair Climber', 'Sprint Intervals', 'Bike Intervals', 'Rowing Intervals'];
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
        // Comprehensive exercise database with categories
        const exercises = [
            // CHEST EXERCISES 💪🏽🏋️‍♂️
            { group: "Chest 💪🏽🏋️‍♂️", name: "Flat Barbell Bench Press 🏋️‍♂️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Incline Barbell Bench Press 🏋️‍♂️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Decline Barbell Bench Press 🏋️‍♂️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Close-Grip Bench Press 🏋️‍♂️", type: "strength", icon: "fa-hand-fist" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Wide-Grip Bench Press 🏋️‍♂️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Floor Press 🏋️‍♂️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Flat Dumbbell Bench Press 🏋️‍♂️", type: "strength", icon: "fa-dumbbell" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Incline Dumbbell Press 🏋️‍♂️", type: "strength", icon: "fa-dumbbell" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Decline Dumbbell Press 🏋️‍♂️", type: "strength", icon: "fa-dumbbell" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Dumbbell Flyes 🏋️‍♂️", type: "strength", icon: "fa-dumbbell" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Incline Dumbbell Flyes 🏋️‍♂️", type: "strength", icon: "fa-dumbbell" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Decline Dumbbell Flyes 🏋️‍♂️", type: "strength", icon: "fa-dumbbell" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Dumbbell Pullover 🏋️‍♂️", type: "strength", icon: "fa-dumbbell" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Machine Chest Press 🖥️", type: "strength", icon: "fa-desktop" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Pec Deck Fly Machine 🎛️", type: "strength", icon: "fa-gear" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Cable Crossover 💪", type: "strength", icon: "fa-cross" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "High Cable Crossover 💪", type: "strength", icon: "fa-cross" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Low Cable Crossover 💪", type: "strength", icon: "fa-cross" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Hammer Strength Chest Press 🖥️", type: "strength", icon: "fa-desktop" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Smith Machine Bench Press 🖥️", type: "strength", icon: "fa-desktop" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Standard Push-ups 🤸‍♂️", type: "strength", icon: "fa-person-burst" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Incline Push-ups 🤸‍♂️", type: "strength", icon: "fa-person-burst" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Decline Push-ups 🤸‍♂️", type: "strength", icon: "fa-person-burst" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Diamond Push-ups 🤸‍♂️", type: "strength", icon: "fa-gem" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Wide Push-ups 🤸‍♂️", type: "strength", icon: "fa-person-burst" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Archer Push-ups 🏹", type: "strength", icon: "fa-bow-arrow" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Plyometric Push-ups ⚡", type: "strength", icon: "fa-bolt" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Chest Dips 🤸‍♂️", type: "strength", icon: "fa-arrow-down-up-across-line" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Single-Arm Dumbbell Press 🏋️‍♂️", type: "strength", icon: "fa-dumbbell" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Landmine Press 🏋️‍♂️", type: "strength", icon: "fa-flag" },
            { group: "Chest 💪🏽🏋️‍♂️", name: "Guillotine Press 🗡️", type: "strength", icon: "fa-sword" },

            // BACK EXERCISES 🏋️‍♂️🦾
            { group: "Back 🏋️‍♂️🦾", name: "Pull-ups ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Back 🏋️‍♂️🦾", name: "Chin-ups ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Back 🏋️‍♂️🦾", name: "Neutral-grip Pull-ups ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Back 🏋️‍♂️🦾", name: "Wide-grip Pull-ups ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Back 🏋️‍♂️🦾", name: "Commando Pull-ups ⚔️", type: "strength", icon: "fa-user-ninja" },
            { group: "Back 🏋️‍♂️🦾", name: "Lat Pulldowns ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Back 🏋️‍♂️🦾", name: "Wide-grip Lat Pulldowns ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Back 🏋️‍♂️🦾", name: "Close-grip Lat Pulldowns ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Back 🏋️‍♂️🦾", name: "Reverse-grip Lat Pulldowns ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Back 🏋️‍♂️🦾", name: "Straight-arm Pulldowns ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Back 🏋️‍♂️🦾", name: "Assisted Pull-ups 🤝", type: "strength", icon: "fa-hands-helping" },
            { group: "Back 🏋️‍♂️🦾", name: "Bent-over Barbell Rows ↔️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Back 🏋️‍♂️🦾", name: "Pendlay Rows ↔️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Back 🏋️‍♂️🦾", name: "T-bar Rows ↔️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Back 🏋️‍♂️🦾", name: "Seated Cable Rows ↔️", type: "strength", icon: "fa-chair" },
            { group: "Back 🏋️‍♂️🦾", name: "Chest-supported Rows ↔️", type: "strength", icon: "fa-bed" },
            { group: "Back 🏋️‍♂️🦾", name: "Inverted Rows ↔️", type: "strength", icon: "fa-arrows-alt-v" },
            { group: "Back 🏋️‍♂️🦾", name: "Single-arm Dumbbell Rows ↔️", type: "strength", icon: "fa-dumbbell" },
            { group: "Back 🏋️‍♂️🦾", name: "Meadows Rows ↔️", type: "strength", icon: "fa-mountain" },
            { group: "Back 🏋️‍♂️🦾", name: "Kroc Rows ↔️", type: "strength", icon: "fa-fire" },
            { group: "Back 🏋️‍♂️🦾", name: "Conventional Deadlifts ⬇️⬆️", type: "strength", icon: "fa-dumbbell" },
            { group: "Back 🏋️‍♂️🦾", name: "Sumo Deadlifts ⬇️⬆️", type: "strength", icon: "fa-dumbbell" },
            { group: "Back 🏋️‍♂️🦾", name: "Romanian Deadlifts 🦵", type: "strength", icon: "fa-dumbbell" },
            { group: "Back 🏋️‍♂️🦾", name: "Stiff-legged Deadlifts 🦵", type: "strength", icon: "fa-dumbbell" },
            { group: "Back 🏋️‍♂️🦾", name: "Trap Bar Deadlifts ⬇️⬆️", type: "strength", icon: "fa-dumbbell" },
            { group: "Back 🏋️‍♂️🦾", name: "Rack Pulls ⬆️", type: "strength", icon: "fa-dumbbell" },
            { group: "Back 🏋️‍♂️🦾", name: "Snatch-grip Deadlifts ⬇️⬆️", type: "strength", icon: "fa-dumbbell" },
            { group: "Back 🏋️‍♂️🦾", name: "Face Pulls 🔄", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Back 🏋️‍♂️🦾", name: "Rear Delt Flyes 🔙", type: "strength", icon: "fa-dove" },
            { group: "Back 🏋️‍♂️🦾", name: "Band Pull-aparts 🔄", type: "strength", icon: "fa-band-aid" },
            { group: "Back 🏋️‍♂️🦾", name: "Reverse Pec Deck 🔄", type: "strength", icon: "fa-rotate-left" },
            { group: "Back 🏋️‍♂️🦾", name: "High Pulls ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Back 🏋️‍♂️🦾", name: "Hammer Strength Rows ↔️", type: "strength", icon: "fa-hammer" },
            { group: "Back 🏋️‍♂️🦾", name: "Machine Pulldowns ⬇️", type: "strength", icon: "fa-desktop" },
            { group: "Back 🏋️‍♂️🦾", name: "Landmine Rows ↔️", type: "strength", icon: "fa-flag" },
            { group: "Back 🏋️‍♂️🦾", name: "Rocky Pull-ups ⬆️", type: "strength", icon: "fa-fist-raised" },
            { group: "Back 🏋️‍♂️🦾", name: "Australian Pull-ups ⬇️", type: "strength", icon: "fa-arrow-down" },

            // SHOULDER EXERCISES 🏋️‍♂️🤸‍♂️
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Barbell Overhead Press ⬆️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Dumbbell Overhead Press ⬆️", type: "strength", icon: "fa-dumbbell" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Arnold Press 🔄", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Push Press ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Seated Dumbbell Press ⬆️", type: "strength", icon: "fa-chair" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Behind-the-neck Press ⬆️", type: "strength", icon: "fa-head-side" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Landmine Press ⬆️", type: "strength", icon: "fa-flag" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Machine Shoulder Press ⬆️", type: "strength", icon: "fa-desktop" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Kettlebell Press ⬆️", type: "strength", icon: "fa-weight" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Standing Dumbbell Lateral Raises ➡️", type: "strength", icon: "fa-arrow-right" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Seated Dumbbell Lateral Raises ➡️", type: "strength", icon: "fa-chair" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Leaning Lateral Raises ➡️", type: "strength", icon: "fa-arrow-right" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Cable Lateral Raises ➡️", type: "strength", icon: "fa-cable-car" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Machine Lateral Raises ➡️", type: "strength", icon: "fa-desktop" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Bent-over Lateral Raises 🔙", type: "strength", icon: "fa-arrow-down" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Face Pulls 🔄", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Reverse Pec Deck 🔄", type: "strength", icon: "fa-rotate-left" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Dumbbell Front Raises ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Barbell Front Raises ⬆️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Cable Front Raises ⬆️", type: "strength", icon: "fa-cable-car" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Plate Front Raises ⬆️", type: "strength", icon: "fa-weight" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Upright Rows ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Shrugs ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Dumbbell Shrugs ⬆️", type: "strength", icon: "fa-dumbbell" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Farmer's Walks 🚶‍♂️", type: "strength", icon: "fa-walking" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Cuban Press 🔄", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Shoulders 🏋️‍♂️🤸‍♂️", name: "Scaptions ⬆️", type: "strength", icon: "fa-arrow-up" },

            // ARM EXERCISES 💪🖐️
            { group: "Arms 💪🖐️", name: "Barbell Curls 💪", type: "strength", icon: "fa-weight-hanging" },
            { group: "Arms 💪🖐️", name: "EZ-bar Curls 💪", type: "strength", icon: "fa-weight-hanging" },
            { group: "Arms 💪🖐️", name: "Standing Dumbbell Curls 💪", type: "strength", icon: "fa-dumbbell" },
            { group: "Arms 💪🖐️", name: "Seated Dumbbell Curls 💪", type: "strength", icon: "fa-chair" },
            { group: "Arms 💪🖐️", name: "Incline Dumbbell Curls 💪", type: "strength", icon: "fa-dumbbell" },
            { group: "Arms 💪🖐️", name: "Hammer Curls 🔨", type: "strength", icon: "fa-hammer" },
            { group: "Arms 💪🖐️", name: "Cross-body Hammer Curls 🔨", type: "strength", icon: "fa-hammer" },
            { group: "Arms 💪🖐️", name: "Preacher Curls (barbell) 📖", type: "strength", icon: "fa-book" },
            { group: "Arms 💪🖐️", name: "Preacher Curls (dumbbell) 📖", type: "strength", icon: "fa-book" },
            { group: "Arms 💪🖐️", name: "Spider Curls 🕷️", type: "strength", icon: "fa-spider" },
            { group: "Arms 💪🖐️", name: "Concentration Curls 🎯", type: "strength", icon: "fa-bullseye" },
            { group: "Arms 💪🖐️", name: "Zottman Curls 🔄", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Arms 💪🖐️", name: "Cable Curls 💪", type: "strength", icon: "fa-cable-car" },
            { group: "Arms 💪🖐️", name: "High Cable Curls 💪", type: "strength", icon: "fa-cable-car" },
            { group: "Arms 💪🖐️", name: "Low Cable Curls 💪", type: "strength", icon: "fa-cable-car" },
            { group: "Arms 💪🖐️", name: "Reverse Curls 🔄", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Arms 💪🖐️", name: "Drag Curls ⬅️", type: "strength", icon: "fa-arrow-left" },
            { group: "Arms 💪🖐️", name: "21s Curls 2️⃣1️⃣", type: "strength", icon: "fa-hashtag" },
            { group: "Arms 💪🖐️", name: "Band Curls 💪", type: "strength", icon: "fa-band-aid" },
            { group: "Arms 💪🖐️", name: "Machine Curls 💪", type: "strength", icon: "fa-desktop" },
            { group: "Arms 💪🖐️", name: "Close-grip Bench Press ⬇️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Arms 💪🖐️", name: "Tricep Dips ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Arms 💪🖐️", name: "Bench Dips ⬇️", type: "strength", icon: "fa-bed" },
            { group: "Arms 💪🖐️", name: "Skull Crushers 💀", type: "strength", icon: "fa-skull" },
            { group: "Arms 💪🖐️", name: "Overhead Tricep Extensions ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Arms 💪🖐️", name: "Overhead Cable Extensions ⬆️", type: "strength", icon: "fa-cable-car" },
            { group: "Arms 💪🖐️", name: "Tricep Pushdowns (straight bar) ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Arms 💪🖐️", name: "Tricep Pushdowns (rope) ⬇️", type: "strength", icon: "fa-rope" },
            { group: "Arms 💪🖐️", name: "Tricep Pushdowns (V-bar) ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Arms 💪🖐️", name: "Tricep Kickbacks ⬅️", type: "strength", icon: "fa-arrow-left" },
            { group: "Arms 💪🖐️", name: "JM Press ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Arms 💪🖐️", name: "Tate Press ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Arms 💪🖐️", name: "Diamond Push-ups 💎", type: "strength", icon: "fa-gem" },
            { group: "Arms 💪🖐️", name: "French Press 🇫🇷", type: "strength", icon: "fa-flag" },
            { group: "Arms 💪🖐️", name: "Single-arm Tricep Extensions ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Arms 💪🖐️", name: "Cable Kickbacks ⬅️", type: "strength", icon: "fa-cable-car" },
            { group: "Arms 💪🖐️", name: "Machine Tricep Extensions ⬆️", type: "strength", icon: "fa-desktop" },
            { group: "Arms 💪🖐️", name: "Band Pushdowns ⬇️", type: "strength", icon: "fa-band-aid" },
            { group: "Arms 💪🖐️", name: "Floor Press ⬇️", type: "strength", icon: "fa-floor" },
            { group: "Arms 💪🖐️", name: "Narrow Push-ups ⬇️", type: "strength", icon: "fa-arrows-in" },
            { group: "Arms 💪🖐️", name: "Wrist Curls ✋", type: "strength", icon: "fa-hand" },
            { group: "Arms 💪🖐️", name: "Reverse Wrist Curls 🔄", type: "strength", icon: "fa-hand" },
            { group: "Arms 💪🖐️", name: "Farmer's Walks 🚶‍♂️", type: "strength", icon: "fa-walking" },
            { group: "Arms 💪🖐️", name: "Plate Pinches 🤏", type: "strength", icon: "fa-weight" },
            { group: "Arms 💪🖐️", name: "Towel Pull-ups 🧻", type: "strength", icon: "fa-hand-holding" },
            { group: "Arms 💪🖐️", name: "Dead Hangs ⏱️", type: "strength", icon: "fa-clock" },
            { group: "Arms 💪🖐️", name: "Wrist Roller ⏱️", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Arms 💪🖐️", name: "Hammer Levering 🔨", type: "strength", icon: "fa-hammer" },

            // LEG EXERCISES 🦵🏋️‍♂️
            { group: "Legs 🦵🏋️‍♂️", name: "Barbell Back Squats ⬇️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Legs 🦵🏋️‍♂️", name: "Front Squats ⬇️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Legs 🦵🏋️‍♂️", name: "High-bar Squats ⬇️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Legs 🦵🏋️‍♂️", name: "Low-bar Squats ⬇️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Legs 🦵🏋️‍♂️", name: "Goblet Squats ⬇️", type: "strength", icon: "fa-weight" },
            { group: "Legs 🦵🏋️‍♂️", name: "Hack Squats ⬇️", type: "strength", icon: "fa-desktop" },
            { group: "Legs 🦵🏋️‍♂️", name: "Leg Press ⬇️", type: "strength", icon: "fa-desktop" },
            { group: "Legs 🦵🏋️‍♂️", name: "Bulgarian Split Squats ⬇️", type: "strength", icon: "fa-person" },
            { group: "Legs 🦵🏋️‍♂️", name: "Walking Lunges 🚶‍♂️", type: "strength", icon: "fa-walking" },
            { group: "Legs 🦵🏋️‍♂️", name: "Reverse Lunges ⬅️", type: "strength", icon: "fa-arrow-left" },
            { group: "Legs 🦵🏋️‍♂️", name: "Stationary Lunges ⬇️", type: "strength", icon: "fa-person" },
            { group: "Legs 🦵🏋️‍♂️", name: "Step-ups ⬆️", type: "strength", icon: "fa-stairs" },
            { group: "Legs 🦵🏋️‍♂️", name: "Leg Extensions ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Legs 🦵🏋️‍♂️", name: "Sissy Squats ⬇️", type: "strength", icon: "fa-person" },
            { group: "Legs 🦵🏋️‍♂️", name: "Pistol Squats 🔫", type: "strength", icon: "fa-person" },
            { group: "Legs 🦵🏋️‍♂️", name: "Belt Squats ⬇️", type: "strength", icon: "fa-belt" },
            { group: "Legs 🦵🏋️‍♂️", name: "Zercher Squats ⬇️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Legs 🦵🏋️‍♂️", name: "Overhead Squats ⬇️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Legs 🦵🏋️‍♂️", name: "Box Squats 📦", type: "strength", icon: "fa-box" },
            { group: "Legs 🦵🏋️‍♂️", name: "Heels-elevated Squats ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Legs 🦵🏋️‍♂️", name: "Romanian Deadlifts 🦵", type: "strength", icon: "fa-dumbbell" },
            { group: "Legs 🦵🏋️‍♂️", name: "Stiff-legged Deadlifts 🦵", type: "strength", icon: "fa-dumbbell" },
            { group: "Legs 🦵🏋️‍♂️", name: "Hamstring Curls (lying) 🦵", type: "strength", icon: "fa-bed" },
            { group: "Legs 🦵🏋️‍♂️", name: "Hamstring Curls (seated) 🦵", type: "strength", icon: "fa-chair" },
            { group: "Legs 🦵🏋️‍♂️", name: "Hamstring Curls (standing) 🦵", type: "strength", icon: "fa-person-standing" },
            { group: "Legs 🦵🏋️‍♂️", name: "Nordic Hamstring Curls 🇳🇴", type: "strength", icon: "fa-person" },
            { group: "Legs 🦵🏋️‍♂️", name: "Glute-ham Raises 🍑", type: "strength", icon: "fa-arrow-up" },
            { group: "Legs 🦵🏋️‍♂️", name: "Good Mornings 🌄", type: "strength", icon: "fa-sun" },
            { group: "Legs 🦵🏋️‍♂️", name: "Single-leg Romanian Deadlifts 🦵", type: "strength", icon: "fa-dumbbell" },
            { group: "Legs 🦵🏋️‍♂️", name: "Kettlebell Swings ⚖️", type: "strength", icon: "fa-weight" },
            { group: "Legs 🦵🏋️‍♂️", name: "Reverse Hyperextensions ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Legs 🦵🏋️‍♂️", name: "Swiss Ball Leg Curls ⚽", type: "strength", icon: "fa-futbol" },
            { group: "Legs 🦵🏋️‍♂️", name: "Cable Pull-throughs ⬅️", type: "strength", icon: "fa-cable-car" },
            { group: "Legs 🦵🏋️‍♂️", name: "Band Leg Curls 🦵", type: "strength", icon: "fa-band-aid" },
            { group: "Legs 🦵🏋️‍♂️", name: "Seated Good Mornings 🌄", type: "strength", icon: "fa-chair" },
            { group: "Legs 🦵🏋️‍♂️", name: "Hip Thrusts 🍑", type: "strength", icon: "fa-arrow-up" },
            { group: "Legs 🦵🏋️‍♂️", name: "Glute Bridges 🍑", type: "strength", icon: "fa-bridge" },
            { group: "Legs 🦵🏋️‍♂️", name: "Single-leg Hip Thrusts 🍑", type: "strength", icon: "fa-arrow-up" },
            { group: "Legs 🦵🏋️‍♂️", name: "Frog Pumps 🐸", type: "strength", icon: "fa-frog" },
            { group: "Legs 🦵🏋️‍♂️", name: "Cable Kickbacks ⬅️", type: "strength", icon: "fa-cable-car" },
            { group: "Legs 🦵🏋️‍♂️", name: "Donkey Kicks 🐴", type: "strength", icon: "fa-horse" },
            { group: "Legs 🦵🏋️‍♂️", name: "Fire Hydrants 🚒", type: "strength", icon: "fa-fire-hydrant" },
            { group: "Legs 🦵🏋️‍♂️", name: "Curtsy Lunges 👑", type: "strength", icon: "fa-person" },
            { group: "Legs 🦵🏋️‍♂️", name: "Sumo Deadlifts 🍑", type: "strength", icon: "fa-dumbbell" },
            { group: "Legs 🦵🏋️‍♂️", name: "Step-ups (high step) ⬆️", type: "strength", icon: "fa-stairs" },
            { group: "Legs 🦵🏋️‍♂️", name: "Bulgarian Split Squats 🍑", type: "strength", icon: "fa-person" },
            { group: "Legs 🦵🏋️‍♂️", name: "Clamshells 🐚", type: "strength", icon: "fa-fish" },
            { group: "Legs 🦵🏋️‍♂️", name: "Standing Calf Raises 🦶", type: "strength", icon: "fa-arrow-up" },
            { group: "Legs 🦵🏋️‍♂️", name: "Seated Calf Raises 🦶", type: "strength", icon: "fa-chair" },
            { group: "Legs 🦵🏋️‍♂️", name: "Donkey Calf Raises 🦶", type: "strength", icon: "fa-horse" },
            { group: "Legs 🦵🏋️‍♂️", name: "Leg Press Calf Raises 🦶", type: "strength", icon: "fa-desktop" },
            { group: "Legs 🦵🏋️‍♂️", name: "Single-leg Calf Raises 🦶", type: "strength", icon: "fa-arrow-up" },
            { group: "Legs 🦵🏋️‍♂️", name: "Farmer's Walk on Toes 🚶‍♂️", type: "strength", icon: "fa-walking" },
            { group: "Legs 🦵🏋️‍♂️", name: "Jump Rope 🦶", type: "cardio", icon: "fa-rope" },
            { group: "Legs 🦵🏋️‍♂️", name: "Box Jumps 🦶", type: "strength", icon: "fa-box" },

            // CORE & ABS EXERCISES 🏋️‍♂️🤸‍♂️
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Plank 🧘‍♂️", type: "strength", icon: "fa-ruler-horizontal" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Forearm Plank 🧘‍♂️", type: "strength", icon: "fa-ruler-horizontal" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Side Plank 🧘‍♂️", type: "strength", icon: "fa-ruler-horizontal" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "RKC Plank 🧘‍♂️", type: "strength", icon: "fa-ruler-horizontal" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Ab Wheel Rollouts 🛞", type: "strength", icon: "fa-circle" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Stability Ball Rollouts ⚽", type: "strength", icon: "fa-futbol" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Dead Bug 🐛", type: "strength", icon: "fa-bug" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Bird-dog 🐦", type: "strength", icon: "fa-dog" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Bear Crawls 🐻", type: "strength", icon: "fa-paw" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Hollow Body Hold 🫥", type: "strength", icon: "fa-person" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "L-sit L️⃣", type: "strength", icon: "fa-chair" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Side Plank with Rotation 🔄", type: "strength", icon: "fa-ruler-horizontal" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Suitcase Carries 🧳", type: "strength", icon: "fa-suitcase" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Windshield Wipers 🪟", type: "strength", icon: "fa-wind" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Side Bend ⬅️➡️", type: "strength", icon: "fa-arrows-left-right" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Pallof Press 🔄", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Cable Chop 🔄", type: "strength", icon: "fa-cable-car" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Cable Lift 🔄", type: "strength", icon: "fa-cable-car" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Renegade Rows 🔄", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Crunches 🤸‍♂️", type: "strength", icon: "fa-arrow-up" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Reverse Crunches 🔄", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Leg Raises ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Hanging Leg Raises ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Knee Raises ⬆️", type: "strength", icon: "fa-arrow-up" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Scissor Kicks ✂️", type: "strength", icon: "fa-scissors" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Flutter Kicks 🦶", type: "strength", icon: "fa-feather" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Russian Twists 🇷🇺", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Bicycle Crunches 🚲", type: "strength", icon: "fa-bicycle" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "V-ups V️⃣", type: "strength", icon: "fa-arrow-up" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Toe Touches 👣", type: "strength", icon: "fa-hand" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Captain's Chair Leg Raises ⬆️", type: "strength", icon: "fa-chair" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Decline Bench Sit-ups ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Medicine Ball Slams ⚽", type: "strength", icon: "fa-futbol" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Wood Choppers 🪓", type: "strength", icon: "fa-axe" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Standing Cable Rotations 🔄", type: "strength", icon: "fa-cable-car" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Medicine Ball Rotational Throws ⚽", type: "strength", icon: "fa-futbol" },
            { group: "Core & Abs 🏋️‍♂️🤸‍♂️", name: "Turkish Get-ups 🇹🇷", type: "strength", icon: "fa-arrow-up" },

            // CARDIO EXERCISES 🏃‍♂️🚴‍♀️🏊‍♂️
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Running/Jogging 🏃‍♂️", type: "cardio", icon: "fa-person-running" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Cycling (stationary/road) 🚴‍♀️", type: "cardio", icon: "fa-bicycle" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Swimming 🏊‍♂️", type: "cardio", icon: "fa-person-swimming" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Rowing 🚣‍♂️", type: "cardio", icon: "fa-water" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Elliptical Trainer 🏃‍♂️", type: "cardio", icon: "fa-person-walking" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Stair Climber 🏃‍♂️", type: "cardio", icon: "fa-stairs" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Cross-country Skiing (machine) ⛷️", type: "cardio", icon: "fa-person-skiing" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Hiking 🥾", type: "cardio", icon: "fa-mountain" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Walking (brisk) 🚶‍♂️", type: "cardio", icon: "fa-walking" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Stair Running 🏃‍♂️", type: "cardio", icon: "fa-stairs" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Skating/Rollerblading ⛸️", type: "cardio", icon: "fa-person-skating" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Jump Rope (continuous) 🦶", type: "cardio", icon: "fa-rope" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Sprint Intervals ⚡", type: "cardio", icon: "fa-bolt" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Bike Intervals 🚴‍♀️", type: "cardio", icon: "fa-bicycle" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Rowing Intervals 🚣‍♂️", type: "cardio", icon: "fa-water" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Battle Ropes 🔥", type: "cardio", icon: "fa-rope" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Burpee Intervals 🔥", type: "cardio", icon: "fa-fire" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Mountain Climber Intervals ⛰️", type: "cardio", icon: "fa-mountain" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Box Jump Intervals 📦", type: "cardio", icon: "fa-box" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Kettlebell Swing Intervals ⚖️", type: "cardio", icon: "fa-weight" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Assault Bike Intervals 🚴‍♀️", type: "cardio", icon: "fa-bicycle" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "TABATA Protocol 🔄", type: "cardio", icon: "fa-clock" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "AMRAP 🔄", type: "cardio", icon: "fa-infinity" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "EMOM 🔄", type: "cardio", icon: "fa-clock" },
            { group: "Cardio 🏃‍♂️🚴‍♀️🏊‍♂️", name: "Chipper Workouts 🔄", type: "cardio", icon: "fa-list-check" },

            // FULL BODY & COMPOUND EXERCISES 🏋️‍♂️🤸‍♂️💥
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Clean and Jerk 💨", type: "strength", icon: "fa-weight-hanging" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Snatch 💨", type: "strength", icon: "fa-weight-hanging" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Power Clean 💨", type: "strength", icon: "fa-weight-hanging" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Power Snatch 💨", type: "strength", icon: "fa-weight-hanging" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Hang Clean 💨", type: "strength", icon: "fa-weight-hanging" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Clean Pull 💨", type: "strength", icon: "fa-weight-hanging" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Farmer's Walks 🏋️‍♂️", type: "strength", icon: "fa-walking" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Sandbag Carries 🏋️‍♂️", type: "strength", icon: "fa-bag-shopping" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Yoke Walks 🏋️‍♂️", type: "strength", icon: "fa-weight-hanging" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Atlas Stone Lifts 🪨", type: "strength", icon: "fa-mountain" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Log Press 🪵", type: "strength", icon: "fa-tree" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Tire Flips 🛞", type: "strength", icon: "fa-tire" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Burpees 💥", type: "strength", icon: "fa-fire" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Thrusters (with weight) 💥", type: "strength", icon: "fa-weight-hanging" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Kettlebell Swings ⚖️", type: "strength", icon: "fa-weight" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Turkish Get-ups 🇹🇷", type: "strength", icon: "fa-arrow-up" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Man-makers 👨‍🔧", type: "strength", icon: "fa-person" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Squat Thrusts ⬇️", type: "strength", icon: "fa-arrow-down" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Bear Complex 🐻", type: "strength", icon: "fa-paw" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Grace (clean & jerks) 💎", type: "strength", icon: "fa-weight-hanging" },
            { group: "Full Body 🏋️‍♂️🤸‍♂️💥", name: "Fran (thrusters + pull-ups) 🇫🇷", type: "strength", icon: "fa-weight-hanging" },

            // MISC & MOBILITY 🧘‍♂️🤲
            { group: "Mobility 🧘‍♂️🤲", name: "Towel Pull-ups ✋", type: "strength", icon: "fa-hand-holding" },
            { group: "Mobility 🧘‍♂️🤲", name: "Plate Pinches ✋", type: "strength", icon: "fa-weight" },
            { group: "Mobility 🧘‍♂️🤲", name: "Fat Gripz Training ✋", type: "strength", icon: "fa-grip" },
            { group: "Mobility 🧘‍♂️🤲", name: "Wrist Roller ⏱️", type: "strength", icon: "fa-arrows-rotate" },
            { group: "Mobility 🧘‍♂️🤲", name: "Climbing (rock wall) 🧗", type: "strength", icon: "fa-mountain" },
            { group: "Mobility 🧘‍♂️🤲", name: "Neck Bridges (caution) 🧠", type: "strength", icon: "fa-brain" },
            { group: "Mobility 🧘‍♂️🤲", name: "Neck Harness Work 🧠", type: "strength", icon: "fa-brain" },
            { group: "Mobility 🧘‍♂️🤲", name: "Manual Resistance Neck Training 🧠", type: "strength", icon: "fa-brain" },
            { group: "Mobility 🧘‍♂️🤲", name: "Foam Rolling 🧘‍♂️", type: "mobility", icon: "fa-roller" },
            { group: "Mobility 🧘‍♂️🤲", name: "Dynamic Stretching 🧘‍♂️", type: "mobility", icon: "fa-person-walking" },
            { group: "Mobility 🧘‍♂️🤲", name: "Static Stretching 🧘‍♂️", type: "mobility", icon: "fa-person-standing" },
            { group: "Mobility 🧘‍♂️🤲", name: "Yoga Poses 🧘‍♂️", type: "mobility", icon: "fa-spa" },
            { group: "Mobility 🧘‍♂️🤲", name: "Animal Flow 🐾", type: "mobility", icon: "fa-paw" },
            { group: "Mobility 🧘‍♂️🤲", name: "Pilates 🧘‍♂️", type: "mobility", icon: "fa-person" },
            { group: "Mobility 🧘‍♂️🤲", name: "Tai Chi 🧘‍♂️", type: "mobility", icon: "fa-yin-yang" }
        ];

        // Exercises that don't require weight input
        const noWeightExercises = [
            'Pull-ups ⬆️', 'Push-ups 🤸‍♂️', 'Lunges 🚶‍♂️', 'Plank 🧘‍♂️', 'Chin-ups ⬆️', 
            'Dips 🤸‍♂️', 'Bodyweight Squats ⬇️', 'Inverted Rows ↔️', 'Handstand Push-ups 🤸‍♂️',
            'Muscle-ups ⬆️', 'Australian Pull-ups ⬇️', 'Archer Push-ups 🏹', 'Plyometric Push-ups ⚡'
        ];

        let exerciseCounter = 0;

        // Create exercise card HTML
        function createExerciseCard() {
            exerciseCounter++;
            const cardId = `exercise-${exerciseCounter}`;

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
                        <span class="exercise-type-indicator type-mobility" style="display:none;">
                            <i class="fas fa-spa me-1"></i>Mobility
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
                
                <div class="cardio-fields" style="display:none;">
                    <label class="form-label">Cardio Details</label>
                    <div class="input-row">
                        <div class="input-field">
                            <div class="unit-container">
                                <input type="number" class="duration-input" placeholder="Duration" min="1">
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
                        <div class="input-field">
                            <div class="unit-container">
                                <input type="number" class="calories-input" placeholder="Calories">
                                <span class="unit-label">cal</span>
                            </div>
                            <div class="small text-muted mt-1">Calories burned (optional)</div>
                        </div>
                    </div>
                </div>
                
                <div class="mobility-fields" style="display:none;">
                    <label class="form-label">Mobility Details</label>
                    <div class="input-row">
                        <div class="input-field">
                            <div class="unit-container">
                                <input type="number" class="time-input" placeholder="Time" min="1" required>
                                <span class="unit-label">sec</span>
                            </div>
                            <div class="small text-muted mt-1">Time in seconds</div>
                        </div>
                        <div class="input-field">
                            <input type="number" class="reps-input-mobility" placeholder="Reps" min="1">
                            <div class="small text-muted mt-1">Reps (if applicable)</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        }

        // Initialize Select2 with grouped exercises
        function initializeSelect2(selectElement) {
            // Group exercises by category
            const groupedExercises = {};
            exercises.forEach(ex => {
                if (!groupedExercises[ex.group]) {
                    groupedExercises[ex.group] = [];
                }
                groupedExercises[ex.group].push(ex);
            });

            // Create HTML options with groups
            let options = '';
            Object.keys(groupedExercises).sort().forEach(group => {
                options += `<optgroup label="${group}">`;
                groupedExercises[group].forEach(ex => {
                    options += `<option value="${ex.name}" data-type="${ex.type}" data-icon="${ex.icon}">${ex.name}</option>`;
                });
                options += `</optgroup>`;
            });

            selectElement.innerHTML = `<option value="">Choose an exercise...</option>${options}`;

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

            let typeClass = '';
            switch(ex.type) {
                case 'strength': typeClass = 'primary'; break;
                case 'cardio': typeClass = 'danger'; break;
                case 'mobility': typeClass = 'warning'; break;
                default: typeClass = 'primary';
            }

            return $(`
            <div>
                <i class="fas ${ex.icon} me-2 text-${typeClass}"></i>
                ${exercise.text}
                <span class="badge bg-${typeClass} float-end">${ex.type}</span>
            </div>
        `);
        }

        // Toggle fields based on exercise type
        function toggleFields(select) {
            const row = $(select).closest('.exercise-card')[0];
            const selectedOption = $(select).select2('data')[0];
            const exerciseName = selectedOption ? selectedOption.id : '';
            const type = selectedOption ? selectedOption.element.dataset.type : 'strength';

            const strengthFields = row.querySelector('.strength-fields');
            const cardioFields = row.querySelector('.cardio-fields');
            const mobilityFields = row.querySelector('.mobility-fields');
            const typeStrength = row.querySelector('.type-strength');
            const typeCardio = row.querySelector('.type-cardio');
            const typeMobility = row.querySelector('.type-mobility');
            const weightField = row.querySelector('.weight-field');

            // Hide all fields first
            strengthFields.style.display = 'none';
            cardioFields.style.display = 'none';
            mobilityFields.style.display = 'none';
            typeStrength.style.display = 'none';
            typeCardio.style.display = 'none';
            typeMobility.style.display = 'none';

            // Show appropriate fields based on type
            switch(type) {
                case 'strength':
                    strengthFields.style.display = 'block';
                    typeStrength.style.display = 'inline-flex';
                    
                    // Hide weight field for bodyweight exercises
                    if (noWeightExercises.includes(exerciseName)) {
                        weightField.style.display = 'none';
                    } else {
                        weightField.style.display = 'block';
                    }
                    break;
                    
                case 'cardio':
                    cardioFields.style.display = 'block';
                    typeCardio.style.display = 'inline-flex';
                    break;
                    
                case 'mobility':
                    mobilityFields.style.display = 'block';
                    typeMobility.style.display = 'inline-flex';
                    break;
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
                const type = selectedOption ? selectedOption.element.dataset.type : 'strength';
                const data = {
                    exercise: exerciseName
                };

                switch(type) {
                    case 'cardio':
                        const durationInput = row.querySelector('.duration-input');
                        const duration = durationInput ? durationInput.value : null;
                        const distanceInput = row.querySelector('.distance-input');
                        const distance = distanceInput ? distanceInput.value : null;
                        const caloriesInput = row.querySelector('.calories-input');
                        const calories = caloriesInput ? caloriesInput.value : null;

                        if (!duration || duration <= 0) {
                            hasErrors = true;
                            if (durationInput) durationInput.classList.add('is-invalid');
                            return;
                        }

                        data.duration = parseFloat(duration);
                        if (distance && distance > 0) data.distance = parseFloat(distance);
                        if (calories && calories > 0) data.calories = parseInt(calories);
                        break;

                    case 'mobility':
                        const timeInput = row.querySelector('.time-input');
                        const time = timeInput ? timeInput.value : null;
                        const repsInputMobility = row.querySelector('.reps-input-mobility');
                        const repsMobility = repsInputMobility ? repsInputMobility.value : null;

                        if (!time || time <= 0) {
                            hasErrors = true;
                            if (timeInput) timeInput.classList.add('is-invalid');
                            return;
                        }

                        data.duration = parseFloat(time);
                        if (repsMobility && repsMobility > 0) data.reps = parseInt(repsMobility);
                        break;

                    default: // strength
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