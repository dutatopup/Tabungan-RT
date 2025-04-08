<?php
session_start();
include 'core/koneksi.php';


// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit;
}
// Proses update data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $tanggal = $_POST['tanggal'];
    $masuk = $_POST['masuk'] ?: 0;
    $keluar = $_POST['keluar'] ?: 0;
    $keterangan = $_POST['keterangan'];

    $conn->begin_transaction();
    try {
        // 1. Ambil data LAMA
        $old_data = $conn->query("SELECT masuk, keluar FROM kas_rt WHERE id = $id")->fetch_assoc();
        $old_masuk = $old_data['masuk'];
        $old_keluar = $old_data['keluar'];

        // 2. Update transaksi saat ini
        $stmt = $conn->prepare("UPDATE kas_rt SET tanggal=?, masuk=?, keluar=?, keterangan=? WHERE id=?");
        $stmt->bind_param("sddsi", $tanggal, $masuk, $keluar, $keterangan, $id);
        $stmt->execute();

        // 3. Hitung selisih dan update SALDO BERANTAI
        $selisih = ($masuk - $old_masuk) - ($keluar - $old_keluar);
        if ($selisih != 0) {
            // 🔥 Update saldo di record yang diubah
            $conn->query("UPDATE kas_rt SET saldo_akhir = saldo_akhir + $selisih WHERE id = $id");
            
            // 🔥 Update saldo di semua record SETELAH ini
            $conn->query("UPDATE kas_rt SET saldo_akhir = saldo_akhir + $selisih WHERE id > $id");
        }

        $conn->commit();
        header("Location: dashboard.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
}

// Ambil data yang akan diedit
$id = $_GET['id'] ?? 0;
$data = $conn->query("SELECT * FROM kas_rt WHERE id = $id")->fetch_assoc();
include 'core/headers.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h2 class="h4 mb-0"><i class="fas fa-money-bill-wave me-2"></i>Edit Data Kas</h2>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="<?= $data['id'] ?>">

                <div class="mb-3">
                    <label for="tanggal" class="form-label"><i class="fas fa-calendar-alt me-1"></i> Tanggal:</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= $data['tanggal'] ?>" required>
                    <div class="invalid-feedback">Silakan isi tanggal</div>
                </div>

                <div class="mb-3">
                    <label for="masuk" class="form-label"><i class="fas fa-arrow-down me-1 text-success"></i> Pemasukan (Rp):</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="masuk" name="masuk" value="<?= $data['masuk'] ?>" min="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="keluar" class="form-label"><i class="fas fa-arrow-up me-1 text-danger"></i> Pengeluaran (Rp):</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="keluar" name="keluar" value="<?= $data['keluar'] ?>" min="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="keterangan" class="form-label"><i class="fas fa-info-circle me-1"></i> Keterangan:</label>
                    <input type="text" class="form-control" id="keterangan" name="keterangan" value="<?= htmlspecialchars($data['keterangan']) ?>" required>
                    <div class="invalid-feedback">Silakan isi keterangan</div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="dashboard.php" class="btn btn-secondary me-md-2"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    <button type="submit" name="update" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Data</button>
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
