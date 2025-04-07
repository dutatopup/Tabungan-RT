<?php
session_start();
include 'core/koneksi.php';
include 'core/headers.php';
include 'core/function.php';

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil role user dari session
$role = $_SESSION['role'];
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-abu-abu text-white">
            <p class="mb-0"><i class="fas fa-book-open me-2"></i>Rekap Harian Tabungan</p>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="bulan" class="form-label"><i class="fas fa-calendar me-1"></i> Pilih Bulan:</label>
                        <select class="form-select" id="bulan" name="bulan" required>
                            <option value="">Pilih Bulan</option>
                            <?php
                            $bulanOptions = getBulanOptions($conn);
                            $selectedBulan = $_GET['bulan'] ?? '';

                            foreach ($bulanOptions as $option) {
                                $selected = $selectedBulan == $option['nama_tabel'] ? 'selected' : '';
                                echo "<option value='{$option['nama_tabel']}' $selected>{$option['label']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-chart-bar me-1"></i> Lihat Rekap</button>
                    </div>
                </div>
            </form>

            <!-- Grafik -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <p class="mb-0"><i class="fas fa-chart-line me-2"></i>Grafik Tabungan Harian</p>
                </div>
                <div class="card-body">
                    <canvas id="tabunganChart" height="300"></canvas>
                </div>
            </div>

            <?php
            if (isset($_GET['bulan']) && !empty($_GET['bulan'])) {
                $nama_tabel = $_GET['bulan'];

                // Pagination settings
                $records_per_page = 10;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($page - 1) * $records_per_page;

                // Parse nama tabel
                $parsed = parseNamaTabel($nama_tabel);
                $bulan_angka = $parsed['bulan_angka'];
                $tahun = $parsed['tahun'];

                if ($bulan_angka && $tahun) {
                    $jumlah_hari = date('t', strtotime("$tahun-$bulan_angka-01"));
                    $nama_bulan = date('F Y', strtotime("$tahun-$bulan_angka-01"));
                    echo "<h3 class='h4 mb-4'><i class='fas fa-calendar-alt me-2'></i>Rekap Harian: $nama_bulan</h3>";
                } else {
                    $jumlah_hari = 30;
                    echo '<div class="alert alert-warning mb-4"><i class="fas fa-exclamation-triangle me-2"></i>Format nama tabel tidak valid, menggunakan default 30 hari</div>';
                }

                // Query untuk total data
                $count_query = "SELECT COUNT(DISTINCT tanggal) AS total 
                   FROM `$nama_tabel`";
                $count_result = $conn->query($count_query);
                $total_records = $count_result->fetch_assoc()['total'];
                $total_pages = ceil($total_records / $records_per_page);

                // Query data dengan pagination
                $query = "SELECT 
                tanggal,
                COUNT(DISTINCT CASE WHEN jumlah > 500 THEN warga_id END) AS jumlah_warga,
                SUM(jumlah) AS total_tabungan
              FROM `$nama_tabel`
              GROUP BY tanggal
              ORDER BY tanggal ASC
              LIMIT $offset, $records_per_page";

                $result = $conn->query($query);

                // Query untuk data grafik
                $chart_query = "SELECT 
                    tanggal,
                    SUM(jumlah) AS total_tabungan
                   FROM `$nama_tabel`
                   GROUP BY tanggal
                   ORDER BY tanggal ASC";
                $chart_result = $conn->query($chart_query);
                $chart_data = [];
                while ($row = $chart_result->fetch_assoc()) {
                    $chart_data[] = $row;
                }

                // Hitung total keseluruhan
                $total_query = "SELECT 
                    SUM(jumlah) AS total_keseluruhan,
                    COUNT(DISTINCT CASE WHEN jumlah >= 500 THEN warga_id END) AS total_warga
                   FROM `$nama_tabel`";
                $total_result = $conn->query($total_query);
                $total_data = $total_result->fetch_assoc();

                if ($result->num_rows > 0) {
                    echo '<div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th class="text-center">Tanggal</th>
                    <th width="20%" class="text-center">Yang Menabung*</th>
                    <th width="25%" class="text-center">Total Tabungan**</th>
                </tr>
            </thead>
            <tbody>';

                    $no = 1 + (($page - 1) * $records_per_page);
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                    <td class='text-center'>{$no}</td>
                    <td>" . date('d F Y', strtotime($row['tanggal'])) . "</td>
                    <td class='text-center'>" . $row['jumlah_warga'] . " orang</td>
                    <td class='text-end'>Rp " . number_format($row['total_tabungan'], 0, ',', '.') . "</td>
                  </tr>";
                        $no++;
                    }

                    // Baris total
                    echo '<tr class="table-secondary">
                <th colspan="3" class="text-center">Total Keseluruhan</th>
                <th class="text-end">Rp ' . number_format($total_data['total_keseluruhan'], 0, ',', '.') . '</th>
              </tr>';

                    echo '</tbody></table></div>';

                    echo '<div class="alert alert-info mt-3">
                <p class="mb-1"><strong>*</strong> Hanya menghitung warga dengan tabungan minimal Rp 500</p>
                <p class="mb-0"><strong>**</strong> Total Tabungan yang masuk pada hari tersebut belum terpotong kewajiban lain (Jimpitan/Dansos/Dana Mushola dan potongan 1% diakhir periode)</p>
              </div>';

                    // Pagination
                    echo '<nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">';
                    if ($page > 1) {
                        echo '<li class="page-item">
                    <a class="page-link" href="?bulan=' . urlencode($nama_tabel) . '&page=1" aria-label="First">
                        <span aria-hidden="true">&laquo;&laquo;</span>
                    </a>
                  </li>
                  <li class="page-item">
                    <a class="page-link" href="?bulan=' . urlencode($nama_tabel) . '&page=' . ($page - 1) . '" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                  </li>';
                    }

                    // Tampilkan beberapa halaman sekitar halaman saat ini
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">
                    <a class="page-link" href="?bulan=' . urlencode($nama_tabel) . '&page=' . $i . '">' . $i . '</a>
                  </li>';
                    }

                    if ($page < $total_pages) {
                        echo '<li class="page-item">
                    <a class="page-link" href="?bulan=' . urlencode($nama_tabel) . '&page=' . ($page + 1) . '" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                  </li>
                  <li class="page-item">
                    <a class="page-link" href="?bulan=' . urlencode($nama_tabel) . '&page=' . $total_pages . '" aria-label="Last">
                        <span aria-hidden="true">&raquo;&raquo;</span>
                    </a>
                  </li>';
                    }
                    echo '</ul></nav>';

                    // Info pagination
                    echo '<div class="text-center text-muted mb-4">
                <i class="fas fa-file-alt"></i> Halaman ' . $page . ' dari ' . $total_pages . ' | <i class="fas fa-database"></i> Total Data: ' . $total_records . '
              </div>';
                } else {
                    echo '<div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>Tidak ada data tabungan untuk bulan ini.
              </div>';
                }
            }
            ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Siapkan data untuk grafik
    const chartData = {
        labels: [<?php
                    if (isset($chart_data) && !empty($chart_data)) {
                        foreach ($chart_data as $row) {
                            echo "'" . date('d M', strtotime($row['tanggal'])) . "',";
                        }
                    }
                    ?>],
        datasets: [{
            label: 'Tabungan Harian',
            data: [<?php
                    if (isset($chart_data) && !empty($chart_data)) {
                        foreach ($chart_data as $row) {
                            echo $row['total_tabungan'] . ",";
                        }
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
        if (chartData.labels.length > 0) {
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
                                stepSize: 25000,
                                callback: function(value) {
                                    const roundedValue = Math.round(value / 25000) * 25000;
                                    return 'Rp ' + roundedValue.toLocaleString('id-ID');
                                }
                            },
                            grace: '5%'
                        }
                    }
                }
            });
        } else {
            document.getElementById('tabunganChart').parentElement.innerHTML =
                '<div class="alert alert-info text-center"><i class="fas fa-info-circle me-2"></i>Tidak ada data untuk menampilkan grafik</div>';
        }
    });
</script>

<?php include 'core/footer.php'; ?>