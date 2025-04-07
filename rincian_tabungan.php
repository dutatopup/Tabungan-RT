<?php
session_start();
include 'core/koneksi.php';
include 'core/headers.php';

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil parameter
$nama_tabel = $_GET['bulan'] ?? '';
$warga_id = $_GET['warga_id'] ?? 0;

// Validasi parameter
if (empty($nama_tabel) || $warga_id <= 0) {
    die("<div class='alert alert-danger'>Parameter tidak valid</div>");
}

// Dapatkan nama bulan dan tahun
$bulan_tahun = strtoupper(str_replace("tab_", "", $nama_tabel));

// Query data warga
$query_warga = $conn->prepare("SELECT nama FROM warga WHERE id = ?");
$query_warga->bind_param("i", $warga_id);
$query_warga->execute();
$result_warga = $query_warga->get_result();
$warga = $result_warga->fetch_assoc();

// Query rincian tabungan
$query = $conn->prepare("
    SELECT tanggal, jumlah 
    FROM `$nama_tabel` 
    WHERE warga_id = ? 
    ORDER BY tanggal ASC
");
$query->bind_param("i", $warga_id);
$query->execute();
$result = $query->get_result();

// Hitung total
$total = 0;
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    $total += $row['jumlah'];
}

// Pagination settings
$per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$total_data = count($data);
$total_pages = ceil($total_data / $per_page);
$offset = ($page - 1) * $per_page;
$paginated_data = array_slice($data, $offset, $per_page);
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-abu-abu text-white">
            <p class="mb-0"><i class="fa-solid fa-file-lines"></i> Rincian Tabungan <?php echo htmlspecialchars($warga['nama']); ?></p>
            <p class="mb-0">Bulan: <?php echo $bulan_tahun; ?></h4>
        </div>

        <div class="card-body">
            <div class="mb-4">
                <a href="rekap_bulanan.php?bulan=<?php echo urlencode($nama_tabel); ?>" class="btn btn-warning">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Rekap Bulanan
                </a>
            </div>

            <!-- Grafik -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-chart-line me-1"></i> Grafik Tabungan Harian
                </div>
                <div class="card-body">
                    <canvas id="tabunganChart" height="300"></canvas>
                </div>
            </div>

            <!-- Tabel Rincian -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-table me-1"></i> Data Tabungan
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th class="text-center">Tanggal</th>
                                    <th class="text-center">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($paginated_data)): ?>
                                    <?php foreach ($paginated_data as $index => $row): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $offset + $index + 1; ?></td>
                                            <td class="text-center"><?php echo date('d F Y', strtotime($row['tanggal'])); ?></td>
                                            <td class="text-end">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <tr class="table-secondary">
                                        <td colspan="2" class="text-center"><strong>Total Tabungan</strong></td>
                                        <td class='text-end'><strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Tidak ada data tabungan</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center mb-2"> <!-- Changed to justify-content-center -->
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?bulan=<?= urlencode($nama_tabel) ?>&warga_id=<?= $warga_id ?>&page=1">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?bulan=<?= urlencode($nama_tabel) ?>&warga_id=<?= $warga_id ?>&page=<?= $page - 1 ?>">
                                                <i class="fas fa-angle-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);

                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?bulan=<?= urlencode($nama_tabel) ?>&warga_id=<?= $warga_id ?>&page=<?= $i ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?bulan=<?= urlencode($nama_tabel) ?>&warga_id=<?= $warga_id ?>&page=<?= $page + 1 ?>">
                                                <i class="fas fa-angle-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?bulan=<?= urlencode($nama_tabel) ?>&warga_id=<?= $warga_id ?>&page=<?= $total_pages ?>">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>

                        <div class="text-center text-muted mb-3"> <!-- Centered text -->
                            <i class="fas fa-file-alt"></i> Halaman <?= $page ?> dari <?= $total_pages ?> | <i class="fas fa-database"></i> Total Data: <?= $total_data ?>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        <strong>Catatan:</strong> Rincian tersebut adalah rincian tabungan untuk bulan <?php echo $bulan_tahun; ?> yang belum dipotong jimpitan/dansos.
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-phone-alt me-1"></i>
                        <strong>Jika ada kesalahan, silakan hubungi administrator.</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tambahkan Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Siapkan data untuk grafik
    const chartData = {
        labels: [<?php
                    foreach ($data as $row) {
                        echo "'" . date('d M', strtotime($row['tanggal'])) . "',";
                    }
                    ?>],
        datasets: [{
            label: 'Tabungan Harian',
            data: [<?php
                    foreach ($data as $row) {
                        echo $row['jumlah'] . ",";
                    }
                    ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
        }]
    };

    // Buat grafik ketika dokumen siap
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('tabunganChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Perkembangan Tabungan Harian',
                        font: {
                            size: 14
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5000,
                            callback: function(value) {
                                const roundedValue = Math.round(value / 5000) * 5000;
                                return 'Rp ' + roundedValue.toLocaleString('id-ID');
                            }
                        },
                        grace: '5%'
                    }
                }
            }
        });
    });
</script>
<?php include 'core/footer.php'; ?>