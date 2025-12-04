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
    } catch (Exception $e) {
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
            --gradient-dark: linear-gradient(135deg, #0a0f23 0%, #1a1f3b 100%);
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
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 10% 90%, rgba(157, 78, 221, 0.2) 0%, transparent 40%),
                radial-gradient(circle at 90% 10%, rgba(0, 212, 255, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(255, 45, 117, 0.1) 0%, transparent 60%);
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.02"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1" fill="white"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            z-index: -1;
        }

        /* Main Container */
        .register-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Header */
        .register-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .logo {
            font-size: 4rem;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 15px;
            letter-spacing: -2px;
            position: relative;
            display: inline-block;
            filter: var(--glow);
        }

        .logo::after {
            content: 'REGISTER';
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.9rem;
            letter-spacing: 4px;
            color: var(--gray);
            font-weight: 300;
            text-transform: uppercase;
        }

        .tagline {
            font-size: 1.2rem;
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Main Grid */
        .register-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 50px;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        /* Left Panel - Hero */
        .hero-panel {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 40px;
            padding: 50px;
            border: 1px solid var(--glass-border);
            box-shadow:
                0 25px 80px rgba(0, 0, 0, 0.4),
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
            background: linear-gradient(90deg, transparent, var(--secondary), transparent);
        }

        .welcome-title {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1.2;
        }

        .welcome-description {
            font-size: 1.1rem;
            color: #cbd5e1;
            line-height: 1.7;
            margin-bottom: 40px;
        }

        /* Benefits Grid */
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .benefit-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 25px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .benefit-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .benefit-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: var(--neon-shadow);
        }

        .benefit-card:hover::before {
            opacity: 0.05;
        }

        .benefit-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .benefit-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .benefit-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: white;
            position: relative;
            z-index: 1;
        }

        .benefit-description {
            font-size: 0.9rem;
            color: #94a3b8;
            line-height: 1.5;
            position: relative;
            z-index: 1;
        }

        /* Testimonial */
        .testimonial {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 25px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 40px;
            position: relative;
        }

        .testimonial::before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 4rem;
            color: var(--primary);
            opacity: 0.3;
            font-family: Georgia, serif;
        }

        .testimonial-content {
            padding-left: 20px;
            position: relative;
            z-index: 1;
        }

        .testimonial-text {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #e2e8f0;
            margin-bottom: 20px;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }

        .author-info h4 {
            font-size: 1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
        }

        .author-info p {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Right Panel - Form */
        .form-panel {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 40px;
            padding: 50px;
            border: 1px solid var(--glass-border);
            box-shadow:
                0 25px 80px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .form-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }

        .form-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #fda4af 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .form-subtitle {
            color: var(--gray);
            margin-bottom: 40px;
            font-size: 1rem;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 30px;
        }

        .input-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .input-label i {
            color: var(--primary);
            font-size: 1rem;
        }

        .input-container {
            position: relative;
        }

        .input-wrapper {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
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
            padding: 20px 20px 20px 60px;
            background: transparent;
            border: none;
            color: var(--light);
            font-size: 1rem;
            outline: none;
            font-weight: 500;
        }

        .form-input::placeholder {
            color: #64748b;
            font-weight: 400;
        }

        .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .toggle-password:hover {
            color: var(--primary);
            background: rgba(255, 255, 255, 0.05);
        }

        /* Password Strength */
        .password-strength {
            margin-top: 15px;
        }

        .strength-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .strength-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 4px;
            transition: all 0.3s ease;
            background: var(--error);
        }

        .strength-fill.weak {
            width: 25%;
            background: var(--error);
        }

        .strength-fill.fair {
            width: 50%;
            background: var(--warning);
        }

        .strength-fill.good {
            width: 75%;
            background: #ff9e00;
        }

        .strength-fill.strong {
            width: 100%;
            background: var(--success);
        }

        .requirements-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            padding: 8px 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            transition: all 0.3s ease;
        }

        .requirement i {
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .requirement.valid {
            color: var(--success);
            background: rgba(0, 230, 118, 0.1);
        }

        .requirement.valid i {
            color: var(--success);
        }

        .requirement.invalid {
            color: var(--gray);
        }

        /* Password Match */
        .password-match {
            margin-top: 15px;
            font-size: 0.9rem;
            padding: 10px 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .password-match.show {
            opacity: 1;
            transform: translateY(0);
        }

        .password-match.valid {
            background: rgba(0, 230, 118, 0.1);
            color: var(--success);
        }

        .password-match.invalid {
            background: rgba(255, 71, 87, 0.1);
            color: var(--error);
        }

        /* Terms Checkbox */
        .terms-container {
            margin: 40px 0;
            padding: 25px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            cursor: pointer;
        }

        .terms-checkbox input[type="checkbox"] {
            display: none;
        }

        .checkbox-custom {
            width: 24px;
            height: 24px;
            border: 2px solid var(--gray);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s ease;
            margin-top: 2px;
        }

        .checkbox-custom i {
            color: white;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .terms-checkbox input[type="checkbox"]:checked+.checkbox-custom {
            background: var(--primary);
            border-color: var(--primary);
        }

        .terms-checkbox input[type="checkbox"]:checked+.checkbox-custom i {
            opacity: 1;
        }

        .terms-label {
            font-size: 0.95rem;
            line-height: 1.6;
            color: #cbd5e1;
        }

        .terms-label a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .terms-label a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 22px;
            background: var(--gradient);
            border: none;
            border-radius: 20px;
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 30px 0;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-5px);
            box-shadow:
                0 15px 40px rgba(0, 212, 255, 0.4),
                0 10px 25px rgba(157, 78, 221, 0.3);
        }

        .submit-btn i {
            font-size: 1.3rem;
        }

        .submit-btn.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Login Link */
        .login-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .login-text {
            color: var(--gray);
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .login-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px 40px;
            background: transparent;
            border: 2px solid var(--primary);
            border-radius: 20px;
            color: var(--primary);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: var(--primary);
            color: var(--dark);
            transform: translateY(-3px);
            box-shadow: var(--neon-shadow);
        }

        /* Footer */
        .register-footer {
            text-align: center;
            margin-top: 60px;
            padding: 30px;
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
            font-size: 0.95rem;
        }

        .footer-link:hover {
            color: var(--primary);
        }

        .copyright {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        /* Error Alert */
        .error-alert {
            background: linear-gradient(135deg, rgba(255, 71, 87, 0.1) 0%, rgba(255, 71, 87, 0.05) 100%);
            border: 1px solid rgba(255, 71, 87, 0.2);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-icon {
            width: 40px;
            height: 40px;
            background: var(--error);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .error-icon i {
            color: white;
            font-size: 1.2rem;
        }

        .error-message {
            color: #ff8e9e;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Floating Elements */
        .floating-element {
            position: fixed;
            z-index: -1;
            opacity: 0.05;
            filter: blur(60px);
            animation: float 15s ease-in-out infinite;
        }

        .floating-1 {
            top: 10%;
            left: 5%;
            width: 400px;
            height: 400px;
            background: var(--primary);
            border-radius: 50%;
        }

        .floating-2 {
            bottom: 10%;
            right: 5%;
            width: 500px;
            height: 500px;
            background: var(--secondary);
            border-radius: 50%;
        }

        .floating-3 {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 600px;
            background: var(--accent);
            border-radius: 50%;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(20px, -20px) rotate(120deg);
            }

            66% {
                transform: translate(-20px, 20px) rotate(240deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .register-grid {
                grid-template-columns: 1fr;
                max-width: 800px;
                gap: 40px;
            }

            .logo {
                font-size: 3.5rem;
            }

            .welcome-title {
                font-size: 2.5rem;
            }

            .form-title {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .register-container {
                padding: 15px;
            }

            .logo {
                font-size: 2.8rem;
            }

            .tagline {
                font-size: 1.1rem;
            }

            .hero-panel,
            .form-panel {
                padding: 30px;
                border-radius: 30px;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
            }

            .requirements-grid {
                grid-template-columns: 1fr;
            }

            .form-title {
                font-size: 1.8rem;
            }

            .footer-links {
                flex-direction: column;
                gap: 15px;
                align-items: center;
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 2.2rem;
            }

            .hero-panel,
            .form-panel {
                padding: 25px;
                border-radius: 25px;
            }

            .welcome-title {
                font-size: 1.7rem;
            }

            .form-title {
                font-size: 1.5rem;
            }

            .form-input {
                padding: 18px 18px 18px 50px;
            }

            .submit-btn {
                padding: 20px;
                font-size: 1rem;
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

            .input-wrapper:focus-within {
                outline: 2px solid var(--primary);
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

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
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

        ::-moz-selection {
            background: var(--primary);
            color: white;
        }

        /* Focus styles */
        :focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Loading state */
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Form validation states */
        .input-wrapper.valid {
            border-color: var(--success);
        }

        .input-wrapper.valid .input-icon {
            color: var(--success);
        }

        .input-wrapper.invalid {
            border-color: var(--error);
            animation: shake 0.5s ease-in-out;
        }

        .input-wrapper.invalid .input-icon {
            color: var(--error);
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

        /* Success animation */
        @keyframes success {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .success {
            animation: success 0.5s ease-out;
        }

        /* Tooltips */
        [data-tooltip] {
            position: relative;
        }

        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 8px;
            border: 1px solid var(--glass-border);
        }

        [data-tooltip]:hover::before {
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
            .form-panel {
                backdrop-filter: none;
                background: var(--dark);
            }
        }

        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {

            .submit-btn:hover,
            .benefit-card:hover,
            .login-btn:hover {
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
            <!-- Error message for registration -->
            <?php if (isset($error)): ?>
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