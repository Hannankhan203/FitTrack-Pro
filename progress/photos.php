<?php require '../includes/functions.php';
require_login();

// Handle photo deletion
if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = "../uploads/" . $filename;
    
    // Check if the file exists and belongs to the current user
    if (file_exists($filepath) && strpos($filename, "user_" . get_user_id() . "_") === 0) {
        // Delete the file from server
        unlink($filepath);
        
        // Delete from database if you have a photos table
        // Uncomment the following lines if you have a database table for photos
        
        // global $conn;
        // $stmt = $conn->prepare("DELETE FROM photos WHERE filename = ? AND user_id = ?");
        // $stmt->bind_param("si", $filename, get_user_id());
        // $stmt->execute();
        
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
        
        // Save to database if you have a photos table
        // Uncomment the following lines if you have a database table for photos
        
        // global $conn;
        // $stmt = $conn->prepare("INSERT INTO photos (user_id, filename, uploaded_at) VALUES (?, ?, NOW())");
        // $stmt->bind_param("is", get_user_id(), $name);
        // $stmt->execute();

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            --primary-color: #3a86ff;
            --primary-dark: #2667cc;
            --secondary-color: #ff006e;
            --accent-color: #8338ec;
            --success-color: #38b000;
            --warning-color: #ffbe0b;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gradient-primary: linear-gradient(135deg, #3a86ff, #8338ec);
            --gradient-secondary: linear-gradient(135deg, #ff006e, #fb5607);
            --gradient-success: linear-gradient(135deg, #38b000, #70e000);
            --gradient-warning: linear-gradient(135deg, #ffbe0b, #ff9100);
            --gradient-danger: linear-gradient(135deg, #dc3545, #c82333);
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

        .upload-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
            border: 3px dashed #e9ecef;
            transition: all 0.3s;
        }

        .upload-card:hover {
            border-color: var(--primary-color);
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
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            transition: all 0.3s;
            cursor: pointer;
            min-height: 60px;
        }

        .file-input-label:hover {
            background: #e9ecef;
            border-color: var(--primary-color);
        }

        .file-name {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #6c757d;
            text-align: center;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .file-name i {
            margin-right: 0.5rem;
            color: var(--primary-color);
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

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .gallery-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
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

        .photo-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            position: relative;
        }

        .photo-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .photo-img {
            width: 100%;
            height: 250px;
            display: block;
            background-color: #f8f9fa;
        }

        .photo-img-container {
            width: 100%;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f8f9fa;
        }

        .photo-img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
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
            border-radius: 6px;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex: 1;
        }

        .photo-btn:hover {
            background: var(--primary-color);
            transform: scale(1.05);
        }

        .photo-btn-delete {
            background: rgba(220, 53, 69, 0.8);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .photo-btn-delete:hover {
            background: var(--danger-color);
        }

        .comparison-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
        }

        .comparison-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .comparison-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .comparison-subtitle {
            opacity: 0.9;
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
            background-color: #f8f9fa;
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

        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            margin-bottom: 0.5rem;
            font-weight: 600;
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

            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
        }

        @media (max-width: 576px) {
            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .comparison-image {
                height: 250px;
            }
        }

        .upload-progress {
            display: none;
            margin-top: 1rem;
        }

        .progress-bar {
            height: 8px;
            background: #e9ecef;
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
            color: #6c757d;
            text-align: center;
        }
        
        .alert-success {
            background: var(--gradient-success);
            color: white;
            border: none;
            border-radius: var(--border-radius);
        }

        /* ============================================
   PREMIUM MOBILE RESPONSIVE DESIGN - PROGRESS PHOTOS
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
    
    /* Alert Messages */
    .alert {
        margin: 0 0.75rem 1rem 0.75rem !important;
        border-radius: 16px !important;
        padding: 1rem !important;
        font-size: 0.9rem;
    }
    
    .alert i {
        font-size: 1.1rem !important;
        margin-right: 0.5rem !important;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #38b000, #70e000) !important;
        color: white;
        border: none;
    }
    
    /* Upload Card - Mobile Optimized */
    .upload-card {
        margin: 0 0.75rem 1.5rem 0.75rem !important;
        padding: 1.5rem !important;
        border-radius: 20px !important;
    }
    
    .upload-icon {
        width: 70px !important;
        height: 70px !important;
        font-size: 1.8rem !important;
        margin-bottom: 1rem !important;
    }
    
    .upload-card h4 {
        font-size: 1.2rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .upload-card p.text-muted {
        font-size: 0.9rem !important;
        margin-bottom: 1.25rem !important;
        line-height: 1.5;
    }
    
    /* File Input - Mobile Optimized */
    .file-input-wrapper {
        margin-bottom: 1rem !important;
    }
    
    .file-input-label {
        padding: 1rem !important;
        min-height: 56px !important;
        border-radius: 14px !important;
        border-width: 2px !important;
    }
    
    .file-input-label .text-center {
        font-size: 0.9rem;
    }
    
    .file-input-label i {
        font-size: 1.1rem !important;
        margin-right: 0.5rem !important;
    }
    
    #fileName {
        font-size: 0.9rem !important;
    }
    
    /* Selected file name display */
    .file-name {
        margin-top: 0.75rem !important;
        padding: 0.75rem !important;
        font-size: 0.85rem !important;
        border-radius: 12px !important;
    }
    
    .file-name i {
        font-size: 0.9rem !important;
        margin-right: 0.5rem !important;
    }
    
    /* Upload Progress */
    .upload-progress {
        margin-top: 1rem !important;
    }
    
    .progress-bar {
        height: 6px !important;
        border-radius: 8px !important;
        margin-bottom: 0.375rem !important;
    }
    
    .upload-status {
        font-size: 0.8rem !important;
    }
    
    /* Upload Button */
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
    
    /* Gallery Section - Mobile Optimized */
    .gallery-section {
        margin: 0 0.75rem 1.5rem 0.75rem !important;
        padding: 1.5rem !important;
        border-radius: 20px !important;
    }
    
    .section-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 1rem;
        margin-bottom: 1.5rem !important;
        padding-bottom: 1rem !important;
    }
    
    .section-title {
        font-size: 1.2rem !important;
        margin-bottom: 0 !important;
    }
    
    .section-title i {
        font-size: 1.1rem !important;
        margin-right: 0.5rem !important;
    }
    
    .photo-count {
        font-size: 0.8rem !important;
        padding: 0.2rem 0.625rem !important;
        margin-left: 0.5rem !important;
    }
    
    /* Toggle View Button */
    .btn-secondary {
        padding: 0.625rem 1rem !important;
        font-size: 0.9rem !important;
        border-radius: 12px !important;
        min-height: 40px !important;
    }
    
    .btn-secondary i {
        font-size: 0.9rem !important;
        margin-right: 0.25rem !important;
    }
    
    /* Gallery Grid - Mobile Optimized */
    .gallery-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
    
    /* Photo Card - Mobile Optimized */
    .photo-card {
        border-radius: 16px !important;
        overflow: hidden;
    }
    
    .photo-img-container {
        height: 280px !important;
    }
    
    .photo-img-container img {
        max-height: 280px !important;
    }
    
    /* Photo Overlay - Always visible on mobile for better UX */
    .photo-overlay {
        opacity: 1 !important;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent 60%) !important;
    }
    
    .photo-date {
        font-size: 0.9rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .photo-date small {
        font-size: 0.8rem !important;
        opacity: 0.9;
    }
    
    .photo-actions {
        gap: 0.75rem !important;
    }
    
    .photo-btn {
        padding: 0.5rem !important;
        border-radius: 10px !important;
        font-size: 0.9rem !important;
        min-height: 40px !important;
        min-width: 40px !important;
    }
    
    .photo-btn i {
        font-size: 1rem !important;
    }
    
    /* Empty State - Mobile Optimized */
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
        margin-bottom: 1.5rem !important;
        max-width: 280px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Comparison Section - Mobile Optimized */
    .comparison-section {
        margin: 0 0.75rem 1.5rem 0.75rem !important;
        padding: 1.5rem !important;
        border-radius: 20px !important;
    }
    
    .comparison-header {
        margin-bottom: 1.5rem !important;
    }
    
    .comparison-title {
        font-size: 1.3rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .comparison-subtitle {
        font-size: 0.9rem !important;
        opacity: 0.8;
    }
    
    .comparison-container {
        grid-template-columns: 1fr !important;
        gap: 1.5rem !important;
    }
    
    .comparison-label {
        font-size: 1rem !important;
        margin-bottom: 0.75rem !important;
        padding: 0.5rem !important;
        border-radius: 10px !important;
    }
    
    .photo-img-container[style*="height: 350px"] {
        height: 250px !important;
    }
    
    .comparison-vs {
        position: relative !important;
        top: 0 !important;
        left: 0 !important;
        transform: none !important;
        margin: 0 auto 1rem auto !important;
        width: 50px !important;
        height: 50px !important;
        font-size: 1rem !important;
    }
    
    .comparison-container > div {
        text-align: center;
    }
    
    .comparison-container small {
        font-size: 0.85rem !important;
    }
    
    /* Progress Timeline - Mobile Optimized */
    .progress-timeline {
        margin-top: 1.5rem !important;
        padding: 0.75rem !important;
        border-radius: 12px !important;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-around;
    }
    
    .timeline-item {
        margin: 0 0.5rem !important;
    }
    
    .timeline-dot {
        width: 10px !important;
        height: 10px !important;
        margin-bottom: 0.25rem !important;
    }
    
    .timeline-label {
        font-size: 0.8rem !important;
    }
    
    /* Tips Section - Mobile Optimized */
    .gallery-section:last-child {
        margin-bottom: 2rem !important;
    }
    
    .row {
        margin: 0 !important;
    }
    
    .col-md-4.mb-3 {
        width: 100% !important;
        margin-bottom: 1.25rem !important;
    }
    
    .d-flex.align-items-start {
        gap: 0.75rem;
    }
    
    .d-flex.align-items-start .me-3 i {
        font-size: 1.5rem !important;
        margin-top: 0.125rem;
    }
    
    .d-flex.align-items-start h6 {
        font-size: 0.95rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .d-flex.align-items-start .small {
        font-size: 0.85rem !important;
        line-height: 1.4;
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
    
    .upload-card,
    .gallery-section,
    .comparison-section {
        animation: fadeInUp 0.5s ease-out;
    }
    
    /* Stagger animations */
    .upload-card { animation-delay: 0.1s; }
    .gallery-section { animation-delay: 0.2s; }
    .comparison-section { animation-delay: 0.3s; }
    
    /* Photo card entrance animation */
    @keyframes photoCardEntrance {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .photo-card {
        animation: photoCardEntrance 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Stagger photo animations */
    .photo-card:nth-child(1) { animation-delay: 0.1s; }
    .photo-card:nth-child(2) { animation-delay: 0.2s; }
    .photo-card:nth-child(3) { animation-delay: 0.3s; }
    .photo-card:nth-child(4) { animation-delay: 0.4s; }
    .photo-card:nth-child(5) { animation-delay: 0.5s; }
    .photo-card:nth-child(6) { animation-delay: 0.6s; }
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
    
    .alert {
        margin: 0 0.5rem 0.75rem 0.5rem !important;
        padding: 0.875rem !important;
        font-size: 0.85rem !important;
    }
    
    .upload-card,
    .gallery-section,
    .comparison-section {
        margin: 0 0.5rem 1rem 0.5rem !important;
        padding: 1.25rem !important;
    }
    
    .upload-icon {
        width: 60px !important;
        height: 60px !important;
        font-size: 1.5rem !important;
    }
    
    .upload-card h4 {
        font-size: 1.1rem !important;
    }
    
    .file-input-label {
        padding: 0.875rem !important;
        min-height: 52px !important;
    }
    
    #fileName {
        font-size: 0.85rem !important;
    }
    
    .btn-primary {
        padding: 0.75rem !important;
        min-height: 52px !important;
        font-size: 0.95rem !important;
    }
    
    .photo-img-container {
        height: 250px !important;
    }
    
    .photo-img-container img {
        max-height: 250px !important;
    }
    
    .photo-date {
        font-size: 0.85rem !important;
    }
    
    .photo-btn {
        min-height: 36px !important;
        min-width: 36px !important;
        padding: 0.375rem !important;
    }
    
    .photo-btn i {
        font-size: 0.9rem !important;
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
    
    .comparison-title {
        font-size: 1.2rem !important;
    }
    
    .photo-img-container[style*="height: 350px"] {
        height: 220px !important;
    }
    
    .btn-secondary {
        padding: 0.5rem 0.75rem !important;
        font-size: 0.85rem !important;
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
    
    .gallery-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    .photo-img-container {
        height: 300px !important;
    }
    
    .photo-img-container img {
        max-height: 300px !important;
    }
    
    .row .col-md-4.mb-3 {
        width: 33.333% !important;
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
    
    .upload-card,
    .gallery-section,
    .comparison-section {
        margin-bottom: 1rem !important;
        padding: 1rem !important;
    }
    
    .upload-icon {
        width: 50px !important;
        height: 50px !important;
        font-size: 1.2rem !important;
    }
    
    .photo-img-container {
        height: 200px !important;
    }
    
    .photo-img-container img {
        max-height: 200px !important;
    }
    
    .photo-img-container[style*="height: 350px"] {
        height: 180px !important;
    }
    
    .btn-primary {
        min-height: 52px !important;
        padding: 0.75rem !important;
    }
    
    .file-input-label {
        min-height: 48px !important;
        padding: 0.75rem !important;
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
    .photo-card:hover {
        transform: none !important;
    }
    
    .photo-card:active {
        transform: scale(0.98) !important;
    }
    
    .photo-btn:hover {
        transform: none !important;
    }
    
    .photo-btn:active {
        transform: scale(1.1) !important;
    }
    
    .btn-primary:hover,
    .btn-secondary:hover {
        transform: none !important;
    }
    
    .btn-primary:active,
    .btn-secondary:active {
        transform: scale(0.98) !important;
    }
    
    .file-input-label:hover {
        transform: none !important;
    }
    
    .file-input-label:active {
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
        
        .upload-card,
        .gallery-section {
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
        
        .file-input-label {
            background: #2d3748 !important;
            border-color: #374151 !important;
            color: #e2e8f0 !important;
        }
        
        .file-name {
            background: #2d3748 !important;
            color: #94a3b8 !important;
        }
        
        .file-name i.text-primary {
            color: #3a86ff !important;
        }
        
        .progress-bar {
            background: #374151 !important;
        }
        
        .photo-card {
            background: #2d3748 !important;
        }
        
        .photo-img-container {
            background-color: #1e293b !important;
        }
        
        .photo-overlay {
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent 60%) !important;
        }
        
        .photo-btn {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
        }
        
        .photo-btn-delete {
            background: rgba(239, 68, 68, 0.8) !important;
            border-color: rgba(239, 68, 68, 0.3) !important;
        }
        
        .photo-btn-delete:hover {
            background: rgba(239, 68, 68, 1) !important;
        }
        
        .comparison-section {
            background: linear-gradient(135deg, #4f46e5, #7c3aed) !important;
        }
        
        .comparison-label {
            background: rgba(255, 255, 255, 0.15) !important;
        }
        
        .timeline-dot {
            background: rgba(255, 255, 255, 0.9) !important;
        }
        
        .text-muted {
            color: #94a3b8 !important;
        }
        
        .btn-secondary {
            background: #475569 !important;
            color: #e2e8f0 !important;
            border: none !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #3730a3) !important;
        }
        
        .empty-state i {
            color: #374151 !important;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #059669, #065f46) !important;
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

/* Section animations */
.upload-card {
    animation: slideInFromLeft 0.5s ease-out;
}

.gallery-section {
    animation: slideInFromRight 0.5s ease-out;
}

/* Upload progress animation */
@keyframes uploadProgress {
    from {
        width: 0%;
    }
    to {
        width: 100%;
    }
}

.progress-fill {
    animation: uploadProgress 2s ease-in-out infinite alternate;
}

/* Photo hover effects */
@media (hover: hover) and (pointer: fine) {
    .photo-card:hover {
        transform: translateY(-8px);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
}

/* Lightbox adjustments for mobile */
@media (max-width: 767.98px) {
    .lb-outerContainer {
        border-radius: 16px !important;
        overflow: hidden;
    }
    
    .lb-container {
        padding: 0 !important;
    }
    
    .lb-nav a.lb-prev,
    .lb-nav a.lb-next {
        width: 44px !important;
        height: 44px !important;
    }
    
    .lb-data .lb-close {
        width: 44px !important;
        height: 44px !important;
        top: 10px !important;
        right: 10px !important;
    }
}

/* Upload button loading animation */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.btn-primary i.fa-spinner {
    animation: spin 1s linear infinite;
}

/* Form focus states */
.file-input-label:focus-within,
.btn-primary:focus,
.btn-secondary:focus {
    box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2) !important;
}

/* Empty state image tips */
@media (max-width: 767.98px) {
    .empty-state .d-flex.justify-content-center.gap-2 {
        gap: 1rem !important;
        flex-wrap: wrap;
    }
    
    .empty-state .text-center {
        flex: 1;
        min-width: 100px;
    }
    
    .empty-state .text-center i {
        font-size: 1.5rem !important;
    }
    
    .empty-state .text-center small {
        font-size: 0.8rem !important;
    }
}

/* Responsive typography */
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
    
    .mt-3 {
        margin-top: 1rem !important;
    }
    
    .mt-4 {
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
    
    .photo-btn {
        min-height: 44px !important;
        min-width: 44px !important;
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
                    <i class="fas fa-images"></i>
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
        <div class="gallery-section">
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
    <!-- Lightbox2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <script>
        // Initialize Lightbox
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': 'Photo %1 of %2',
            'fadeDuration': 300,
            'imageFadeDuration': 300
        });

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

        // Also update on page load in case there's a persisted file (though unlikely)
        document.addEventListener('DOMContentLoaded', updateFileName);

        // Form submission with progress simulation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('photoInput');
            const uploadBtn = document.getElementById('uploadBtn');
            const progressBar = document.getElementById('uploadProgress');
            const progressFill = document.getElementById('progressFill');
            const uploadStatus = document.getElementById('uploadStatus');

            if (!fileInput.files[0]) {
                e.preventDefault();
                alert('Please select a photo to upload.');
                return;
            }

            // Validate file size (5MB max)
            const maxSize = 5 * 1024 * 1024;
            if (fileInput.files[0].size > maxSize) {
                e.preventDefault();
                alert('File size exceeds 5MB limit. Please choose a smaller image.');
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(fileInput.files[0].type)) {
                e.preventDefault();
                alert('Please select a valid image file (JPG, PNG, or GIF).');
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

        // Toggle gallery view (grid/list) - FIXED VERSION
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

        // Delete photo function with confirmation
        function deletePhoto(filename, date) {
            Swal.fire({
                title: 'Delete Photo?',
                text: `Are you sure you want to delete the photo from ${date}? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
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

        // Add some sample interaction for the timeline
        document.addEventListener('DOMContentLoaded', function() {
            const timelineDots = document.querySelectorAll('.timeline-dot');
            timelineDots.forEach((dot, index) => {
                dot.addEventListener('click', function() {
                    alert('Viewing photos from ' + (index === 0 ? 'the beginning' : index === 1 ? 'mid-journey' : 'recent') + ' of your transformation.');
                });
            });
        });
    </script>
</body>
</html>