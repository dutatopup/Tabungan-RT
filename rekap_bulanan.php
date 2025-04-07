<?php
session_start();
include 'core/koneksi.php';
include 'core/headers.php';
include 'core/function.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-abu-abu text-white">
            <p class="mb-0"><i class="fa-solid fa-swatchbook"></i> Rekap Tabungan Per Bulan</p>
        </div>

        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-regular fa-calendar-check"></i> Pilih Bulan:</label>
                        <select name="bulan" class="form-select" required>
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

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-magnifying-glass"></i> Cari Nama:</label>
                        <input type="text" name="cari" class="form-control"
                            value="<?php echo isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : ''; ?>"
                            placeholder="Masukkan nama warga">
                    </div>

                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-70">
                            <i class="fas fa-eye me-1"></i> Lihat Rekap
                        </button>
                    </div>
                </div>
            </form>

            <?php
            if (isset($_GET['bulan']) && !empty($_GET['bulan'])) {
                $nama_tabel = $_GET['bulan'];
                $cari = isset($_GET['cari']) ? $_GET['cari'] : '';

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
                    echo "<h3 class='mb-4'><i class='fa-regular fa-calendar-days'></i> Rekap Tabungan: $nama_bulan</h3>";
                } else {
                    $jumlah_hari = 30;
                    echo "<div class='alert alert-warning mb-4'>⚠️ Format nama tabel tidak valid, menggunakan default 30 hari</div>";
                }

                // Konstanta perhitungan
                $jimpitan_per_hari = 500;
                $dana_mushola_per_hari = 500;
                $dansos_per_bulan = 1000;

                // Query untuk total data
                $count_query = "SELECT COUNT(DISTINCT warga.id) AS total 
                               FROM warga 
                               LEFT JOIN `$nama_tabel` t ON warga.id = t.warga_id 
                               WHERE warga.nama LIKE '%$cari%'";
                $count_result = $conn->query($count_query);
                $total_records = $count_result ? $count_result->fetch_assoc()['total'] : 0;
                $total_pages = ceil($total_records / $records_per_page);

                // Query untuk summary keseluruhan (tanpa pagination)
                $summary_query = "SELECT 
                                    warga.id,
                                    COALESCE(SUM(t.jumlah), 0) AS total_tabungan,
                                    ($jumlah_hari * $jimpitan_per_hari + $dansos_per_bulan) AS jimpitan,
                                    ($jumlah_hari * $dana_mushola_per_hari) AS mushola
                                  FROM warga 
                                  LEFT JOIN `$nama_tabel` t ON warga.id = t.warga_id 
                                  WHERE warga.nama LIKE '%$cari%'
                                  GROUP BY warga.id";

                $summary_result = $conn->query($summary_query);
                $summary_totals = array(
                    'total_tabungan' => 0,
                    'total_jimpitan' => 0,
                    'total_mushola' => 0,
                    'total_minus' => 0
                );

                if ($summary_result) {
                    while ($row = $summary_result->fetch_assoc()) {
                        $summary_totals['total_tabungan'] += $row['total_tabungan'] ?? 0;
                        $summary_totals['total_jimpitan'] += $row['jimpitan'] ?? 0;
                        $summary_totals['total_mushola'] += $row['mushola'] ?? 0;

                        $total_didapat = ($row['total_tabungan'] ?? 0) - ($row['jimpitan'] ?? 0) - ($row['mushola'] ?? 0);
                        if ($total_didapat < 0) {
                            $summary_totals['total_minus'] += abs($total_didapat);
                        }
                    }
                }

                // Query data dengan pagination
                $query = "SELECT warga.id, warga.nama, 
                                 COALESCE(SUM(t.jumlah), 0) AS total_tabungan 
                          FROM warga 
                          LEFT JOIN `$nama_tabel` t ON warga.id = t.warga_id 
                          WHERE warga.nama LIKE '%$cari%' 
                          GROUP BY warga.id 
                          ORDER BY warga.id ASC
                          LIMIT $offset, $records_per_page";

                $result = $conn->query($query);

                if ($result && $result->num_rows > 0) {
                    // Hitung total untuk halaman ini
                    $total_page_tabungan = 0;
                    $total_page_jimpitan = 0;
                    $total_page_mushola = 0;
                    $total_page_minus = 0;

                    echo '<div class="table-responsive mb-4">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">No</th>
                                <th class="text-center">Nama</th>
                                <th class="text-center">Total Tabungan</th>
                                <th class="text-center">Dana Jimpitan/Dansos</th>
                                <th class="text-center">Dana Mushola</th>
                                <th class="text-center">Total Didapat</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>';

                    $no = 1 + (($page - 1) * $records_per_page);
                    while ($row = $result->fetch_assoc()) {
                        $jimpitan_dansos = ($jumlah_hari * $jimpitan_per_hari) + $dansos_per_bulan;
                        $dana_mushola = $jumlah_hari * $dana_mushola_per_hari;
                        $total_didapat = $row['total_tabungan'] - $jimpitan_dansos - $dana_mushola;

                        $total_page_tabungan += $row['total_tabungan'];
                        $total_page_jimpitan += $jimpitan_dansos;
                        $total_page_mushola += $dana_mushola;

                        if ($total_didapat < 0) {
                            $total_page_minus += abs($total_didapat);
                        }

                        $total_didapat_format = $total_didapat < 0
                            ? "<span class='text-danger'>Rp " . number_format($total_didapat, 0, ',', '.') . "</span>"
                            : "Rp " . number_format($total_didapat, 0, ',', '.');

                        echo "<tr>
                                <td class='text-center'>{$no}</td>
                                <td>" . htmlspecialchars($row['nama']) . "</td>
                                <td class='text-end'>Rp " . number_format($row['total_tabungan'], 0, ',', '.') . "</td>
                                <td class='text-end'>Rp " . number_format($jimpitan_dansos, 0, ',', '.') . "</td>
                                <td class='text-end'>Rp " . number_format($dana_mushola, 0, ',', '.') . "</td>
                                <td class='text-end'>{$total_didapat_format}</td>
                                <td class='text-center'>
                                    <a href='rincian_tabungan.php?bulan=" . urlencode($nama_tabel) . "&warga_id=" . $row['id'] . "' class='btn btn-sm btn-danger'>
                                        <i class='fas fa-rocket me-1'></i> Rincian
                                    </a>
                                </td>
                              </tr>";
                        $no++;
                    }

                    // Total untuk halaman ini
                    echo '<tr class="table-secondary">
                            <td colspan="2" class="text-center"><strong>Total Halaman Ini</strong></td>
                            <td class="text-end"><strong>Rp ' . number_format($total_page_tabungan, 0, ',', '.') . '</strong></td>
                            <td class="text-end"><strong>Rp ' . number_format($total_page_jimpitan, 0, ',', '.') . '</strong></td>
                            <td class="text-end"><strong>Rp ' . number_format($total_page_mushola, 0, ',', '.') . '</strong></td>
                            <td colspan="2" class="text-center"><strong>Kekurangan: Rp ' . number_format($total_page_minus, 0, ',', '.') . '</strong></td>
                          </tr>';

                    echo '</tbody></table></div>';


                    echo '<nav aria-label="Page navigation">';
                    echo '<ul class="pagination justify-content-center">';



                    if ($page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?bulan=' . urlencode($nama_tabel) . '&cari=' . urlencode($cari) . '&page=1"><i class="fas fa-angle-double-left"></i></a></li>';
                        echo '<li class="page-item"><a class="page-link" href="?bulan=' . urlencode($nama_tabel) . '&cari=' . urlencode($cari) . '&page=' . ($page - 1) . '"><i class="fas fa-angle-left"></i></a></li>';
                    }

                    // Tampilkan beberapa halaman sekitar halaman saat ini
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?bulan=' . urlencode($nama_tabel) . '&cari=' . urlencode($cari) . '&page=' . $i . '">' . $i . '</a></li>';
                    }

                    if ($page < $total_pages) {
                        echo '<li class="page-item"><a class="page-link" href="?bulan=' . urlencode($nama_tabel) . '&cari=' . urlencode($cari) . '&page=' . ($page + 1) . '"><i class="fas fa-angle-right"></i></a></li>';
                        echo '<li class="page-item"><a class="page-link" href="?bulan=' . urlencode($nama_tabel) . '&cari=' . urlencode($cari) . '&page=' . $total_pages . '"><i class="fas fa-angle-double-right"></i></a></li>';
                    }
                    echo '</ul></nav>';
                    echo '<div class="text-center text-muted mb-2">';
                    echo '<i class="fas fa-file-alt"></i> Halaman ' . $page . ' dari ' . $total_pages . ' | <i class="fas fa-database"></i> Total Data: ' . $total_records . ' </div>';
                    echo '</div>';

                    if (empty($cari)) {
                        // Hitung total bersih Jimpitan/Dansos (tanpa mempengaruhi dana mushola)
                        $total_bersih_jimpitan = $summary_totals['total_jimpitan'] - $summary_totals['total_minus'];

                        // Tampilkan summary
                        echo '<div class="card border-primary mb-4">';
                        echo '<div class="card-header bg-primary text-white">';
                        echo '<p class="mb-0"><i class="fas fa-chart-bar"></i> Ringkasan Bulanan (Semua Data)</p>';
                        echo '</div>';
                        echo '<div class="card-body">';
                        echo '<div class="row">';

                        echo '<div class="col-md-6">';
                        echo '<p class="mb-2"><span class="fw-bold"><i class="fas fa-sack-dollar text-warning"></i> Total Tabungan Warga:</span> <span class="float-end">Rp ' . number_format($summary_totals['total_tabungan'], 0, ',', '.') . '</span></p>';
                        echo '<p class="mb-2"><span class="fw-bold"><i class="far fa-suitcase-medical text-info"></i> Total Jimpitan/Dansos:</span> <span class="float-end">Rp ' . number_format($summary_totals['total_jimpitan'], 0, ',', '.') . '</span></p>';
                        echo '</div>';

                        echo '<div class="col-md-6">';
                        echo '<p class="mb-2"><span class="fw-bold"><i class="far fa-star-and-crescent text-primary"></i> Total Dana Mushola:</span> <span class="float-end text-primary">Rp ' . number_format($summary_totals['total_mushola'], 0, ',', '.') . '</span></p>';
                        echo '<p class="mb-2"><span class="fw-bold"><i class="fas fa-lightbulb text-danger"></i> Total Kekurangan:</span> <span class="float-end text-danger">Rp ' . number_format($summary_totals['total_minus'], 0, ',', '.') . '</span></p>';
                        echo '<p class="mb-2"><span class="fw-bold"><i class="fas fa-money-bill-wave text-success"></i> Total Bersih Jimpitan/Dansos:</span> <span class="float-end text-success">Rp ' . number_format($total_bersih_jimpitan, 0, ',', '.') . '</span></p>';
                        echo '</div>';

                        echo '</div>';
                        echo '<div class="alert alert-warning mt-3 mb-0">';
                        echo '<i class="fa-solid fa-bullseye text-danger"></i> <b>Kekurangan Jimpitan/Dansos akan diambil dari tabungan warga yang bersangkutan pada akhir periode tabungan.</b>';
                        echo '</div>';
                        echo '</div></div>';
                    }
                } else {
                    echo "<div class='alert alert-info'>Tidak ada data tabungan untuk pencarian ini.</div>";
                }
            }
            ?>
        </div>
    </div>
</div>

<?php include 'core/footer.php'; ?>