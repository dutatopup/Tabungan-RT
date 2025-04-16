<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabungan RT 06 Bumijaya</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/icon.png">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
</head>
<body>
    <!-- Desktop Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-abu-abu d-none d-lg-block fixed-top">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center w-100">
                <!-- Logo di sebelah kiri -->
                <div class="d-flex align-items-center text-white">
                    <i class="fa-solid fa-circle-dollar-to-slot fs-3 me-2"></i>
                    <span class="fs-5 fw-bold">Tabungan RT 06 Bumijaya</span>
                </div>
                
                <!-- Menu dan logout di sebelah kanan -->
                <div class="d-flex align-items-center">
                    <nav class="me-3"> <!-- Reduced right margin -->
                        <ul class="nav">
                            <?php if (isset($_SESSION['username'])): ?>
                                <li class="nav-item">
                                    <a href="dashboard.php" class="nav-link text-white">
                                        <i class="fa-solid fa-house me-1"></i> Beranda
                                    </a>
                                </li>
                                
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-tools me-1"></i> Admin Tools
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="input.php"><i class="fa-regular fa-edit me-1"></i> Input Data</a></li>
                                            <li><a class="dropdown-item" href="kelola_warga.php"><i class="fa-regular fa-circle-user me-1"></i> Kelola Warga</a></li>
                                        </ul>
                                    </li>
                                    
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-file-alt me-1"></i> Laporan
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="rekap_harian.php"><i class="fas fa-calendar-day me-1"></i> Harian per Orang</a></li>
                                            <li><a class="dropdown-item" href="rekap_harian_perbulan.php"><i class="fas fa-calendar-week me-1"></i> Harian per Bulan</a></li>
                                            <li><a class="dropdown-item" href="rekap_bulanan.php"><i class="fas fa-calendar-alt me-1"></i> Bulanan</a></li>
                                            <li><a class="dropdown-item" href="rekap_tahunan.php"><i class="fas fa-calendar-check me-1"></i> Tahunan</a></li>
                                        </ul>
                                    </li>
                                <?php else: ?>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-file-alt me-1"></i> Laporan
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="rekap_harian.php"><i class="fas fa-calendar-day me-1"></i> Harian per Orang</a></li>
                                            <li><a class="dropdown-item" href="rekap_harian_perbulan.php"><i class="fas fa-calendar-week me-1"></i> Harian per Bulan</a></li>
                                            <li><a class="dropdown-item" href="rekap_bulanan.php"><i class="fas fa-calendar-alt me-1"></i> Bulanan</a></li>
                                            <li><a class="dropdown-item" href="rekap_tahunan.php"><i class="fas fa-calendar-check me-1"></i> Tahunan</a></li>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    
                    <!-- Tombol logout -->
                    <?php if (isset($_SESSION['username'])): ?>
                        <a href="logout.php" class="btn btn-danger text-white">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation -->
    <nav class="navbar navbar-dark bg-abu-abu d-lg-none fixed-top">
        <div class="container">
            <div class="d-flex align-items-center text-white">
                <i class="fa-solid fa-circle-dollar-to-slot fs-3 me-2"></i>
                <span class="fs-5 fw-bold">Tabungan RT 06</span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="offcanvas offcanvas-end bg-abu-abu text-white" tabindex="-1" id="mobileMenu" style="width: 70%;">
                <div class="offcanvas-header">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-dollar-to-slot fs-3 me-2"></i>
                        <h5 class="offcanvas-title">Tabungan RT 06</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <nav class="nav flex-column">
                        <?php if (isset($_SESSION['username'])): ?>
                            <a href="dashboard.php" class="nav-link text-white mb-2">
                                <i class="fa-solid fa-house me-2"></i> Beranda
                            </a>
                            
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <div class="dropdown mb-2">
                                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-tools me-2"></i> Admin Tools
                                    </a>
                                    <ul class="dropdown-menu bg-abu-abu">
                                        <li><a class="dropdown-item text-white" href="input.php"><i class="fa-regular fa-edit me-1"></i> Input Data</a></li>
                                        <li><a class="dropdown-item text-white" href="kelola_warga.php"><i class="fa-regular fa-circle-user me-1"></i> Kelola Warga</a></li>
                                    </ul>
                                </div>
                                
                                <div class="dropdown mb-2">
                                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-file-alt me-2"></i> Laporan
                                    </a>
                                    <ul class="dropdown-menu bg-abu-abu">
                                        <li><a class="dropdown-item text-white" href="rekap_harian.php"><i class="fas fa-calendar-day me-1"></i> Harian per Orang</a></li>
                                        <li><a class="dropdown-item text-white" href="rekap_harian_perbulan.php"><i class="fas fa-calendar-week me-1"></i> Harian per Bulan</a></li>
                                        <li><a class="dropdown-item text-white" href="rekap_bulanan.php"><i class="fas fa-calendar-alt me-1"></i> Bulanan</a></li>
                                        <li><a class="dropdown-item text-white" href="rekap_tahunan.php"><i class="fas fa-calendar-check me-1"></i> Tahunan</a></li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="dropdown mb-2">
                                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-file-alt me-2"></i> Laporan
                                    </a>
                                    <ul class="dropdown-menu bg-abu-abu">
                                        <li><a class="dropdown-item text-white" href="rekap_harian.php"><i class="fas fa-calendar-day me-1"></i> Harian per Orang</a></li>
                                        <li><a class="dropdown-item text-white" href="rekap_harian_perbulan.php"><i class="fas fa-calendar-week me-1"></i> Harian per Bulan</a></li>
                                        <li><a class="dropdown-item text-white" href="rekap_bulanan.php"><i class="fas fa-calendar-alt me-1"></i> Bulanan</a></li>
                                        <li><a class="dropdown-item text-white" href="rekap_tahunan.php"><i class="fas fa-calendar-check me-1"></i> Tahunan</a></li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <a href="logout.php" class="nav-link text-danger mt-3">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div style="padding-top: 50px;">
        <div class="container mt-3">
            <div class="content">
                <div class="content-wrap">
