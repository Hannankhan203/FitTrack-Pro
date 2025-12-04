<?php 
// Set timezone to Pakistan/Islamabad
date_default_timezone_set('Asia/Karachi');

require '../includes/functions.php';
require_login(); 
?>
<?php
$user_id = get_user_id();

// Handle photo deletion
if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = "../uploads/" . $filename;
    
    // Check if the file exists and belongs to the current user
    if (file_exists($filepath) && strpos($filename, "user_" . get_user_id() . "_") === 0) {
        // Delete the file from server
        unlink($filepath);
        
        // Redirect to refresh the page
        header("Location: photos.php?deleted=1");
        exit();
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];

    // Check for errors
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = "user_" . get_user_id() . "_" . time() . "." . $ext;
        $uploadDir = "../uploads/";

        // Create uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Move uploaded file
        move_uploaded_file($file['tmp_name'], $uploadDir . $name);

        // Refresh the page to show the new photo
        echo '<script>window.location.href = "photos.php";</script>';
        exit();
    }
}

// Get user's photos
$photos = glob("../uploads/user_" . get_user_id() . "_*");
rsort($photos);
$photoCount = count($photos);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Progress Photos - FitTrack Pro</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <!-- Lightbox2 for image gallery -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <!-- SweetAlert2 for confirmation dialogs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

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

        /* Upload Card */
        .upload-card {
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

        .upload-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin: 0 auto 1.5rem;
        }

        /* Form Elements */
        .form-label {
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: white;
            display: block;
            font-size: 1.1rem;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 60px;
            color: rgba(255, 255, 255, 0.7);
        }

        .file-input-label:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            color: white;
        }

        .file-name {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .file-name i {
            margin-right: 0.5rem;
            color: var(--primary);
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

        /* Gallery Section */
        .gallery-section {
            background: linear-gradient(145deg, rgba(157, 78, 221, 0.05), rgba(157, 78, 221, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(157, 78, 221, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(157, 78, 221, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--accent);
        }

        .photo-count {
            background: var(--gradient-secondary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-left: 10px;
        }

        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .gallery-grid.list-view {
            grid-template-columns: 1fr;
        }

        .gallery-grid.list-view .photo-card {
            display: flex;
            flex-direction: row;
            height: 200px;
        }

        .gallery-grid.list-view .photo-img-container {
            width: 200px;
            height: 200px;
            flex-shrink: 0;
        }

        .gallery-grid.list-view .photo-overlay {
            position: relative;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.7), transparent);
            opacity: 1;
            flex-grow: 1;
            justify-content: center;
            padding: 1.5rem;
        }

        .gallery-grid.list-view .photo-date {
            color: white;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .gallery-grid.list-view .photo-actions {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
        }

        /* Photo Card */
        .photo-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .photo-card:hover {
            transform: translateY(-5px);
            background: rgba(0, 212, 255, 0.08);
            box-shadow: 0 15px 40px rgba(0, 212, 255, 0.15);
        }

        .photo-img-container {
            width: 100%;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.02);
        }

        .photo-img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .photo-card:hover .photo-img-container img {
            transform: scale(1.05);
        }

        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .photo-card:hover .photo-overlay {
            opacity: 1;
        }

        .photo-date {
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .photo-actions {
            display: flex;
            gap: 0.5rem;
        }

        .photo-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 8px;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex: 1;
        }

        .photo-btn:hover {
            background: var(--primary);
            transform: scale(1.05);
        }

        .photo-btn-delete {
            background: rgba(255, 45, 117, 0.8);
            border: 1px solid rgba(255, 45, 117, 0.3);
        }

        .photo-btn-delete:hover {
            background: var(--secondary);
        }

        /* Comparison Section */
        .comparison-section {
            background: linear-gradient(145deg, rgba(255, 45, 117, 0.05), rgba(255, 45, 117, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 45, 117, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(255, 45, 117, 0.1);
            color: white;
        }

        .comparison-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .comparison-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffffff 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .comparison-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }

        .comparison-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            position: relative;
        }

        .comparison-label {
            text-align: center;
            margin-bottom: 1rem;
            font-weight: 600;
            font-size: 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem;
            border-radius: 8px;
        }

        .comparison-image {
            width: 100%;
            height: 350px;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
            background-color: rgba(255, 255, 255, 0.02);
            padding: 15px;
        }

        .comparison-image:hover {
            transform: scale(1.02);
        }

        .comparison-vs {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--gradient-secondary);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            z-index: 2;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
        }

        .empty-state-icon {
            font-size: 4rem;
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

        .text-muted{
            color: var(--light) !important;
        }

        /* Tips Section */
        .tips-section {
            background: linear-gradient(145deg, rgba(0, 212, 255, 0.05), rgba(0, 212, 255, 0.02));
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 28px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.1);
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

        .alert-danger {
            background: rgba(255, 45, 117, 0.1);
            border-color: rgba(255, 45, 117, 0.2);
            color: var(--secondary);
        }

        .alert i {
            margin-right: 0.5rem;
        }

        .btn-close {
            filter: invert(1) brightness(2);
            opacity: 0.7;
        }

        .btn-close:hover {
            opacity: 1;
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

        /* Progress Timeline */
        .progress-timeline {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .timeline-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 1rem;
        }

        .timeline-dot {
            width: 12px;
            height: 12px;
            background: white;
            border-radius: 50%;
            margin-bottom: 0.5rem;
        }

        .timeline-label {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        /* Upload Progress */
        .upload-progress {
            display: none;
            margin-top: 1rem;
        }

        .progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient-success);
            border-radius: 4px;
            width: 0%;
            transition: width 0.3s;
        }

        .upload-status {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
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

            .upload-card,
            .gallery-section,
            .comparison-section,
            .tips-section {
                padding: 1.5rem;
                border-radius: 24px;
                margin-bottom: 1.5rem;
            }

            .upload-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }

            .comparison-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .comparison-vs {
                position: relative;
                top: 0;
                left: 0;
                transform: none;
                margin: 1rem auto;
            }

            .gallery-grid.list-view .photo-card {
                flex-direction: column;
                height: auto;
            }

            .gallery-grid.list-view .photo-img-container {
                width: 100%;
                height: 250px;
            }

            .gallery-grid.list-view .photo-overlay {
                background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
                position: absolute;
                justify-content: flex-end;
                padding: 1rem;
            }

            .gallery-grid.list-view .photo-actions {
                position: static;
                margin-top: 0.5rem;
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

            .upload-card,
            .gallery-section,
            .comparison-section,
            .tips-section {
                padding: 1.25rem;
                margin-bottom: 1.25rem;
            }

            .upload-icon {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }

            .section-title {
                font-size: 1.3rem;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .photo-img-container {
                height: 200px;
            }

            .comparison-image {
                height: 250px;
            }

            .file-input-label {
                padding: 0.75rem;
                min-height: 50px;
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

            .photo-count {
                background: var(--gradient-secondary);
                color: white;
                padding: 0.2rem 0.6rem;
                border-radius: 10px;
                font-size: 0.7rem;
                font-weight: 600;
                margin-left: 10px;
            }

            .section-header {
                justify-content: space-between;
                flex-direction: column;
                align-items: center;
                margin-bottom: 2rem;
                padding-bottom: 1rem;
                border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            }
        }

        /* Very small screens (under 400px) */
        @media (max-width: 399.98px) {
            .page-header h1 {
                font-size: 1.3rem;
            }

            .upload-card h4 {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 1rem;
                font-weight: 800;
                color: white;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .photo-img-container {
                height: 180px;
            }

            .comparison-image {
                height: 200px;
            }

            .file-input-label {
                font-size: 0.9rem;
            }

            .btn {
                padding: 0.6rem 1rem;
                border-radius: 8px;
                font-size: 0.7rem;
                gap: 4px;
            }
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
                        <a class="nav-link" href="../workouts/log.php">
                            <i class="fas fa-dumbbell"></i> Workouts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../meals/planner.php">
                            <i class="fas fa-utensils"></i> Meals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="charts.php">
                            <i class="fas fa-chart-line"></i> Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="photos.php">
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
        <!-- Success message -->
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Photo deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-camera me-2"></i>Progress Photos</h1>
            <p>Track your transformation journey visually. See how far you've come!</p>
        </div>

        <!-- Upload Form -->
        <div class="upload-card">
            <div class="upload-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <h4 class="text-center mb-3">Upload Progress Photo</h4>
            <p class="text-center text-muted mb-4">Upload photos regularly to track your fitness journey visually</p>

            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="file-input-wrapper mb-3">
                    <label class="file-input-label">
                        <div class="text-center">
                            <i class="fas fa-image me-2"></i>
                            <span id="fileName">Choose a photo (JPG, PNG, max 5MB)</span>
                        </div>
                        <input type="file" name="photo" accept="image/*" required id="photoInput" onchange="updateFileName()">
                    </label>
                </div>

                <!-- File name display area -->
                <div class="file-name" id="selectedFileName">
                    <i class="fas fa-info-circle"></i>
                    <span>No file selected</span>
                </div>

                <div class="upload-progress" id="uploadProgress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <div class="upload-status" id="uploadStatus">Uploading...</div>
                </div>

                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        <i class="fas fa-upload me-2"></i>Upload Photo
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Photos Gallery -->
        <div class="gallery-section">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-images"></i>
                    Recent Photos
                    <?php if ($photoCount > 0): ?>
                        <span class="photo-count"><?= $photoCount ?> photos</span>
                    <?php endif; ?>
                </div>
                <div>
                    <button class="btn btn-secondary" id="toggleViewBtn" onclick="toggleGalleryView()">
                        <i class="fas fa-th-large me-1"></i> Grid
                    </button>
                </div>
            </div>

            <?php if ($photoCount > 0): ?>
                <div class="gallery-grid" id="photoGallery">
                    <?php foreach (array_slice($photos, 0, 6) as $p):
                        $fileDate = date('M j, Y', filemtime($p));
                        $fileTime = date('g:i A', filemtime($p));
                        $filename = basename($p);
                        $imagePath = "../uploads/" . $filename;
                    ?>
                        <div class="photo-card">
                            <a href="<?= $imagePath ?>" data-lightbox="progress-gallery" data-title="Progress Photo - <?= $fileDate ?>">
                                <div class="photo-img-container">
                                    <img src="<?= $imagePath ?>" alt="Progress photo from <?= $fileDate ?>">
                                </div>
                            </a>
                            <div class="photo-overlay">
                                <div class="photo-date">
                                    <i class="far fa-calendar me-1"></i><?= $fileDate ?>
                                    <small class="d-block"><?= $fileTime ?></small>
                                </div>
                                <div class="photo-actions">
                                    <a href="<?= $imagePath ?>" class="photo-btn" data-lightbox="progress-gallery" data-title="Progress Photo - <?= $fileDate ?>">
                                        <i class="fas fa-expand"></i>
                                    </a>
                                    <button class="photo-btn photo-btn-delete" onclick="deletePhoto('<?= $filename ?>', '<?= $fileDate ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($photoCount > 6): ?>
                    <div class="text-center mt-4">
                        <button class="btn btn-primary" onclick="loadMorePhotos()">
                            <i class="fas fa-plus me-2"></i>Load More Photos
                        </button>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-images empty-state-icon"></i>
                    <h4>No Photos Yet</h4>
                    <p class="mb-4">Upload your first progress photo to start tracking your visual transformation!</p>
                    <div class="d-flex justify-content-center gap-2">
                        <div class="text-center">
                            <div class="mb-2">
                                <i class="fas fa-camera text-primary fs-3"></i>
                            </div>
                            <small>Take consistent photos</small>
                        </div>
                        <div class="text-center">
                            <div class="mb-2">
                                <i class="fas fa-calendar text-primary fs-3"></i>
                            </div>
                            <small>Track weekly progress</small>
                        </div>
                        <div class="text-center">
                            <div class="mb-2">
                                <i class="fas fa-chart-line text-primary fs-3"></i>
                            </div>
                            <small>See visual changes</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Before & After Comparison -->
        <?php if ($photoCount >= 2):
            $oldestPhoto = $photos[count($photos) - 1];
            $newestPhoto = $photos[0];
            $oldestDate = date('M j, Y', filemtime($oldestPhoto));
            $newestDate = date('M j, Y', filemtime($newestPhoto));
            $oldestFilename = basename($oldestPhoto);
            $newestFilename = basename($newestPhoto);
            $oldestImagePath = "../uploads/" . $oldestFilename;
            $newestImagePath = "../uploads/" . $newestFilename;
        ?>
            <div class="comparison-section">
                <div class="comparison-header">
                    <h3 class="comparison-title">Your Transformation Journey</h3>
                    <p class="comparison-subtitle">See how far you've come from your first photo</p>
                </div>

                <div class="comparison-container">
                    <div>
                        <div class="comparison-label">Before</div>
                        <a href="<?= $oldestImagePath ?>" data-lightbox="comparison" data-title="Before - <?= $oldestDate ?>">
                            <div class="photo-img-container" style="height: 350px;">
                                <img src="<?= $oldestImagePath ?>" alt="Before photo from <?= $oldestDate ?>">
                            </div>
                        </a>
                        <div class="text-center mt-2">
                            <small><i class="far fa-calendar me-1"></i><?= $oldestDate ?></small>
                        </div>
                    </div>

                    <div class="comparison-vs">VS</div>

                    <div>
                        <div class="comparison-label">After</div>
                        <a href="<?= $newestImagePath ?>" data-lightbox="comparison" data-title="After - <?= $newestDate ?>">
                            <div class="photo-img-container" style="height: 350px;">
                                <img src="<?= $newestImagePath ?>" alt="After photo from <?= $newestDate ?>">
                            </div>
                        </a>
                        <div class="text-center mt-2">
                            <small><i class="far fa-calendar me-1"></i><?= $newestDate ?></small>
                        </div>
                    </div>
                </div>

                <?php if ($photoCount > 2): ?>
                    <div class="progress-timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-label">Start</div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-label">Progress</div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-label">Current</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Tips Section -->
        <div class="tips-section">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-lightbulb"></i>
                    Photo Tips for Best Results
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-camera text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Consistent Lighting</h6>
                            <p class="small text-muted mb-0">Take photos in the same lighting conditions each time for accurate comparison.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-ruler-combined text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Same Pose & Angle</h6>
                            <p class="small text-muted mb-0">Maintain the same pose, distance, and camera angle for consistent results.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-calendar-alt text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Weekly Progress</h6>
                            <p class="small text-muted mb-0">Take photos weekly to track gradual changes that are hard to notice day-to-day.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div class="mobile-nav">
        <a href="../dashboard.php" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="../workouts/" class="mobile-nav-item">
            <i class="fas fa-dumbbell"></i>
            <span>Workouts</span>
        </a>
        <a href="../meals/" class="mobile-nav-item">
            <i class="fas fa-utensils"></i>
            <span>Meals</span>
        </a>
        <a href="charts.php" class="mobile-nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Progress</span>
        </a>
        <a href="photos.php" class="mobile-nav-item active">
            <i class="fas fa-camera"></i>
            <span>Photos</span>
        </a>
        <a href="../profile.php" class="mobile-nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>

        <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Use a simpler lightbox library that's more compatible -->
    <script src="https://cdn.jsdelivr.net/npm/basiclightbox@5.0.4/dist/basicLightbox.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basiclightbox@5.0.4/dist/basicLightbox.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <script>
        // Wait for the page to fully load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize lightbox functionality
            function initLightbox() {
                const lightboxLinks = document.querySelectorAll('a[data-lightbox]');
                
                lightboxLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        const imgSrc = this.getAttribute('href');
                        const imgTitle = this.getAttribute('data-title') || 'Progress Photo';
                        
                        // Create lightbox instance
                        const instance = basicLightbox.create(`
                            <div class="lightbox-container">
                                <img src="${imgSrc}" alt="${imgTitle}" class="lightbox-image">
                                <div class="lightbox-caption">${imgTitle}</div>
                            </div>
                        `, {
                            className: 'custom-lightbox',
                            onShow: () => {
                                document.body.style.overflow = 'hidden';
                            },
                            onClose: () => {
                                document.body.style.overflow = 'auto';
                            }
                        });
                        
                        instance.show();
                    });
                });
            }

            // Call lightbox initialization
            initLightbox();

            // Simple file name display
            function updateFileName() {
                const fileInput = document.getElementById('photoInput');
                const fileNameElement = document.getElementById('fileName');
                const selectedFileNameElement = document.getElementById('selectedFileName');

                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    const fileName = file.name;
                    const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB

                    // Update button text
                    fileNameElement.textContent = fileName;

                    // Update file info display
                    selectedFileNameElement.innerHTML = `
                        <i class="fas fa-file-image text-primary"></i>
                        <span><strong>Selected file:</strong> ${fileName} (${fileSize} MB)</span>
                    `;
                } else {
                    fileNameElement.textContent = 'Choose a photo (JPG, PNG, max 5MB)';
                    selectedFileNameElement.innerHTML = `
                        <i class="fas fa-info-circle"></i>
                        <span>No file selected</span>
                    `;
                }
            }

            // Attach event listener directly
            document.getElementById('photoInput').addEventListener('change', updateFileName);

            // Also update on page load
            updateFileName();

            // Form submission with progress simulation
            document.getElementById('uploadForm').addEventListener('submit', function(e) {
                const fileInput = document.getElementById('photoInput');
                const uploadBtn = document.getElementById('uploadBtn');
                const progressBar = document.getElementById('uploadProgress');
                const progressFill = document.getElementById('progressFill');
                const uploadStatus = document.getElementById('uploadStatus');

                if (!fileInput.files[0]) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'No file selected',
                        text: 'Please select a photo to upload.',
                        confirmButtonColor: '#00d4ff'
                    });
                    return;
                }

                // Validate file size (5MB max)
                const maxSize = 5 * 1024 * 1024;
                if (fileInput.files[0].size > maxSize) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'File too large',
                        text: 'File size exceeds 5MB limit. Please choose a smaller image.',
                        confirmButtonColor: '#ff2d75'
                    });
                    return;
                }

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(fileInput.files[0].type)) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid file type',
                        text: 'Please select a valid image file (JPG, PNG, or GIF).',
                        confirmButtonColor: '#ff2d75'
                    });
                    return;
                }

                // Show selected file name in the progress status
                const fileName = fileInput.files[0].name;
                uploadStatus.textContent = `Uploading: ${fileName}`;

                // Show progress bar
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
                progressBar.style.display = 'block';

                // Simulate upload progress
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 10;
                    progressFill.style.width = progress + '%';

                    if (progress >= 90) {
                        clearInterval(interval);
                        uploadStatus.textContent = `Processing: ${fileName}`;
                    }
                }, 200);

                // Allow form to submit normally
                return true;
            });

            // Toggle gallery view (grid/list)
            function toggleGalleryView() {
                const gallery = document.getElementById('photoGallery');
                const toggleBtn = document.getElementById('toggleViewBtn');
                
                if (gallery.classList.contains('list-view')) {
                    // Switch to grid view
                    gallery.classList.remove('list-view');
                    toggleBtn.innerHTML = '<i class="fas fa-th-large me-1"></i> Grid';
                } else {
                    // Switch to list view
                    gallery.classList.add('list-view');
                    toggleBtn.innerHTML = '<i class="fas fa-list me-1"></i> List';
                }
            }

            // Make toggleGalleryView globally accessible
            window.toggleGalleryView = toggleGalleryView;

            // Load more photos functionality
            let loadedPhotos = 6;

            function loadMorePhotos() {
                const loadBtn = document.querySelector('.btn-primary');
                loadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                loadBtn.disabled = true;

                setTimeout(() => {
                    alert('In a real application, this would load more photos from the server.');
                    loadBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Load More Photos';
                    loadBtn.disabled = false;
                }, 1000);
            }

            // Make loadMorePhotos globally accessible
            window.loadMorePhotos = loadMorePhotos;

            // Delete photo function with confirmation
            function deletePhoto(filename, date) {
                Swal.fire({
                    title: 'Delete Photo?',
                    text: `Are you sure you want to delete the photo from ${date}? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ff2d75',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect to delete the photo
                        window.location.href = `photos.php?delete=${filename}`;
                    }
                });
            }

            // Make deletePhoto globally accessible
            window.deletePhoto = deletePhoto;

            // Add some sample interaction for the timeline
            const timelineDots = document.querySelectorAll('.timeline-dot');
            timelineDots.forEach((dot, index) => {
                dot.addEventListener('click', function() {
                    alert('Viewing photos from ' + (index === 0 ? 'the beginning' : index === 1 ? 'mid-journey' : 'recent') + ' of your transformation.');
                });
            });

            // Mobile navigation handling (same as charts.php)
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

            // Make updateFileName globally accessible
            window.updateFileName = updateFileName;

            // Add custom styles for the lightbox
            const style = document.createElement('style');
            style.textContent = `
                .custom-lightbox {
                    background: rgba(0, 0, 0, 0.9) !important;
                    backdrop-filter: blur(20px) !important;
                    -webkit-backdrop-filter: blur(20px) !important;
                }
                
                .lightbox-container {
                    position: relative;
                    max-width: 90vw;
                    max-height: 90vh;
                    margin: auto;
                }
                
                .lightbox-image {
                    max-width: 100%;
                    max-height: 80vh;
                    display: block;
                    margin: 0 auto;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                }
                
                .lightbox-caption {
                    text-align: center;
                    color: white;
                    padding: 1rem;
                    font-size: 1.1rem;
                    background: rgba(0, 0, 0, 0.7);
                    border-radius: 0 0 12px 12px;
                    margin-top: -5px;
                }
                
                .basicLightbox__placeholder {
                    max-width: 95vw !important;
                    max-height: 95vh !important;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>