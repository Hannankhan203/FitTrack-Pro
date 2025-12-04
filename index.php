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
            --primary: #00d4ff;
            --primary-dark: #0099cc;
            --secondary: #ff2d75;
            --accent: #9d4edd;
            --dark: #0f172a;
            --darker: #0a0f1f;
            --light: #f8fafc;
            --gray: #64748b;
            --success: #00e676;
            --card-bg: rgba(255, 255, 255, 0.05);
            --gradient: linear-gradient(135deg, #00d4ff 0%, #9d4edd 50%, #ff2d75 100%);
            --gradient-dark: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --neon-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
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
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 80%, rgba(157, 78, 221, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 212, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 45, 117, 0.1) 0%, transparent 50%);
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.03"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            z-index: -1;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px 0;
        }

        .logo {
            font-size: 3.5rem;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            letter-spacing: -1px;
            position: relative;
            display: inline-block;
        }

        .logo::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--gradient);
            border-radius: 2px;
            filter: drop-shadow(var(--neon-shadow));
        }

        .tagline {
            font-size: 1.1rem;
            color: var(--gray);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 20px;
            font-weight: 300;
        }

        /* Main Grid Layout */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Left Panel - Hero */
        .hero-panel {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px;
            border: 1px solid var(--glass-border);
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .hero-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero-description {
            color: #cbd5e1;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: var(--neon-shadow);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Features */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--glass-border);
            transform: translateX(5px);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-icon i {
            color: white;
            font-size: 1.2rem;
        }

        .feature-text {
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Right Panel - Login */
        .login-panel {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px;
            border: 1px solid var(--glass-border);
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .login-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--secondary), transparent);
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #fda4af 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .welcome-subtitle {
            color: var(--gray);
            margin-bottom: 30px;
            font-size: 1rem;
        }

        /* Form */
        .form-group {
            margin-bottom: 25px;
        }

        .input-label {
            display: block;
            margin-bottom: 8px;
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .input-wrapper:focus-within {
            border-color: var(--primary);
            box-shadow: var(--neon-shadow);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .input-wrapper:focus-within .input-icon {
            color: var(--primary);
        }

        .form-input {
            width: 100%;
            padding: 18px 20px 18px 60px;
            background: transparent;
            border: none;
            color: var(--light);
            font-size: 1rem;
            outline: none;
        }

        .form-input::placeholder {
            color: #64748b;
        }

        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .forgot-link {
            display: inline-block;
            margin-top: 10px;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Login Button */
        .login-btn {
            width: 100%;
            padding: 20px;
            background: var(--gradient);
            border: none;
            border-radius: 15px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin: 10px 0 25px;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow:
                0 10px 30px rgba(0, 212, 255, 0.4),
                0 5px 15px rgba(157, 78, 221, 0.3);
        }

        .login-btn i {
            margin-right: 10px;
        }

        /* Demo Box */
        .demo-box {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1) 0%, rgba(157, 78, 221, 0.1) 100%);
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 20px;
            padding: 25px;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }

        .demo-box::before {
            content: 'DEMO ACCESS';
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--gradient);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            letter-spacing: 1px;
        }

        .demo-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .demo-credentials {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .credential-item {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 12px;
        }

        .credential-label {
            font-size: 0.8rem;
            color: var(--gray);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .credential-value {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--light);
            font-size: 0.95rem;
            word-break: break-all;
        }

        /* Social Login */
        .social-divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .social-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--glass-border), transparent);
        }

        .social-divider span {
            background: var(--darker);
            padding: 0 20px;
            color: var(--gray);
            font-size: 0.9rem;
            position: relative;
        }

        .social-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            color: var(--light);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            border-color: var(--primary);
        }

        .social-btn.google:hover {
            background: #4285f4;
            border-color: #4285f4;
        }

        .social-btn.apple:hover {
            background: #000;
            border-color: #000;
        }

        /* Register CTA */
        .register-cta {
            text-align: center;
            margin-top: 30px;
        }

        .register-text {
            color: var(--gray);
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .register-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 18px;
            background: transparent;
            border: 2px solid var(--primary);
            border-radius: 15px;
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .register-btn:hover {
            background: var(--primary);
            color: var(--dark);
            transform: translateY(-3px);
            box-shadow: var(--neon-shadow);
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 50px;
            padding: 30px 0;
            color: var(--gray);
            font-size: 0.9rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 15px;
        }

        .footer-link {
            color: var(--gray);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: var(--primary);
        }

        .copyright {
            font-size: 0.8rem;
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            z-index: -1;
            opacity: 0.1;
            filter: blur(40px);
        }

        .floating-1 {
            top: 10%;
            left: 5%;
            width: 300px;
            height: 300px;
            background: var(--primary);
            border-radius: 50%;
        }

        .floating-2 {
            bottom: 10%;
            right: 5%;
            width: 400px;
            height: 400px;
            background: var(--secondary);
            border-radius: 50%;
        }

        /* Animations */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        @keyframes glow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        .pulse {
            animation: glow 2s ease-in-out infinite;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-grid {
                grid-template-columns: 1fr;
                max-width: 800px;
            }

            .logo {
                font-size: 3rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                margin-bottom: 30px;
            }

            .logo {
                font-size: 2.5rem;
            }

            .hero-panel,
            .login-panel {
                padding: 30px;
            }

            .hero-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .demo-credentials {
                grid-template-columns: 1fr;
            }

            .social-buttons {
                grid-template-columns: 1fr;
            }

            .footer-links {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 2rem;
            }

            .hero-panel,
            .login-panel {
                padding: 20px;
            }

            .hero-title {
                font-size: 1.8rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .welcome-title {
                font-size: 1.6rem;
            }
        }

        /* Dark mode enhancements */
        @media (prefers-color-scheme: dark) {
            body {
                background: var(--darker);
            }
        }

        /* High contrast mode */
        @media (prefers-contrast: high) {
            :root {
                --primary: #00ffff;
                --secondary: #ff00ff;
                --accent: #ffff00;
            }
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                color: black;
            }

            .hero-panel,
            .login-panel {
                border: 1px solid #ddd;
                box-shadow: none;
                background: white;
            }

            .floating-element {
                display: none;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Selection color */
        ::selection {
            background: var(--primary);
            color: white;
        }

        /* Focus styles */
        :focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Utility classes */
        .hidden {
            display: none !important;
        }

        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Loading state */
        .loading {
            position: relative;
            pointer-events: none;
            opacity: 0.8;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Error state */
        .error {
            border-color: var(--secondary) !important;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .error-message {
            color: var(--secondary);
            font-size: 0.9rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Success state */
        .success {
            border-color: var(--success) !important;
        }

        .success-message {
            color: var(--success);
            font-size: 0.9rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 8px;
            border: 1px solid var(--glass-border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .tooltip:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: var(--dark);
            margin-bottom: 2px;
            z-index: 1000;
        }

        /* Accessibility */
        @media (prefers-reduced-transparency: reduce) {

            .hero-panel,
            .login-panel,
            .input-wrapper {
                backdrop-filter: none;
                background: var(--dark);
            }
        }

        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {

            .login-btn:hover,
            .social-btn:hover,
            .register-btn:hover,
            .feature-item:hover,
            .stat-card:hover {
                transform: none;
            }

            .input-wrapper:focus-within {
                transform: none;
            }
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
            <?php if (isset($_GET['registered'])): ?>
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