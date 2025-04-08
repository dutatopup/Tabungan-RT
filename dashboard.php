<?php
session_start();
include 'core/koneksi.php';

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil role user dari session
$role = $_SESSION['role'] ?? 'user';

// Variabel untuk pesan sukses
$success_message = '';
$tanggal_hari_ini = date('Y-m-d');

// Proses form tambah data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $tanggal = $_POST['tanggal'];
    $masuk = $_POST['masuk'] ?: 0;
    $keluar = $_POST['keluar'] ?: 0;
    $keterangan = $_POST['keterangan'];

    // Hitung saldo akhir baru
    $saldo_sebelumnya = 0;
    $result = $conn->query("SELECT saldo_akhir FROM kas_rt ORDER BY id DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $saldo_sebelumnya = $row['saldo_akhir'];
    }

    $saldo_akhir = $saldo_sebelumnya + $masuk - $keluar;

    $stmt = $conn->prepare("INSERT INTO kas_rt (tanggal, masuk, keluar, saldo_akhir, keterangan) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdids", $tanggal, $masuk, $keluar, $saldo_akhir, $keterangan);

    if ($stmt->execute()) {
        $success_message = "Data berhasil disimpan!";
    } else {
        $success_message = "Gagal menyimpan data: " . $conn->error;
    }
}

// Proses hapus data
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    // Dapatkan data yang akan dihapus untuk memperbarui saldo setelahnya
    $data_hapus = $conn->query("SELECT * FROM kas_rt WHERE id = $id")->fetch_assoc();
    $selisih = $data_hapus['masuk'] - $data_hapus['keluar'];

    // Hapus data
    $conn->query("DELETE FROM kas_rt WHERE id = $id");

    // Perbarui saldo akhir untuk semua record setelah yang dihapus
    $conn->query("UPDATE kas_rt SET saldo_akhir = saldo_akhir - $selisih WHERE id > $id");

    header("Location: dashboard.php");
    exit;
}

// Pagination setup
$records_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $records_per_page;

// Hitung total records
$total_records = $conn->query("SELECT COUNT(*) FROM kas_rt")->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);

// Ambil data dengan pagination
$query = "SELECT * FROM kas_rt ORDER BY id ASC LIMIT $offset, $records_per_page";
$result = $conn->query($query);
include 'core/headers.php';
?>

<div class="container mt-4">
    <h3 class="mb-4"><i class="fa-solid fa-house"></i> Beranda</h3>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
        <!-- Form Tambah Data -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="h5 mb-0">Tambah Transaksi</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal:</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal_hari_ini); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Pemasukan (Rp):</label>
                            <input type="number" name="masuk" class="form-control" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Pengeluaran (Rp):</label>
                            <input type="number" name="keluar" class="form-control" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Keterangan:</label>
                            <input type="text" name="keterangan" class="form-control" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" name="tambah" class="btn btn-primary w-100">Tambah Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tambahkan kode ini SEBELUM card tabel laporan (setelah form tambah data) -->
    <div class="row mb-4">
        <!-- Kas Masuk Terakhir -->
        <div class="col-md-4 mb-4">
            <?php
            $last_masuk = $conn->query("SELECT masuk, keterangan, tanggal FROM kas_rt WHERE masuk > 0 ORDER BY id DESC LIMIT 1")->fetch_assoc();
            $last_masuk_value = $last_masuk ? $last_masuk['masuk'] : 0;
            $keterangan_masuk = $last_masuk ? $last_masuk['keterangan'] : 'Belum ada transaksi';
            $tanggal_masuk = $last_masuk ? date('d F Y', strtotime($last_masuk['tanggal'])) : '-';
            ?>
            <div class="card border-success shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-arrow-down me-2"></i>Saldo Masuk Terakhir</h5>
                </div>
                <div class="card-body text-center py-3">
                    <h3 class="text-success">Rp <?= number_format($last_masuk_value, 0, ',', '.') ?></h3>
                    <p class="text-muted mb-1 small">
                        <i class="fas fa-calendar-day text-success"></i>
                        <?= $tanggal_masuk ?>
                    </p>
                    <p class="text-muted mb-0 small" style="min-height: 40px;">
                        <i class="fas fa-info-circle text-success"></i>
                        <?= htmlspecialchars(mb_strimwidth($keterangan_masuk, 0, 50, '...')) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Saldo Keluar Terakhir -->
        <div class="col-md-4 mb-4">
            <?php
            $last_keluar = $conn->query("SELECT keluar, keterangan, tanggal FROM kas_rt WHERE keluar > 0 ORDER BY id DESC LIMIT 1")->fetch_assoc();
            $last_keluar_value = $last_keluar ? $last_keluar['keluar'] : 0;
            $keterangan_keluar = $last_keluar ? $last_keluar['keterangan'] : 'Belum ada transaksi';
            $tanggal_keluar = $last_keluar ? date('d F Y', strtotime($last_keluar['tanggal'])) : '-';
            ?>
            <div class="card border-danger shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-arrow-up me-2"></i>Saldo Keluar Terakhir</h5>
                </div>
                <div class="card-body text-center py-3">
                    <h3 class="text-danger">Rp <?= number_format($last_keluar_value, 0, ',', '.') ?></h3>
                    <p class="text-muted mb-1 small">
                        <i class="fas fa-calendar-day text-danger"></i>
                        <?= $tanggal_keluar ?>
                    </p>
                    <p class="text-muted mb-0 small" style="min-height: 40px;">
                        <i class="fas fa-info-circle text-danger"></i>
                        <?= htmlspecialchars(mb_strimwidth($keterangan_keluar, 0, 50, '...')) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Saldo Akhir -->
        <div class="col-md-4 mb-4">
            <?php
            $saldo_akhir = $conn->query("SELECT saldo_akhir, keterangan, tanggal FROM kas_rt ORDER BY id DESC LIMIT 1")->fetch_assoc();
            $saldo_akhir_value = $saldo_akhir ? $saldo_akhir['saldo_akhir'] : 0;
            $keterangan_saldo = $saldo_akhir ? $saldo_akhir['keterangan'] : 'Saldo awal';
            $tanggal_saldo = $saldo_akhir ? date('d F Y', strtotime($saldo_akhir['tanggal'])) : '-';
            ?>
            <div class="card border-primary shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-wallet me-2"></i>Saldo Akhir</h5>
                </div>
                <div class="card-body text-center py-3">
                    <h3 class="text-primary">Rp <?= number_format($saldo_akhir_value, 0, ',', '.') ?></h3>
                    <p class="text-muted mb-1 small">
                        <i class="fas fa-calendar-day text-primary"></i>
                        <?= $tanggal_saldo ?>
                    </p>
                    <p class="text-muted mb-0 small" style="min-height: 40px;">
                        <i class="fas fa-info-circle text-primary"></i>
                        Saldo Saat Ini
                    </p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-abu-abu text-white">
                <h2 class="h5 mb-0"><i class="fas fa-chart-bar"></i> Laporan Kas RT 06 Bumijaya</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered table-bordered-columns">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center"><i class="fas fa-calendar-day text-primary"></i> Tanggal</th>
                                <th class="text-center"><i class="fas fa-arrow-down text-success"></i> Saldo Masuk</th>
                                <th class="text-center"><i class="fas fa-arrow-up text-danger"></i> Saldo Keluar</th>
                                <th class="text-center"><i class="fas fa-wallet text-warning"></i> Saldo Akhir</th>
                                <th class="text-center"><i class="fas fa-info-circle text-info"></i> Keterangan</th>
                                <?php if ($role === 'admin'): ?>
                                    <th class="text-center"><i class="fas fa-cog text-danger"></i> Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = $offset + 1;

                            while ($row = $result->fetch_assoc()) {
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= date('d F Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="text-end"><?= $row['masuk'] ? 'Rp ' . number_format($row['masuk'], 0, ',', '.') : '-' ?></td>
                                    <td class="text-end"><?= $row['keluar'] ? 'Rp ' . number_format($row['keluar'], 0, ',', '.') : '-' ?></td>
                                    <td class="text-end fw-bold"><?= 'Rp ' . number_format($row['saldo_akhir'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                    <?php if ($role === 'admin'): ?>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="edit_kas.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>

                                                <!-- Tombol trigger modal -->
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>

                                                <!-- Delete Confirmation Modal -->
                                                <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Penghapusan</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Anda akan menghapus data transaksi berikut:</p>
                                                                <ul class="list-unstyled">
                                                                    <li><strong>Tanggal:</strong> <?= date('d F Y', strtotime($row['tanggal'])) ?></li>
                                                                    <li><strong>Pemasukan:</strong> <?= $row['masuk'] ? 'Rp ' . number_format($row['masuk'], 0, ',', '.') : '-' ?></li>
                                                                    <li><strong>Pengeluaran:</strong> <?= $row['keluar'] ? 'Rp ' . number_format($row['keluar'], 0, ',', '.') : '-' ?></li>
                                                                    <li><strong>Keterangan:</strong> <?= htmlspecialchars($row['keterangan']) ?></li>
                                                                </ul>
                                                                <div class="alert alert-danger mt-3">
                                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                                    Data yang dihapus tidak dapat dikembalikan!
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <a href="dashboard.php?hapus=<?= $row['id'] ?>" class="btn btn-danger">Hapus</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-2">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1" aria-label="First" title="Halaman Pertama">
                            <span aria-hidden="true">&laquo;&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous" title="Sebelumnya">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                // Show limited page numbers for mobile
                $start_page = max(1, $page - 1);
                $end_page = min($total_pages, $page + 1);

                // Always show first page
                if ($start_page > 1): ?>
                    <li class="page-item <?= 1 == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=1">1</a>
                    </li>
                    <?php if ($start_page > 2): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif;
                endif;

                for ($i = $start_page; $i <= $end_page; $i++):
                    if ($i == 1 && $start_page > 1) continue; // Skip if already shown
                    ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor;

                // Always show last page
                if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                    <li class="page-item <?= $total_pages == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $total_pages ?>"><?= $total_pages ?></a>
                    </li>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next" title="Berikutnya">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $total_pages ?>" aria-label="Last" title="Halaman Terakhir">
                            <span aria-hidden="true">&raquo;&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="text-center text-muted small">
            <i class="fas fa-file-alt"></i> Halaman <?= $page ?> dari <?= $total_pages ?> |
            <i class="fas fa-database"></i> Total Data: <?= number_format($total_records) ?>
        </div>
    </div>
    <?php include 'core/footer.php'; ?>
