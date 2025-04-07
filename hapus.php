<?php
session_start();
include 'core/koneksi.php';
include 'core/headers.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['tabel'])) {
    $id = $_GET['id'];
    $tabel = $_GET['tabel'];

    // Get data info before deletion for confirmation
    $info_sql = "SELECT w.nama, t.tanggal, t.jumlah 
                FROM `$tabel` t
                JOIN warga w ON t.warga_id = w.id
                WHERE t.id = $id";
    $info_result = $conn->query($info_sql);
    $data_info = $info_result->fetch_assoc();

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete'])) {
        // Delete data from table
        $delete_sql = "DELETE FROM `$tabel` WHERE id = $id";
        if ($conn->query($delete_sql) === TRUE) {
            echo '<script>alert("Data berhasil dihapus!"); window.location="rekap_harian.php";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
}
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-danger text-white">
            <h2 class="h4 mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Penghapusan Data</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <h5 class="alert-heading"><i class="fas fa-exclamation-circle me-2"></i>Peringatan!</h5>
                <p class="mb-0">Anda yakin ingin menghapus data berikut? Tindakan ini tidak dapat dibatalkan.</p>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold"><i class="fas fa-user me-2"></i>Nama:</div>
                        <div class="col-md-9"><?php echo htmlspecialchars($data_info['nama']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold"><i class="fas fa-calendar-alt me-2"></i>Tanggal:</div>
                        <div class="col-md-9"><?php echo date('d F Y', strtotime($data_info['tanggal'])); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 fw-bold"><i class="fas fa-money-bill-wave me-2"></i>Jumlah:</div>
                        <div class="col-md-9">Rp <?php echo number_format($data_info['jumlah']); ?></div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="javascript:history.back()" class="btn btn-secondary me-md-2"><i class="fas fa-times me-1"></i> Batal</a>
                    <button type="submit" name="confirm_delete" class="btn btn-danger"><i class="fas fa-trash-alt me-1"></i> Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'core/footer.php'; ?>