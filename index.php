<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FitTrack Pro - Fitness & Meal Planner</title>
    
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
            --gradient-primary: linear-gradient(135deg, #3a86ff, #8338ec);
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
            background: var(--gradient-primary);
            z-index: -1;
            border-bottom-left-radius: 40% 20%;
            border-bottom-right-radius: 40% 20%;
        }
        
        .logo {
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            font-size: 2.5rem;
            background: var(--gradient-secondary);
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
            max-width: 1200px;
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
        
        .feature-list {
            list-style: none;
            padding-left: 0;
            margin-top: 1.5rem;
        }
        
        .feature-list li {
            margin-bottom: 1rem;
            padding-left: 2rem;
            position: relative;
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 0;
            color: rgba(255, 255, 255, 0.9);
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
        
        .social-btn {
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
        }
        
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        
        .divider span {
            padding: 0 1rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: rgba(56, 176, 0, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(56, 176, 0, 0.2);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .demo-account {
            background-color: #f8f9fa;
            border-left: 4px solid var(--accent-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .demo-account h6 {
            color: var(--accent-color);
            font-weight: 700;
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
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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
   PREMIUM MOBILE RESPONSIVE DESIGN
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
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
    
    /* Left section - Hero banner */
    .left-section {
        padding: 2.5rem 1.5rem !important;
        border-radius: 0 0 32px 32px;
        text-align: center;
        min-height: 45vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    
    .left-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(135deg, rgba(58, 134, 255, 0.95) 0%, rgba(131, 56, 236, 0.95) 100%);
        z-index: -1;
    }
    
    /* Logo styling */
    .logo {
        font-size: 2.8rem;
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .logo-subtitle {
        font-size: 0.9rem;
        letter-spacing: 4px;
        font-weight: 400;
        opacity: 0.9;
        margin-bottom: 2rem;
        position: relative;
    }
    
    /* Feature list - Grid layout */
    .feature-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-top: 2rem;
    }
    
    .feature-list li {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 1rem;
        margin: 0;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: transform 0.3s ease;
    }
    
    .feature-list li:hover {
        transform: translateY(-3px);
        background: rgba(255, 255, 255, 0.15);
    }
    
    .feature-list li i {
        position: static;
        display: block;
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: white;
    }
    
    .feature-list li span {
        font-size: 0.85rem;
        line-height: 1.3;
        display: block;
    }
    
    /* Right section - Login form */
    .right-section {
        padding: 2.5rem 1.5rem !important;
        background: white;
        border-radius: 32px 32px 0 0;
        margin-top: -20px;
        position: relative;
        z-index: 2;
        box-shadow: 0 -20px 50px rgba(0, 0, 0, 0.1);
    }
    
    /* Welcome header */
    .right-section h3 {
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: 700;
    }
    
    .right-section > p.text-muted {
        font-size: 1rem;
        color: #666;
        margin-bottom: 2rem;
        line-height: 1.5;
    }
    
    /* Form styling */
    .mb-3, .mb-4 {
        margin-bottom: 1.5rem !important;
    }
    
    .form-label {
        font-size: 0.95rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
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
        padding: 0 1.25rem;
        min-width: 60px;
        justify-content: center;
    }
    
    .input-group-text i {
        color: #64748b;
        font-size: 1.1rem;
    }
    
    .form-control {
        border: none;
        padding: 1.25rem 1rem;
        font-size: 1rem;
        background: transparent;
        height: auto;
        min-height: 60px;
    }
    
    .form-control:focus {
        box-shadow: none;
        background: transparent;
    }
    
    .form-control::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }
    
    /* Password toggle button */
    #togglePassword {
        background: transparent;
        border: none;
        border-left: 2px solid #e2e8f0;
        color: #64748b;
        padding: 0 1.25rem;
        min-width: 70px;
        transition: all 0.3s ease;
    }
    
    #togglePassword:hover {
        background: #edf2f7;
        color: #3a86ff;
    }
    
    /* Forgot password link */
    .text-end a {
        font-size: 0.95rem;
        color: #3a86ff;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s ease;
    }
    
    .text-end a:hover {
        color: #2563eb;
        gap: 8px;
    }
    
    /* Login button */
    .btn-primary.pulse-animation {
        padding: 1.25rem;
        font-size: 1.1rem;
        font-weight: 700;
        border-radius: 16px;
        margin-top: 0.5rem;
        min-height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .btn-primary.pulse-animation::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: 0.5s;
    }
    
    .btn-primary.pulse-animation:hover::before {
        left: 100%;
    }
    
    .btn-primary.pulse-animation:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 40px rgba(102, 126, 234, 0.5);
    }
    
    .btn-primary.pulse-animation i {
        font-size: 1.3rem;
        margin-right: 0.75rem;
    }
    
    /* Demo account section */
    .demo-account {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: none;
        border-radius: 20px;
        padding: 1.5rem;
        margin: 2rem 0;
        position: relative;
        overflow: hidden;
        border-left: 5px solid #8b5cf6;
    }
    
    .demo-account::after {
        content: 'DEMO';
        position: absolute;
        top: 10px;
        right: 10px;
        background: #8b5cf6;
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 12px;
        letter-spacing: 1px;
    }
    
    .demo-account h6 {
        color: #7c3aed;
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .demo-account p {
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
        color: #475569;
        display: flex;
        align-items: center;
    }
    
    .demo-account p strong {
        color: #1e293b;
        font-weight: 600;
        min-width: 80px;
    }
    
    .demo-account p span {
        background: white;
        padding: 4px 12px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-weight: 600;
        margin-left: 10px;
        border: 1px solid #cbd5e1;
    }
    
    /* Divider */
    .divider {
        margin: 2rem 0;
        position: relative;
    }
    
    .divider::before,
    .divider::after {
        border-bottom: 2px solid #e2e8f0;
    }
    
    .divider span {
        background: white;
        padding: 0 1rem;
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 600;
    }
    
    /* Social buttons */
    .row.g-2 {
        gap: 1rem !important;
    }
    
    .col-6 {
        width: 100%;
        flex: 0 0 100%;
    }
    
    .social-btn {
        padding: 1rem;
        font-size: 1rem;
        border-radius: 16px;
        font-weight: 600;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        border: 2px solid #e2e8f0;
        background: white;
        margin-bottom: 0.5rem;
    }
    
    .social-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e1;
    }
    
    .social-btn i {
        font-size: 1.3rem;
        margin-right: 0.75rem;
    }
    
    /* Register button */
    .btn-outline-primary {
        padding: 1.25rem;
        font-size: 1.1rem;
        border-radius: 16px;
        font-weight: 700;
        min-height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-width: 2px;
        border-color: #3a86ff;
        color: #3a86ff;
        background: white;
        margin-top: 1rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .btn-outline-primary:hover {
        background: linear-gradient(135deg, #3a86ff 0%, #2667cc 100%);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(58, 134, 255, 0.25);
        border-color: transparent;
    }
    
    .btn-outline-primary i {
        font-size: 1.3rem;
        margin-right: 0.75rem;
    }
    
    .text-center p {
        font-size: 1.05rem;
        color: #475569;
        margin-bottom: 1rem;
        font-weight: 500;
    }
    
    /* Footer */
    .footer {
        padding: 2rem 1.5rem;
        margin-top: 2rem;
        background: #f8fafc;
        border-radius: 20px;
    }
    
    .footer p {
        font-size: 0.85rem;
        line-height: 1.6;
        color: #64748b;
        margin-bottom: 0.5rem;
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
    
    /* Floating shapes - hide on mobile */
    .floating-shapes {
        display: none;
    }
    
    /* Custom scrollbar for mobile */
    ::-webkit-scrollbar {
        width: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #3a86ff 0%, #8338ec 100%);
        border-radius: 10px;
    }
}

/* Extra small devices (phones under 400px) */
@media (max-width: 399.98px) {
    .left-section {
        padding: 2rem 1rem !important;
        min-height: 40vh;
    }
    
    .logo {
        font-size: 2.2rem;
    }
    
    .feature-list {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .right-section {
        padding: 2rem 1rem !important;
    }
    
    .right-section h3 {
        font-size: 1.6rem;
    }
    
    .form-control {
        padding: 1rem;
        min-height: 56px;
    }
    
    .btn-primary.pulse-animation,
    .btn-outline-primary {
        padding: 1rem;
        min-height: 56px;
    }
    
    .demo-account {
        padding: 1.25rem;
    }
    
    .demo-account p {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .demo-account p span {
        margin-left: 0;
        width: 100%;
        text-align: center;
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
    
    .feature-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .feature-list li {
        padding: 1rem;
        text-align: center;
    }
    
    .feature-list li i {
        position: static;
        display: block;
        margin-bottom: 0.5rem;
    }
}

/* Landscape mode optimization */
@media (max-height: 600px) and (orientation: landscape) {
    body {
        overflow-y: auto;
    }
    
    .left-section {
        min-height: auto;
        padding: 1.5rem !important;
    }
    
    .feature-list {
        display: none;
    }
    
    .right-section {
        padding: 1.5rem !important;
    }
    
    .form-control {
        padding: 0.75rem;
        min-height: 50px;
    }
    
    .btn-primary.pulse-animation,
    .btn-outline-primary {
        padding: 0.75rem;
        min-height: 50px;
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

.btn-primary.pulse-animation.loading {
    animation: buttonLoading 0.6s ease-in-out infinite;
}

/* Success/error message styling */
.alert-success {
    border-radius: 16px;
    border: none;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
}

.alert-danger {
    border-radius: 16px;
    border: none;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
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
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    }
    50% {
        transform: scale(1.03);
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    }
}

/* Custom focus ring */
.form-control:focus,
#togglePassword:focus,
.btn:focus {
    outline: none;
    box-shadow: 0 0 0 4px rgba(58, 134, 255, 0.2);
}
    </style>
</head>
<body>
    <!-- Floating background shapes -->
    <div class="floating-shapes shape-1"></div>
    <div class="floating-shapes shape-2"></div>
    
    <div class="container py-5">
        <div class="card-container">
            <!-- Success message for registration -->
            <?php if(isset($_GET['registered'])): ?>
                <div class="alert-success text-center mb-4">
                    <i class="fas fa-check-circle me-2"></i>Registration successful! Please login to your account.
                </div>
            <?php endif; ?>
            
            <div class="main-card row g-0">
                <!-- Left Section - Brand & Features -->
                <div class="col-lg-6 left-section">
                    <div class="mb-4">
                        <h1 class="logo">FitTrack Pro</h1>
                        <p class="logo-subtitle">FITNESS & MEAL PLANNER</p>
                    </div>
                    
                    <h2 class="mb-4">Transform Your Fitness Journey</h2>
                    <p class="mb-4">Join thousands of users who are achieving their fitness goals with our intelligent platform.</p>
                    
                    <ul class="feature-list">
                        <li><i class="fas fa-dumbbell me-2"></i> Personalized workout plans</li>
                        <li><i class="fas fa-utensils me-2"></i> Custom meal planning</li>
                        <li><i class="fas fa-chart-line me-2"></i> Progress tracking & analytics</li>
                        <li><i class="fas fa-video me-2"></i> Exercise library with videos</li>
                        <li><i class="fas fa-mobile-alt me-2"></i> Mobile app sync</li>
                    </ul>
                    
                    <div class="mt-4">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-quote-left fa-2x opacity-50"></i>
                            </div>
                            <div>
                                <p class="mb-0 fst-italic">"FitTrack Pro helped me lose 20lbs and completely transformed my relationship with fitness."</p>
                                <small class="opacity-75">- Sarah J., Verified User</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Section - Login Form -->
                <div class="col-lg-6 right-section">
                    <h3 class="mb-4">Welcome Back</h3>
                    <p class="text-muted mb-4">Sign in to access your personalized fitness dashboard</p>
                    
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-envelope text-primary"></i>
                                </span>
                                <input type="email" name="email" class="form-control border-start-0" placeholder="Enter your email" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-lock text-primary"></i>
                                </span>
                                <input type="password" name="password" class="form-control border-start-0" placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="text-end mt-2">
                                <a href="#" class="text-decoration-none">Forgot password?</a>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-4 pulse-animation">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                    
                    <!-- Demo Account Info -->
                    <div class="demo-account">
                        <h6><i class="fas fa-user-secret me-2"></i>Demo Account</h6>
                        <p class="mb-1"><strong>Email:</strong> admin@wtp.com</p>
                        <p class="mb-0"><strong>Password:</strong> Admin123</p>
                    </div>
                    
                    <div class="divider">
                        <span>Or sign in with</span>
                    </div>
                    
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <button type="button" class="btn btn-light social-btn w-100 border">
                                <i class="fab fa-google text-danger me-2"></i>Google
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-light social-btn w-100 border">
                                <i class="fab fa-apple me-2"></i>Apple
                            </button>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <p class="mb-3">New to FitTrack Pro?</p>
                        <a href="register.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer mt-5">
                <p class="mb-2">By continuing, you agree to our <a href="#">Terms</a> and <a href="#">Privacy Policy</a>.</p>
                <p class="mb-0">© 2023 FitTrack Pro. All rights reserved. | <a href="#">Contact Support</a></p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.querySelector('input[name="password"]');
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
        
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/fitness-tracker/sw.js');
        }
        
        // Add some interactive animation to the login button on hover
        document.querySelector('button[type="submit"]').addEventListener('mouseover', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        document.querySelector('button[type="submit"]').addEventListener('mouseout', function() {
            this.style.transform = 'translateY(0)';
        });
    </script>
</body>
</html>