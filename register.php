<?php
require 'includes/db.php';

if ($_POST) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $password]);
        header("Location: index.php?registered=1");
        exit();
    } catch(Exception $e) {
        $error = "Email already exists!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - FitTrack Pro</title>
    
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
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #38b000;
            --error-color: #e63946;
            --gradient-primary: linear-gradient(185deg, #3a86ff, #8338ec);
            --gradient-secondary: linear-gradient(135deg, #ff006e, #fb5607);
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
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 50vh;
            background: var(--gradient-secondary);
            z-index: -1;
            border-bottom-left-radius: 40% 20%;
            border-bottom-right-radius: 40% 20%;
        }
        
        .logo {
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            font-size: 2.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .logo-subtitle {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 300;
            font-size: 1rem;
            letter-spacing: 2px;
        }
        
        .card-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .main-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: none;
        }
        
        .left-section {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .right-section {
            padding: 3rem;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 134, 255, 0.25);
        }
        
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .password-strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .password-strength-weak {
            background-color: var(--error-color);
            width: 25%;
        }
        
        .password-strength-fair {
            background-color: #ffbe0b;
            width: 50%;
        }
        
        .password-strength-good {
            background-color: #ff9e00;
            width: 75%;
        }
        
        .password-strength-strong {
            background-color: var(--success-color);
            width: 100%;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }
        
        .requirement i {
            margin-right: 8px;
            font-size: 0.8rem;
        }
        
        .requirement.valid {
            color: var(--success-color);
        }
        
        .requirement.invalid {
            color: #6c757d;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 15px rgba(58, 134, 255, 0.3);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .alert-error {
            background-color: rgba(230, 57, 70, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(230, 57, 70, 0.2);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-container {
            display: flex;
            align-items: flex-start;
            margin: 1.5rem 0;
        }
        
        .checkbox-container input {
            margin-top: 0.25rem;
            margin-right: 10px;
            accent-color: var(--primary-color);
        }
        
        .checkbox-container label {
            user-select: none;
            line-height: 1.5;
        }
        
        .benefit-list {
            list-style: none;
            padding-left: 0;
            margin-top: 2rem;
        }
        
        .benefit-list li {
            margin-bottom: 1rem;
            padding-left: 2rem;
            position: relative;
        }
        
        .benefit-list li i {
            position: absolute;
            left: 0;
            top: 0;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .footer {
            text-align: center;
            padding: 2rem 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .footer a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .floating-shapes {
            position: absolute;
            z-index: -1;
            opacity: 0.1;
        }
        
        .shape-1 {
            top: 10%;
            left: 5%;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--gradient-secondary);
        }
        
        .shape-2 {
            bottom: 15%;
            right: 8%;
            width: 150px;
            height: 150px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            background: var(--gradient-primary);
        }
        
        @media (max-width: 992px) {
            .left-section {
                padding: 2rem;
            }
            
            .right-section {
                padding: 2rem;
            }
            
            .logo {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            body::before {
                height: 40vh;
            }
            
            .left-section, .right-section {
                padding: 1.5rem;
            }
        }

        /* ============================================
   PREMIUM MOBILE RESPONSIVE DESIGN - REGISTER
   ============================================ */

/* Base mobile styles */
@media (max-width: 767.98px) {
    /* Reset container spacing */
    .container.py-5 {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        max-width: 100%;
        padding-left: 0;
        padding-right: 0;
    }
    
    /* Background gradient for mobile */
    body {
        background: linear-gradient(135deg, #ff006e 0%, #fb5607 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        overflow-y: auto;
    }
    
    body::before {
        display: none;
    }
    
    /* Card container */
    .card-container {
        width: 100%;
        margin: 0;
        padding: 0;
    }
    
    /* Main card layout - Stack vertically */
    .main-card {
        flex-direction: column;
        margin: 0;
        border-radius: 0;
        min-height: 100vh;
        box-shadow: none;
        background: transparent;
    }
    
    /* Left section - Benefits */
    .left-section {
        padding: 2.5rem 1.5rem !important;
        border-radius: 0 0 32px 32px;
        text-align: center;
        min-height: auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
        background: linear-gradient(185deg, #3a86ff 0%, #8338ec 100%);
    }
    
    /* Logo styling */
    .logo {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .logo-subtitle {
        font-size: 0.9rem;
        letter-spacing: 3px;
        font-weight: 400;
        opacity: 0.9;
        margin-bottom: 1.5rem;
    }
    
    .left-section h2 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
        line-height: 1.3;
        font-weight: 600;
    }
    
    .left-section p {
        font-size: 0.95rem;
        line-height: 1.5;
        opacity: 0.9;
        margin-bottom: 1.5rem;
    }
    
    /* Benefit list */
    .benefit-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .benefit-list li {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 1rem;
        margin: 0;
        text-align: left;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: transform 0.3s ease;
        display: flex;
        align-items: flex-start;
    }
    
    .benefit-list li:hover {
        transform: translateY(-3px);
        background: rgba(255, 255, 255, 0.15);
    }
    
    .benefit-list li i {
        position: static;
        font-size: 1.2rem;
        margin-right: 0.75rem;
        margin-top: 2px;
        color: white;
        min-width: 24px;
    }
    
    .benefit-list li span {
        font-size: 0.9rem;
        line-height: 1.4;
        display: block;
    }
    
    /* Testimonial */
    .left-section .d-flex {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 1.25rem;
        margin-top: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .left-section .fa-quote-left {
        font-size: 1.5rem;
        opacity: 0.6;
    }
    
    /* Right section - Registration form */
    .right-section {
        padding: 2.5rem 1.5rem !important;
        background: white;
        border-radius: 32px 32px 0 0;
        margin-top: -20px;
        position: relative;
        z-index: 2;
        box-shadow: 0 -20px 50px rgba(0, 0, 0, 0.1);
    }
    
    /* Registration header */
    .right-section h3 {
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
        background: linear-gradient(185deg, #3a86ff 0%, #8338ec 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: 700;
    }
    
    .right-section > p.text-muted {
        font-size: 1rem;
        color: #666;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }
    
    /* Form styling */
    .mb-3 {
        margin-bottom: 1.25rem !important;
    }
    
    .form-label {
        font-size: 0.95rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .input-group {
        border-radius: 16px;
        overflow: hidden;
        border: 2px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #f8fafc;
    }
    
    .input-group:focus-within {
        border-color: #3a86ff;
        background: white;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(58, 134, 255, 0.15);
    }
    
    .input-group-text {
        background: transparent;
        border: none;
        padding: 0 1rem;
        min-width: 50px;
        justify-content: center;
    }
    
    .input-group-text i {
        color: #64748b;
        font-size: 1.1rem;
    }
    
    .form-control {
        border: none;
        padding: 1rem;
        font-size: 1rem;
        background: transparent;
        height: auto;
        min-height: 56px;
    }
    
    .form-control:focus {
        box-shadow: none;
        background: transparent;
    }
    
    .form-control::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }
    
    /* Password toggle buttons */
    #togglePassword,
    #toggleConfirmPassword {
        background: transparent;
        border: none;
        border-left: 2px solid #e2e8f0;
        color: #64748b;
        padding: 0 1rem;
        min-width: 60px;
        transition: all 0.3s ease;
    }
    
    #togglePassword:hover,
    #toggleConfirmPassword:hover {
        background: #edf2f7;
        color: #3a86ff;
    }
    
    /* Password strength indicator */
    .password-strength {
        height: 6px;
        border-radius: 8px;
        margin-top: 8px;
        background-color: #e2e8f0;
        overflow: hidden;
    }
    
    .password-strength-fill {
        height: 100%;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    /* Password requirements */
    .password-requirements {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .requirement {
        font-size: 0.8rem;
        padding: 0.5rem;
        border-radius: 8px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }
    
    .requirement i {
        margin-right: 6px;
        font-size: 0.7rem;
    }
    
    .requirement.valid {
        background: #f0fdf4;
        color: #16a34a;
    }
    
    .requirement.invalid {
        background: #f8fafc;
        color: #64748b;
    }
    
    /* Password match message */
    #passwordMatchMessage {
        font-size: 0.85rem;
        padding: 0.5rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        margin-top: 0.5rem;
    }
    
    #passwordMatchMessage i {
        margin-right: 6px;
    }
    
    /* Terms checkbox */
    .checkbox-container {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1rem;
        margin: 1.5rem 0;
        border: 1px solid #e2e8f0;
    }
    
    .checkbox-container input {
        margin-top: 0.25rem;
        transform: scale(1.2);
    }
    
    .checkbox-container label {
        font-size: 0.9rem;
        line-height: 1.5;
        color: #475569;
    }
    
    .checkbox-container label a {
        color: #3a86ff;
        font-weight: 600;
        text-decoration: none;
    }
    
    .checkbox-container label a:hover {
        text-decoration: underline;
    }
    
    /* Submit button */
    .btn-primary {
        padding: 1.125rem;
        font-size: 1.1rem;
        font-weight: 700;
        border-radius: 16px;
        margin-top: 0.5rem;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(185deg, #3a86ff 0%, #8338ec 100%);
        border: none;
        box-shadow: 0 10px 30px rgba(58, 134, 255, 0.4);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: 0.5s;
    }
    
    .btn-primary:hover::before {
        left: 100%;
    }
    
    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 40px rgba(58, 134, 255, 0.5);
    }
    
    .btn-primary i {
        font-size: 1.2rem;
        margin-right: 0.75rem;
    }
    
    /* Login link */
    .text-center p {
        font-size: 1rem;
        color: #475569;
        margin-top: 1rem;
    }
    
    .text-center a {
        color: #3a86ff;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .text-center a:hover {
        color: #2563eb;
        text-decoration: underline;
    }
    
    /* Footer */
    .footer {
        padding: 1.5rem;
        margin-top: 2rem;
        background: #f8fafc;
        border-radius: 20px;
        text-align: center;
    }
    
    .footer p {
        font-size: 0.85rem;
        line-height: 1.6;
        color: #64748b;
    }
    
    .footer a {
        color: #3a86ff;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .footer a:hover {
        color: #2563eb;
        text-decoration: underline;
    }
    
    /* Error alert */
    .alert-error {
        border-radius: 16px;
        border: none;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 1rem 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
    }
    
    /* Floating shapes - hide on mobile */
    .floating-shapes {
        display: none;
    }
}

/* Extra small devices (phones under 400px) */
@media (max-width: 399.98px) {
    .left-section {
        padding: 2rem 1rem !important;
    }
    
    .logo {
        font-size: 2rem;
    }
    
    .benefit-list {
        gap: 0.5rem;
    }
    
    .benefit-list li {
        padding: 0.75rem;
    }
    
    .benefit-list li i {
        font-size: 1rem;
        margin-right: 0.5rem;
    }
    
    .benefit-list li span {
        font-size: 0.85rem;
    }
    
    .right-section {
        padding: 2rem 1rem !important;
    }
    
    .right-section h3 {
        font-size: 1.5rem;
    }
    
    .form-control {
        padding: 0.875rem;
        min-height: 52px;
    }
    
    .btn-primary {
        padding: 1rem;
        min-height: 56px;
        font-size: 1rem;
    }
    
    .password-requirements {
        grid-template-columns: 1fr;
    }
}

/* Tablet portrait mode */
@media (min-width: 768px) and (max-width: 991.98px) {
    .container.py-5 {
        padding-top: 2rem !important;
        padding-bottom: 2rem !important;
    }
    
    .left-section,
    .right-section {
        padding: 2rem !important;
    }
    
    .logo {
        font-size: 2.2rem;
    }
    
    .benefit-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .benefit-list li {
        padding: 1rem;
        text-align: left;
    }
    
    .benefit-list li i {
        position: static;
        display: block;
        margin-bottom: 0.5rem;
    }
}

/* Landscape mode optimization */
@media (max-height: 700px) and (orientation: landscape) {
    body {
        overflow-y: auto;
    }
    
    .left-section {
        min-height: auto;
        padding: 1.5rem !important;
    }
    
    .benefit-list {
        display: none;
    }
    
    .right-section {
        padding: 1.5rem !important;
    }
    
    .form-control {
        padding: 0.75rem;
        min-height: 48px;
    }
    
    .btn-primary {
        padding: 0.875rem;
        min-height: 52px;
    }
    
    .checkbox-container {
        padding: 0.75rem;
        margin: 1rem 0;
    }
}

/* iPhone notch and safe area support */
@supports (padding: max(0px)) {
    .container.py-5 {
        padding-left: max(0px, env(safe-area-inset-left)) !important;
        padding-right: max(0px, env(safe-area-inset-right)) !important;
        padding-top: max(0px, env(safe-area-inset-top)) !important;
        padding-bottom: max(0px, env(safe-area-inset-bottom)) !important;
    }
}

/* Loading animation for form submission */
@keyframes buttonLoading {
    0% { transform: scale(1); }
    50% { transform: scale(0.98); }
    100% { transform: scale(1); }
}

.btn-primary.loading {
    animation: buttonLoading 0.6s ease-in-out infinite;
}

/* Input error state */
.input-group.error {
    border-color: #ef4444;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Enhanced pulse animation */
@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 10px 30px rgba(58, 134, 255, 0.4);
    }
    50% {
        transform: scale(1.03);
        box-shadow: 0 15px 40px rgba(58, 134, 255, 0.6);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 10px 30px rgba(58, 134, 255, 0.4);
    }
}

.btn-primary:not(.loading) {
    animation: pulse 2s infinite;
}

/* Custom focus ring */
.form-control:focus,
#togglePassword:focus,
#toggleConfirmPassword:focus,
.btn:focus {
    outline: none;
    box-shadow: 0 0 0 4px rgba(58, 134, 255, 0.2);
}

/* Password match success/fail styles */
.password-match {
    border-color: #10b981 !important;
}

.password-mismatch {
    border-color: #ef4444 !important;
}

/* Success message animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-error {
    animation: fadeInUp 0.5s ease-out;
}
    </style>
</head>
<body>
    <!-- Floating background shapes -->
    <div class="floating-shapes shape-1"></div>
    <div class="floating-shapes shape-2"></div>
    
    <div class="container py-5">
        <div class="card-container">
            <!-- Error message for registration -->
            <?php if(isset($error)): ?>
                <div class="alert-error text-center mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>Error: <?= $error ?>
                </div>
            <?php endif; ?>
            
            <div class="main-card row g-0">
                <!-- Left Section - Benefits & Info -->
                <div class="col-lg-5 left-section">
                    <div class="mb-4">
                        <h1 class="logo">FitTrack Pro</h1>
                        <p class="logo-subtitle">JOIN OUR FITNESS COMMUNITY</p>
                    </div>
                    
                    <h2 class="mb-4">Start Your Fitness Journey Today</h2>
                    <p class="mb-4">Create your account and unlock personalized fitness and nutrition plans designed just for you.</p>
                    
                    <ul class="benefit-list">
                        <li><i class="fas fa-chart-line me-2"></i> Track your progress with advanced analytics</li>
                        <li><i class="fas fa-heartbeat me-2"></i> Get personalized workout recommendations</li>
                        <li><i class="fas fa-apple-alt me-2"></i> Custom meal plans based on your goals</li>
                        <li><i class="fas fa-trophy me-2"></i> Earn achievements and stay motivated</li>
                        <li><i class="fas fa-users me-2"></i> Join a supportive community</li>
                    </ul>
                    
                    <div class="mt-4">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-quote-left fa-2x opacity-50"></i>
                            </div>
                            <div>
                                <p class="mb-0 fst-italic">"Joining FitTrack Pro was the best decision for my health journey. The personalized plans made all the difference!"</p>
                                <small class="opacity-75">- Michael T., Member for 2 years</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Section - Registration Form -->
                <div class="col-lg-7 right-section">
                    <h3 class="mb-4">Create Your Account</h3>
                    <p class="text-muted mb-4">Fill in your details to get started</p>
                    
                    <form method="POST" id="registerForm">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-user text-primary"></i>
                                    </span>
                                    <input type="text" name="name" class="form-control border-start-0" placeholder="Enter your full name" required>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-envelope text-primary"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control border-start-0" placeholder="Enter your email" required>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-lock text-primary"></i>
                                    </span>
                                    <input type="password" name="password" id="password" class="form-control border-start-0" placeholder="Create a strong password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                
                                <!-- Password strength indicator -->
                                <div class="password-strength mt-2">
                                    <div class="password-strength-fill" id="passwordStrength"></div>
                                </div>
                                
                                <!-- Password requirements -->
                                <div class="password-requirements mt-2">
                                    <div class="requirement invalid" id="reqLength">
                                        <i class="fas fa-circle"></i>
                                        <span>At least 8 characters</span>
                                    </div>
                                    <div class="requirement invalid" id="reqUppercase">
                                        <i class="fas fa-circle"></i>
                                        <span>Contains uppercase letter</span>
                                    </div>
                                    <div class="requirement invalid" id="reqLowercase">
                                        <i class="fas fa-circle"></i>
                                        <span>Contains lowercase letter</span>
                                    </div>
                                    <div class="requirement invalid" id="reqNumber">
                                        <i class="fas fa-circle"></i>
                                        <span>Contains number</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-lock text-primary"></i>
                                    </span>
                                    <input type="password" name="confirmPassword" id="confirmPassword" class="form-control border-start-0" placeholder="Confirm your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordMatchMessage" class="mt-2" style="font-size: 0.85rem;"></div>
                            </div>
                        </div>
                        
                        <div class="checkbox-container">
                            <input type="checkbox" id="termsCheck" required>
                            <label for="termsCheck">
                                I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a>. I understand that my data will be used to personalize my fitness experience.
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-4" id="submitBtn">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account? <a href="index.php" class="text-decoration-none">Back to Login</a></p>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer mt-5">
                <p class="mb-0">© 2023 FitTrack Pro. All rights reserved. | <a href="#">Contact Support</a> | <a href="#">Privacy Policy</a></p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const icon = this.querySelector('i');
            
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                confirmPasswordInputInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const submitBtn = document.getElementById('submitBtn');
            
            // Reset requirements
            let strength = 0;
            let reqsMet = 0;
            
            // Check length
            if (password.length >= 8) {
                document.getElementById('reqLength').classList.remove('invalid');
                document.getElementById('reqLength').classList.add('valid');
                document.getElementById('reqLength').querySelector('i').className = 'fas fa-check-circle';
                strength += 25;
                reqsMet++;
            } else {
                document.getElementById('reqLength').classList.remove('valid');
                document.getElementById('reqLength').classList.add('invalid');
                document.getElementById('reqLength').querySelector('i').className = 'fas fa-circle';
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                document.getElementById('reqUppercase').classList.remove('invalid');
                document.getElementById('reqUppercase').classList.add('valid');
                document.getElementById('reqUppercase').querySelector('i').className = 'fas fa-check-circle';
                strength += 25;
                reqsMet++;
            } else {
                document.getElementById('reqUppercase').classList.remove('valid');
                document.getElementById('reqUppercase').classList.add('invalid');
                document.getElementById('reqUppercase').querySelector('i').className = 'fas fa-circle';
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                document.getElementById('reqLowercase').classList.remove('invalid');
                document.getElementById('reqLowercase').classList.add('valid');
                document.getElementById('reqLowercase').querySelector('i').className = 'fas fa-check-circle';
                strength += 25;
                reqsMet++;
            } else {
                document.getElementById('reqLowercase').classList.remove('valid');
                document.getElementById('reqLowercase').classList.add('invalid');
                document.getElementById('reqLowercase').querySelector('i').className = 'fas fa-circle';
            }
            
            // Check numbers
            if (/[0-9]/.test(password)) {
                document.getElementById('reqNumber').classList.remove('invalid');
                document.getElementById('reqNumber').classList.add('valid');
                document.getElementById('reqNumber').querySelector('i').className = 'fas fa-check-circle';
                strength += 25;
                reqsMet++;
            } else {
                document.getElementById('reqNumber').classList.remove('valid');
                document.getElementById('reqNumber').classList.add('invalid');
                document.getElementById('reqNumber').querySelector('i').className = 'fas fa-circle';
            }
            
            // Update strength bar
            strengthBar.style.width = strength + '%';
            
            // Update strength bar color
            if (strength < 25) {
                strengthBar.className = 'password-strength-fill';
            } else if (strength < 50) {
                strengthBar.className = 'password-strength-fill password-strength-weak';
            } else if (strength < 75) {
                strengthBar.className = 'password-strength-fill password-strength-fair';
            } else if (strength < 100) {
                strengthBar.className = 'password-strength-fill password-strength-good';
            } else {
                strengthBar.className = 'password-strength-fill password-strength-strong';
            }
            
            // Check password match
            checkPasswordMatch();
        });
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const messageElement = document.getElementById('passwordMatchMessage');
            
            if (confirmPassword === '') {
                messageElement.textContent = '';
                messageElement.style.color = '';
                return;
            }
            
            if (password === confirmPassword) {
                messageElement.innerHTML = '<i class="fas fa-check-circle me-1"></i> Passwords match';
                messageElement.style.color = 'var(--success-color)';
            } else {
                messageElement.innerHTML = '<i class="fas fa-times-circle me-1"></i> Passwords do not match';
                messageElement.style.color = 'var(--error-color)';
            }
        }
        
        // Listen for confirm password input
        document.getElementById('confirmPassword').addEventListener('input', checkPasswordMatch);
        
        // Form validation before submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const termsCheck = document.getElementById('termsCheck').checked;
            
            // Check if passwords match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please make sure both passwords are identical.');
                return;
            }
            
            // Check if terms are accepted
            if (!termsCheck) {
                e.preventDefault();
                alert('Please agree to the Terms of Service and Privacy Policy to continue.');
                return;
            }
            
            // Check password strength (at least 3 requirements met)
            let reqsMet = 0;
            if (password.length >= 8) reqsMet++;
            if (/[A-Z]/.test(password)) reqsMet++;
            if (/[a-z]/.test(password)) reqsMet++;
            if (/[0-9]/.test(password)) reqsMet++;
            
            if (reqsMet < 3) {
                e.preventDefault();
                alert('Please use a stronger password. Your password should meet at least 3 of the requirements.');
                return;
            }
            
            // If all checks pass, show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
            submitBtn.disabled = true;
        });
        
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/fitness-tracker/sw.js');
        }
    </script>
</body>
</html>