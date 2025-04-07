<?php
session_start();
include 'core/koneksi.php';
include 'core/headers.php';

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil role user dari session
$role = $_SESSION['role'] ?? 'user'; // Default ke 'user' jika tidak ada

// Atur tanggal
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$bulan_tahun = strtolower(date("Fy", strtotime($tanggal)));
$nama_tabel = "tab_" . $bulan_tahun;

// Cek eksistensi tabel
$check_table = $conn->query("SHOW TABLES LIKE '$nama_tabel'");
$table_exists = ($check_table->num_rows > 0);

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Pastikan tidak kurang dari 1
$offset = ($page - 1) * $records_per_page;

if ($table_exists) {
    // Hitung total records
    $total_records_query = $conn->prepare("SELECT COUNT(*) FROM `$nama_tabel` WHERE tanggal = ?");
    $total_records_query->bind_param("s", $tanggal);
    $total_records_query->execute();
    $total_records = $total_records_query->get_result()->fetch_row()[0];
    $total_pages = ceil($total_records / $records_per_page);

    // Query untuk mendapatkan total keseluruhan
    $total_query = $conn->prepare("SELECT SUM(jumlah) FROM `$nama_tabel` WHERE tanggal = ?");
    $total_query->bind_param("s", $tanggal);
    $total_query->execute();
    $total_all = $total_query->get_result()->fetch_row()[0] ?? 0;

    // Query dengan pagination
    $query = "SELECT t.id, w.nama, t.jumlah 
              FROM `$nama_tabel` t
              JOIN warga w ON t.warga_id = w.id
              WHERE t.tanggal = ?
              ORDER BY w.id ASC
              LIMIT $offset, $records_per_page";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    $total_page = 0; // Total untuk halaman ini saja

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $total_page += $row['jumlah'];
    }
}
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-abu-abu text-white">
            <p class="mb-0"><i class="fas fa-book me-2"></i> Data Tabungan Tanggal <?php echo date('d F Y', strtotime($tanggal)); ?></p>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <form method="GET" action="rekap_harian.php" class="row g-3">
                        <div class="col-md-8">
                            <label for="tanggal" class="form-label"><i class="fas fa-calendar-alt me-1"></i> Pilih Tanggal:</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?php echo $tanggal; ?>" required>
                            <input type="hidden" name="page" value="1">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-eye me-1"></i> Tampilkan</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($table_exists && !empty($data)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th class="text-center">Nama</th>
                                <th width="20%" class="text-center">Jumlah</th>
                                <?php if ($role === 'admin'): ?>
                                    <th width="15%" class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $index => $row): ?>
                                <tr>
                                    <td class="text-center"><?php echo $offset + $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td class="text-end">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                    <?php if ($role === 'admin'): ?>
                                        <td class="text-center">
                                            <!-- Edit Button -->
                                            <a href="edit.php?id=<?php echo $row['id']; ?>&tabel=<?php echo $nama_tabel; ?>"
                                                class="btn btn-sm btn-warning me-1"
                                                title="Edit Data">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <!-- Delete Button Trigger Modal -->
                                            <button type="button"
                                                class="btn btn-sm btn-danger"
                                                title="Hapus Data"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal<?php echo $row['id']; ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>

                                            <!-- Delete Confirmation Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Penghapusan</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Apakah Anda yakin ingin menghapus data ini?</p>
                                                            <p><strong>Nama:</strong> <?php echo htmlspecialchars($row['nama']); ?></p>
                                                            <p><strong>Jumlah:</strong> Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></p>
                                                            <div class="alert alert-warning mt-3">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                Data yang dihapus tidak dapat dikembalikan!
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <a href="hapus.php?id=<?php echo $row['id']; ?>&tabel=<?php echo $nama_tabel; ?>"
                                                                class="btn btn-danger">
                                                                Lanjutkan
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <th class="text-center" colspan="<?php echo ($role === 'admin') ? 3 : 2; ?>">Total Harian</th>
                                <th class="text-end">Rp <?php echo number_format($total_all, 0, ',', '.'); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?tanggal=<?= $tanggal ?>&page=1" aria-label="First">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?tanggal=<?= $tanggal ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        // Tampilkan beberapa halaman sekitar halaman saat ini
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?tanggal=<?= $tanggal ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?tanggal=<?= $tanggal ?>&page=<?= $page + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?tanggal=<?= $tanggal ?>&page=<?= $total_pages ?>" aria-label="Last">
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
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Tidak ada data tabungan untuk ditampilkan.
                </div>
            <?php endif; ?>

            <div class="text-end text-muted mt-3">
                <small>Tanggal Cetak: <?php echo date('d/m/Y'); ?></small>
            </div>
        </div>
    </div>
</div>

<?php include 'core/footer.php'; ?>