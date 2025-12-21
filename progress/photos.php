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
    $fileType = $_GET['type'] ?? 'uploads'; // Get storage type from URL

    // Determine which directory to delete from
    if ($fileType === 'private') {
        $filepath = "../private_uploads/" . $filename;
    } else {
        $filepath = "../uploads/" . $filename;
    }

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

    // Check for storage type
    $storageType = $_POST['storage_type'] ?? 'uploads';

    // Validate storage type
    if (!in_array($storageType, ['uploads', 'private'])) {
        $storageType = 'uploads'; // Default to uploads if invalid
    }

    // Check for errors
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = "user_" . get_user_id() . "_" . time() . "." . $ext;

        // Determine upload directory based on storage type
        if ($storageType === 'private') {
            $uploadDir = "../private_uploads/";
        } else {
            $uploadDir = "../uploads/";
        }

        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $name)) {
            // Refresh the page to show the new photo
            echo '<script>window.location.href = "photos.php";</script>';
            exit();
        }
    }
}

// Get user's photos from both directories
$publicPhotos = glob("../uploads/user_" . get_user_id() . "_*");
$privatePhotos = glob("../private_uploads/user_" . get_user_id() . "_*");

// Combine all photos with their storage type
$photos = array();
foreach ($publicPhotos as $photo) {
    $photos[] = array(
        'path' => $photo,
        'type' => 'uploads'
    );
}
foreach ($privatePhotos as $photo) {
    $photos[] = array(
        'path' => $photo,
        'type' => 'private'
    );
}

// Sort by modification time (newest first)
usort($photos, function ($a, $b) {
    return filemtime($b['path']) - filemtime($a['path']);
});

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
                <!-- Storage Type Selection -->
                <div class="storage-selection">
                    <h6 class="form-label text-center mb-3">Where would you like to save this photo?</h6>
                    <div class="storage-options">
                        <div class="storage-option">
                            <input type="radio" name="storage_type" id="storage_public" value="uploads" checked>
                            <label for="storage_public">
                                <div class="storage-icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <div class="storage-title">Public Uploads</div>
                                <div class="storage-desc">
                                    Photos saved here can be viewed by others in your community
                                </div>
                            </label>
                        </div>

                        <div class="storage-option">
                            <input type="radio" name="storage_type" id="storage_private" value="private">
                            <label for="storage_private">
                                <div class="storage-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <div class="storage-title">Private Uploads</div>
                                <div class="storage-desc">
                                    Photos saved here are only visible to you (more secure)
                                </div>
                            </label>
                        </div>
                    </div>
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
                    <?php foreach (array_slice($photos, 0, 6) as $photo):
                        $photoPath = $photo['path'];
                        $storageType = $photo['type'];
                        $fileDate = date('M j, Y', filemtime($photoPath));
                        $fileTime = date('g:i A', filemtime($photoPath));
                        $filename = basename($photoPath);
                        
                        // Determine the correct image path based on storage type
                        if ($storageType === 'private') {
                            $imagePath = "../private_uploads/" . $filename;
                        } else {
                            $imagePath = "../uploads/" . $filename;
                        }
                    ?>
                        <div class="photo-card">
                            <span class="photo-badge <?= $storageType ?>">
                                <i class="fas fa-<?= $storageType === 'private' ? 'lock' : 'globe' ?> me-1"></i>
                                <?= $storageType === 'private' ? 'Private' : 'Public' ?>
                            </span>
                            <a href="<?= $imagePath ?>" data-lightbox="progress-gallery" data-title="Progress Photo - <?= $fileDate ?> (<?= $storageType === 'private' ? 'Private' : 'Public' ?>)">
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
                                    <a href="<?= $imagePath ?>" class="photo-btn" data-lightbox="progress-gallery" data-title="Progress Photo - <?= $fileDate ?> (<?= $storageType === 'private' ? 'Private' : 'Public' ?>)">
                                        <i class="fas fa-expand"></i>
                                    </a>
                                    <button class="photo-btn photo-btn-delete" onclick="deletePhoto('<?= $filename ?>', '<?= $fileDate ?>', '<?= $storageType ?>')">
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
            $oldestDate = date('M j, Y', filemtime($oldestPhoto['path']));
            $newestDate = date('M j, Y', filemtime($newestPhoto['path']));
            $oldestFilename = basename($oldestPhoto['path']);
            $newestFilename = basename($newestPhoto['path']);
            
            // Determine image paths based on storage type
            if ($oldestPhoto['type'] === 'private') {
                $oldestImagePath = "../private_uploads/" . $oldestFilename;
            } else {
                $oldestImagePath = "../uploads/" . $oldestFilename;
            }
            
            if ($newestPhoto['type'] === 'private') {
                $newestImagePath = "../private_uploads/" . $newestFilename;
            } else {
                $newestImagePath = "../uploads/" . $newestFilename;
            }
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

                // Get selected storage type
                const storageType = document.querySelector('input[name="storage_type"]:checked').value;
                const storageLabel = storageType === 'private' ? 'Private Uploads' : 'Public Uploads';
                
                // Show selected file name in the progress status
                const fileName = fileInput.files[0].name;
                uploadStatus.textContent = `Uploading to ${storageLabel}: ${fileName}`;

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
            function deletePhoto(filename, date, storageType) {
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
                        // Redirect to delete the photo with storage type
                        window.location.href = `photos.php?delete=${filename}&type=${storageType}`;
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