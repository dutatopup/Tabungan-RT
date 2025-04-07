<?php
ob_start();
session_start();
include 'core/koneksi.php';

// Cek login dan role
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

// Proses form sebelum output HTML
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $stmt = $conn->prepare("INSERT INTO warga (nama) VALUES (?)");
    $stmt->bind_param("s", $nama);
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = $nama . ' berhasil ditambahkan!';
        header("Location: " . $_SERVER['PHP_SELF']);
        ob_end_flush();
        exit();
    }
}

if (isset($_POST['edit'])) {
    $id = $_POST['id_edit'];
    $nama_baru = $_POST['nama_baru'];
    $stmt = $conn->prepare("UPDATE warga SET nama = ? WHERE id = ?");
    $stmt->bind_param("si", $nama_baru, $id);
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = 'Berhasil diubah menjadi ' . $nama_baru . '!';
        header("Location: " . $_SERVER['PHP_SELF']);
        ob_end_flush();
        exit();
    }
}

if (isset($_POST['hapus'])) {
    $id = $_POST['id_hapus'];
    $stmt = $conn->prepare("DELETE FROM warga WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = 'Warga berhasil dihapus!';
        header("Location: " . $_SERVER['PHP_SELF']);
        ob_end_flush();
        exit();
    }
}

include 'core/headers.php';
?>

<div class="container mt-4">
    <!-- Pesan Sukses -->
    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success_msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Form Tambah Warga -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <p class="card-title mb-0"><i class="fa-solid fa-user-plus"></i>&nbsp Tambah Warga</p>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama:</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <button type="submit" name="tambah" class="btn btn-primary w-100">
                            Tambah Warga
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Form Ubah Warga -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <p class="card-title mb-0"><i class="fa-solid fa-user-pen"></i>&nbsp Ubah Warga</p>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Pilih Warga:</label>
                            <select name="id_edit" class="form-select" required>
                                <option value="">-- Pilih Warga --</option>
                                <?php
                                $result = $conn->query("SELECT * FROM warga ORDER BY nama");
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . $row['id'] . '">' . $row['nama'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Baru:</label>
                            <input type="text" name="nama_baru" class="form-control" required>
                        </div>
                        <button type="submit" name="edit" class="btn btn-warning w-100">
                            Ubah Nama
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Form Hapus Warga -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <p class="card-title mb-0"><i class="fa-solid fa-user-minus"></i>&nbsp Hapus Warga</p>
                </div>
                <div class="card-body">
                    <form method="POST" id="hapusWargaForm">
                        <div class="mb-3">
                            <label class="form-label">Pilih Warga:</label>
                            <select name="id_hapus" class="form-select" required id="wargaSelect">
                                <option value="">-- Pilih Warga --</option>
                                <?php
                                $result = $conn->query("SELECT * FROM warga ORDER BY nama");
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . $row['id'] . '">' . $row['nama'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#konfirmasiHapusModal">
                            Hapus Warga
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <div class="modal fade" id="konfirmasiHapusModal" tabindex="-1" aria-labelledby="konfirmasiHapusModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="konfirmasiHapusModalLabel">Konfirmasi Penghapusan</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin menghapus warga: <strong><span id="namaWarga"></span></strong>?</p>
                        <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Data yang dihapus tidak dapat dikembalikan!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" form="hapusWargaForm" name="hapus" class="btn btn-danger">Ya, Hapus</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const wargaSelect = document.getElementById('wargaSelect');
                const namaWargaSpan = document.getElementById('namaWarga');

                wargaSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    namaWargaSpan.textContent = selectedOption.text;
                });
            });
        </script>
    </div>
</div>

<?php
include 'core/footer.php';
ob_end_flush();
?>