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

        .workout-form-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .exercise-card {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
            position: relative;
        }

        .exercise-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 8px 25px rgba(58, 134, 255, 0.1);
        }

        .exercise-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .exercise-number {
            display: flex;
            align-items: center;
        }

        .exercise-badge {
            width: 36px;
            height: 36px;
            background: var(--gradient-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 12px;
        }

        .exercise-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .remove-btn {
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
            cursor: pointer;
        }

        .remove-btn:hover {
            background: #ff5252;
            transform: scale(1.1);
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .exercise-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .exercise-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 134, 255, 0.25);
        }

        .input-group {
            margin-bottom: 1rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #6c757d;
            font-weight: 500;
        }

        .input-row {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .input-field {
            flex: 1;
        }

        .input-field input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .input-field input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 134, 255, 0.25);
        }

        .unit-label {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            background: white;
            padding: 0 0.5rem;
        }

        .unit-container {
            position: relative;
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
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 15px rgba(58, 134, 255, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--gradient-success);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 15px rgba(56, 176, 0, 0.3);
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e9ecef;
        }

        .exercise-type-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 1rem;
        }

        .type-strength {
            background: rgba(58, 134, 255, 0.1);
            color: var(--primary-color);
        }

        .type-cardio {
            background: rgba(255, 0, 110, 0.1);
            color: var(--secondary-color);
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

        .floating-action {
            position: fixed;
            bottom: 80px;
            right: 20px;
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
            box-shadow: 0 5px 20px rgba(255, 0, 110, 0.3);
            transition: all 0.3s;
            cursor: pointer;
        }

        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(255, 0, 110, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        /* Saved Exercises Styles */
        .saved-exercises-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .saved-exercise-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .saved-exercise-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(58, 134, 255, 0.1);
        }

        .saved-exercise-info {
            flex: 1;
        }

        .saved-exercise-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .saved-exercise-details {
            display: flex;
            gap: 1.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .saved-exercise-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .saved-exercise-actions {
            display: flex;
            gap: 0.5rem;
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
            cursor: pointer;
        }

        .delete-btn:hover {
            background: #ff5252;
            transform: scale(1.1);
        }

        .saved-exercise-type {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.75rem;
        }

        .alert {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
            margin: 1rem;
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

            .input-row {
                flex-direction: column;
                gap: 0.5rem;
            }

            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }

            .form-actions button {
                width: 100%;
            }

            .floating-action {
                bottom: 70px;
            }

            .saved-exercise-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .saved-exercise-actions {
                margin-top: 1rem;
                align-self: flex-end;
            }

            .saved-exercise-details {
                flex-wrap: wrap;
                gap: 0.75rem;
            }
        }

        .select2-container--default .select2-selection--single {
            height: 50px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 50px;
            padding-left: 15px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 48px;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary-color);
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }

        /* ============================================
   PREMIUM MOBILE RESPONSIVE DESIGN - WORKOUT LOG
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
    
    .date-display {
        flex-direction: row !important;
        justify-content: center;
        padding: 0.5rem 1rem !important;
        margin-top: 1rem !important;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px !important;
        font-size: 0.9rem;
        display: inline-flex !important;
    }
    
    .date-display i {
        margin-right: 0.5rem;
        font-size: 0.9rem;
    }
    
    /* Saved Exercises Container - Mobile Optimized */
    .saved-exercises-container,
    .workout-form-container {
        margin: 0 0.75rem 1.5rem 0.75rem !important;
        padding: 1.5rem !important;
        border-radius: 20px !important;
    }
    
    .saved-exercises-container h4,
    .workout-form-container h4 {
        font-size: 1.2rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .text-muted.mb-3 {
        margin-bottom: 1rem !important;
        font-size: 0.9rem;
    }
    
    /* Saved Exercise Card - Mobile Optimized */
    .saved-exercise-card {
        padding: 1.25rem !important;
        border-radius: 16px !important;
        margin-bottom: 1rem !important;
        flex-direction: column !important;
        align-items: stretch !important;
    }
    
    .saved-exercise-info {
        width: 100%;
    }
    
    .saved-exercise-name {
        font-size: 1.1rem !important;
        margin-bottom: 0.75rem !important;
        flex-wrap: wrap;
    }
    
    .saved-exercise-name i {
        font-size: 1rem !important;
        margin-right: 0.5rem !important;
    }
    
    .saved-exercise-type {
        font-size: 0.7rem !important;
        padding: 0.2rem 0.5rem !important;
        margin-left: 0.5rem !important;
    }
    
    .saved-exercise-details {
        flex-wrap: wrap !important;
        gap: 0.75rem !important;
        margin: 0.5rem 0 !important;
    }
    
    .saved-exercise-detail {
        font-size: 0.8rem !important;
    }
    
    .saved-exercise-detail i {
        font-size: 0.9rem !important;
    }
    
    .saved-exercise-actions {
        margin-top: 1rem !important;
        align-self: flex-end !important;
        width: 100%;
        text-align: right;
    }
    
    .delete-btn {
        width: 40px !important;
        height: 40px !important;
        font-size: 1rem !important;
    }
    
    /* Exercise Card - Mobile Optimized */
    .exercise-card {
        padding: 1.25rem !important;
        border-radius: 16px !important;
        margin-bottom: 1.25rem !important;
        border-width: 1px !important;
    }
    
    .exercise-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        margin-bottom: 1.25rem !important;
        padding-bottom: 1rem !important;
        gap: 1rem;
    }
    
    .exercise-number {
        width: 100%;
        justify-content: space-between;
    }
    
    .exercise-badge {
        width: 32px !important;
        height: 32px !important;
        font-size: 0.9rem !important;
        margin-right: 0.75rem !important;
    }
    
    .exercise-title {
        font-size: 1.1rem !important;
        margin-right: auto;
    }
    
    .exercise-type-indicator {
        font-size: 0.75rem !important;
        padding: 0.2rem 0.5rem !important;
        margin-left: 0.5rem !important;
    }
    
    .remove-btn {
        position: absolute !important;
        top: 1.25rem;
        right: 1.25rem;
        width: 32px !important;
        height: 32px !important;
        font-size: 0.9rem !important;
    }
    
    /* Form Elements - Mobile Optimized */
    .form-label {
        font-size: 0.95rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    /* Select2 Mobile Optimization */
    .select2-container--default .select2-selection--single {
        height: 48px !important;
        border-radius: 12px !important;
        border-width: 2px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 48px !important;
        padding-left: 1rem !important;
        font-size: 0.95rem !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 46px !important;
    }
    
    .select2-dropdown {
        border-radius: 12px !important;
        margin-top: 5px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .select2-results__option {
        padding: 0.75rem 1rem !important;
        font-size: 0.9rem !important;
    }
    
    /* Input Fields - Mobile Optimized */
    .input-row {
        flex-direction: column !important;
        gap: 1rem !important;
        margin-top: 0.75rem !important;
    }
    
    .input-field {
        width: 100% !important;
    }
    
    .input-field input {
        padding: 0.875rem !important;
        font-size: 1rem !important;
        min-height: 50px !important;
        border-radius: 12px !important;
        border-width: 2px !important;
    }
    
    .unit-container {
        position: relative;
    }
    
    .unit-label {
        right: 0.875rem !important;
        font-size: 0.9rem !important;
        background: transparent;
    }
    
    .small.text-muted {
        font-size: 0.8rem !important;
        margin-top: 0.25rem !important;
    }
    
    /* Form Fields Visibility */
    .strength-fields,
    .duration-field {
        margin-top: 1rem;
    }
    
    .strength-fields label,
    .duration-field label {
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        display: block;
        color: #2d3748;
    }
    
    /* Form Actions - Mobile Optimized */
    .form-actions {
        flex-direction: column !important;
        gap: 1rem !important;
        margin-top: 1.5rem !important;
        padding-top: 1.5rem !important;
    }
    
    .form-actions button {
        width: 100% !important;
        min-height: 52px !important;
        font-size: 1rem !important;
        border-radius: 14px !important;
    }
    
    .form-actions > div {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    /* Buttons - Mobile Optimized */
    .btn {
        padding: 0.75rem 1.25rem !important;
        font-size: 0.95rem !important;
        border-radius: 12px !important;
        min-height: 52px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .btn i {
        font-size: 1.1rem !important;
        margin-right: 0.5rem !important;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3a86ff, #8338ec) !important;
    }
    
    .btn-success {
        background: linear-gradient(135deg, #38b000, #70e000) !important;
    }
    
    /* Quick Tips Section */
    .workout-form-container.mt-4 {
        margin-top: 0 !important;
        margin-bottom: 1.5rem !important;
    }
    
    .workout-form-container h5 {
        font-size: 1.1rem !important;
        margin-bottom: 1rem !important;
    }
    
    .row.mt-3 {
        margin-top: 1rem !important;
    }
    
    .col-md-4.mb-3 {
        margin-bottom: 1.25rem !important;
        width: 100% !important;
    }
    
    .d-flex.align-items-start {
        gap: 0.75rem;
    }
    
    .d-flex.align-items-start .me-3 i {
        font-size: 1.5rem !important;
    }
    
    .d-flex.align-items-start h6 {
        font-size: 0.95rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .d-flex.align-items-start .small {
        font-size: 0.85rem !important;
        line-height: 1.4;
    }
    
    /* Floating Action Button */
    .floating-action {
        position: fixed;
        bottom: 85px;
        right: 20px;
        z-index: 999;
    }
    
    .floating-btn {
        width: 60px !important;
        height: 60px !important;
        font-size: 1.5rem !important;
        box-shadow: 0 8px 25px rgba(255, 0, 110, 0.3) !important;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .floating-btn:hover {
        transform: scale(1.1) !important;
    }
    
    /* Empty State */
    .empty-state {
        padding: 2rem 1rem !important;
    }
    
    .empty-state i {
        font-size: 3rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .empty-state h4 {
        font-size: 1.2rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    .empty-state p {
        font-size: 0.9rem !important;
        max-width: 250px;
        margin: 0 auto;
    }
    
    /* Alert Messages */
    .alert {
        top: 80px !important;
        right: 0.75rem !important;
        left: 0.75rem !important;
        max-width: none !important;
        margin: 0 !important;
        border-radius: 12px !important;
        padding: 1rem !important;
    }
    
    .alert i {
        font-size: 1.1rem !important;
        margin-right: 0.5rem !important;
    }
    
    /* Animation for mobile */
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .exercise-card,
    .saved-exercise-card {
        animation: slideUp 0.5s ease-out;
    }
    
    /* Stagger animations */
    .exercise-card:nth-child(1) { animation-delay: 0.1s; }
    .exercise-card:nth-child(2) { animation-delay: 0.2s; }
    .exercise-card:nth-child(3) { animation-delay: 0.3s; }
    .exercise-card:nth-child(4) { animation-delay: 0.4s; }
    
    .saved-exercise-card:nth-child(1) { animation-delay: 0.1s; }
    .saved-exercise-card:nth-child(2) { animation-delay: 0.2s; }
    .saved-exercise-card:nth-child(3) { animation-delay: 0.3s; }
    .saved-exercise-card:nth-child(4) { animation-delay: 0.4s; }
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
    
    .date-display {
        font-size: 0.85rem !important;
        padding: 0.5rem 0.75rem !important;
    }
    
    .saved-exercises-container,
    .workout-form-container {
        margin: 0 0.5rem 1rem 0.5rem !important;
        padding: 1.25rem !important;
    }
    
    .saved-exercises-container h4,
    .workout-form-container h4 {
        font-size: 1.1rem !important;
    }
    
    .saved-exercise-card,
    .exercise-card {
        padding: 1rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .exercise-title {
        font-size: 1rem !important;
    }
    
    .input-field input {
        padding: 0.75rem !important;
        font-size: 0.95rem !important;
    }
    
    .btn {
        padding: 0.625rem 1rem !important;
        font-size: 0.9rem !important;
        min-height: 48px !important;
    }
    
    .floating-btn {
        width: 56px !important;
        height: 56px !important;
        font-size: 1.4rem !important;
    }
    
    .empty-state {
        padding: 1.5rem 1rem !important;
    }
    
    .empty-state i {
        font-size: 2.5rem !important;
    }
    
    .empty-state h4 {
        font-size: 1.1rem !important;
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
    
    .input-row {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 1rem !important;
    }
    
    .form-actions {
        flex-direction: row !important;
        gap: 1rem !important;
    }
    
    .form-actions button {
        width: auto !important;
    }
    
    .row.mt-3 {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 1rem !important;
    }
    
    .col-md-4.mb-3 {
        width: 100% !important;
        margin-bottom: 0 !important;
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
    
    .saved-exercises-container,
    .workout-form-container {
        margin-bottom: 1rem !important;
        padding: 1rem !important;
    }
    
    .saved-exercise-card,
    .exercise-card {
        padding: 0.75rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    .floating-action {
        bottom: 70px;
    }
    
    .btn {
        min-height: 44px !important;
        padding: 0.5rem 1rem !important;
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

#saveBtn:disabled {
    animation: buttonPulse 1s infinite;
}

/* Touch feedback for mobile */
@media (hover: none) and (pointer: coarse) {
    .exercise-card:hover,
    .saved-exercise-card:hover {
        transform: none !important;
        box-shadow: none !important;
    }
    
    .exercise-card:active,
    .saved-exercise-card:active {
        transform: scale(0.98) !important;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
    }
    
    .btn-primary:hover,
    .btn-success:hover {
        transform: none !important;
    }
    
    .btn-primary:active,
    .btn-success:active {
        transform: scale(0.98) !important;
    }
    
    .floating-btn:hover {
        transform: none !important;
    }
    
    .floating-btn:active {
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
        
        .saved-exercises-container,
        .workout-form-container,
        .exercise-card,
        .saved-exercise-card {
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
        
        .select2-container--default .select2-selection--single,
        .input-field input {
            background: #2d3748 !important;
            border-color: #374151 !important;
            color: #e2e8f0 !important;
        }
        
        .select2-dropdown {
            background: #1e293b;
            border-color: #374151;
        }
        
        .select2-results__option {
            color: #e2e8f0;
            background: #1e293b;
        }
        
        .select2-results__option--highlighted[aria-selected] {
            background-color: #3a86ff !important;
        }
        
        .unit-label {
            background: transparent !important;
            color: #94a3b8 !important;
        }
        
        .text-muted,
        .small.text-muted,
        .form-text {
            color: #94a3b8 !important;
        }
        
        .exercise-badge {
            color: white !important;
        }
        
        .remove-btn,
        .delete-btn {
            background: #dc2626 !important;
            color: white !important;
        }
        
        .type-strength {
            background: rgba(58, 134, 255, 0.2) !important;
            color: #93c5fd !important;
        }
        
        .type-cardio {
            background: rgba(239, 68, 68, 0.2) !important;
            color: #fca5a5 !important;
        }
        
        .alert {
            background: #2d3748 !important;
            border-color: #374151 !important;
            color: #e2e8f0 !important;
        }
        
        .floating-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #3730a3) !important;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #059669, #065f46) !important;
        }
    }
}

/* Enhanced animations for mobile */
@keyframes slideInFromBottom {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Floating button animation */
.floating-btn {
    animation: slideInFromBottom 0.6s ease-out 0.3s both;
}

/* Exercise card entrance animation */
@keyframes exerciseCardEntrance {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.exercise-card {
    animation: exerciseCardEntrance 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Save button loading animation */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

#saveBtn i.fa-spinner {
    animation: spin 1s linear infinite;
}

/* Form focus states */
.input-field input:focus,
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #3a86ff !important;
    box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2) !important;
}

/* Invalid state styling */
.is-invalid {
    border-color: #ef4444 !important;
}

.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
}

/* Responsive spacing for better mobile readability */
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
    
    .mb-5 {
        margin-bottom: 2rem !important;
    }
    
    .mt-3 {
        margin-top: 1rem !important;
    }
    
    .mt-4 {
        margin-top: 1.5rem !important;
    }
    
    .mt-5 {
        margin-top: 2rem !important;
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
    
    .select2-search__field {
        font-size: 16px !important;
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
                                <span class="saved-exercise-type <?= $exercise_type === 'strength' ? 'type-strength' : 'type-cardio' ?>">
                                    <?= ucfirst($exercise_type) ?>
                                </span>
                            </div>
                            <div class="saved-exercise-details">
                                <?php if ($exercise_type === 'strength'): ?>
                                    <?php if (isset($exercise['sets']) && $exercise['sets']): ?>
                                        <div class="saved-exercise-detail">
                                            <i class="fas fa-redo text-primary"></i>
                                            <span><?= $exercise['sets'] ?> sets</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($exercise['reps']) && $exercise['reps']): ?>
                                        <div class="saved-exercise-detail">
                                            <i class="fas fa-sync-alt text-primary"></i>
                                            <span><?= $exercise['reps'] ?> reps</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($exercise['weight']) && $exercise['weight'] && !in_array($exercise['exercise'], ['Pull-ups', 'Push-ups', 'Lunges', 'Plank'])): ?>
                                        <div class="saved-exercise-detail">
                                            <i class="fas fa-weight text-primary"></i>
                                            <span><?= $exercise['weight'] ?> kg</span>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (isset($exercise['duration']) && $exercise['duration']): ?>
                                        <div class="saved-exercise-detail">
                                            <i class="fas fa-clock text-danger"></i>
                                            <span><?= $exercise['duration'] ?> min</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($exercise['distance']) && $exercise['distance']): ?>
                                        <div class="saved-exercise-detail">
                                            <i class="fas fa-road text-danger"></i>
                                            <span><?= $exercise['distance'] ?> km</span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="saved-exercise-detail">
                                    <i class="fas fa-calendar text-muted"></i>
                                    <span>Today</span>
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
                    <i class="fas fa-dumbbell"></i>
                    <h4>No Exercises Added</h4>
                    <p>Click the button below to add your first exercise</p>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='../dashboard.php'">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </button>
                    <div>
                        <button type="button" class="btn btn-primary me-2" id="addExerciseBtn">
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
        <div class="workout-form-container mt-4">
            <h5><i class="fas fa-lightbulb me-2 text-warning"></i>Workout Tips</h5>
            <div class="row mt-3">
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-weight-hanging text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Progressive Overload</h6>
                            <p class="small text-muted mb-0">Gradually increase weight or reps to continue making progress.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-clock text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Rest Periods</h6>
                            <p class="small text-muted mb-0">Take 60-90 seconds rest between sets for optimal recovery.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-heartbeat text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Form First</h6>
                            <p class="small text-muted mb-0">Always prioritize proper form over heavier weights to prevent injury.</p>
                        </div>
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

    <!-- Mobile Navigation -->
    <div class="mobile-nav">
        <a href="../dashboard.php" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="log.php" class="mobile-nav-item active">
            <i class="fas fa-dumbbell"></i>
            <span>Workouts</span>
        </a>
        <a href="../meals/" class="mobile-nav-item">
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
                        <span class="exercise-type-indicator type-strength" style="display:none;">Strength</span>
                        <span class="exercise-type-indicator type-cardio" style="display:none;">Cardio</span>
                    </div>
                    <button type="button" class="remove-btn" onclick="removeExercise('${cardId}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="input-group">
                    <label for="exercise-select-${exerciseCounter}">Select Exercise</label>
                    <select class="exercise-select" id="exercise-select-${exerciseCounter}" required>
                        <option value="">Choose an exercise...</option>
                        ${options}
                    </select>
                </div>
                
                <div class="strength-fields">
                    <label>Strength Details</label>
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
                    <label>Cardio Duration</label>
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

            const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
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
                    select.closest('.input-group').classList.add('is-invalid');
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

            console.log('Sending workouts:', workouts);

            try {
                const response = await fetch('../api/workout-save.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(workouts)
                });

                console.log('Response status:', response.status);

                // Check if response is OK
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                console.log('Response data:', result);

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
        });
    </script>
</body>

</html>