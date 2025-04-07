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

    // Get savings data to edit
    $sql = "SELECT t.jumlah, w.nama, t.tanggal 
            FROM `$tabel` t
            JOIN warga w ON t.warga_id = w.id
            WHERE t.id = $id";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $tabel = $_POST['tabel'];
    $jumlah_baru = $_POST['jumlah'];

    // Update savings data
    $update_sql = "UPDATE `$tabel` SET jumlah = '$jumlah_baru' WHERE id = $id";
    if ($conn->query($update_sql) === TRUE) {
        echo '<script>alert("Data berhasil diperbarui!"); window.location="rekap_harian.php";</script>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-warning text-black">
            <h2 class="h4 mb-0"><i class="fas fa-edit me-2"></i> Ubah Data Tabungan</h2>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="tabel" value="<?php echo $tabel; ?>">

                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-user me-1"></i> Nama Warga:</label>
                    <div class="form-control-plaintext"><?php echo htmlspecialchars($data['nama']); ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-calendar-alt me-1"></i> Tanggal:</label>
                    <div class="form-control-plaintext"><?php echo date('d F Y', strtotime($data['tanggal'])); ?></div>
                </div>

                <div class="mb-3">
                    <label for="jumlah" class="form-label"><i class="fas fa-money-bill-wave me-1"></i> Tabungan:</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="jumlah" name="jumlah" value="<?php echo htmlspecialchars($data['jumlah']); ?>" required>
                    </div>
                    <div class="invalid-feedback">Silakan isi jumlah tabungan</div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="javascript:history.back()" class="btn btn-secondary me-md-2"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Bootstrap form validation
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>

<?php include 'core/footer.php'; ?>