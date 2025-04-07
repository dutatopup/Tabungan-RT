<?php
session_start();
include 'core/koneksi.php';
include 'core/headers.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i> Akses Ditolak
                    </h3>
                </div>
                <div class="card-body text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-ban text-danger" style="font-size: 5rem;"></i>
                    </div>
                    <h4 class="text-danger mb-3">Anda tidak memiliki izin untuk mengakses halaman ini.</h4>
                    <p class="text-muted">Silakan hubungi administrator jika Anda memerlukan akses.</p>
                    
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Kembali
                        </a>
                        <a href="dashboard.php" class="btn btn-danger">
                            <i class="fas fa-home me-2"></i> Ke Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-light text-muted text-center small">
                    <i class="fas fa-info-circle me-1"></i> Kode Error: 403 - Forbidden
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'core/footer.php'; ?>