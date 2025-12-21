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