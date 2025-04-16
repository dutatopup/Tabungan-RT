<?php
session_start();
include 'core/koneksi.php';
include 'core/headers.php';

// Define constants for maintenance values
define('JIMPITAN_PER_HARI', 500);
define('DANA_MUSHOLA_PER_HARI', 500);
define('DANSOS_PER_BULAN', 1000);

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil role user dari session
$role = $_SESSION['role'];

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Pastikan tidak kurang dari 1
$offset = ($page - 1) * $records_per_page;

// Tahun default: 2025 (tahun pertama program)
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : 25;
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-abu-abu text-white">
            <p class="mb-0"><i class="fas fa-book me-2"></i>Rekap Tabungan Tahunan</p>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-8">
                    <form method="GET" class="row g-3 align-items-end"> <!-- Ubah dari align-items-center ke align-items-end -->
                        <div class="col-md-4">
                            <label for="tahun_awal" class="form-label">Tahun Awal</label>
                            <select name="tahun_awal" id="tahun_awal" class="form-select" disabled>
                                <option value="25" selected>2025</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="tahun" class="form-label">Tahun Akhir</label>
                            <select name="tahun" id="tahun" class="form-select">
                                <option value="25" <?php echo ($tahun == 25) ? 'selected' : ''; ?>>2025</option>
                                <option value="26" <?php echo ($tahun == 26) ? 'selected' : ''; ?>>2026</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <input type="hidden" name="page" value="1">
                            <button type="submit" class="btn btn-primary w-70 mt-2"> <!-- Tambah w-100 dan mt-2 -->
                                <i class="fas fa-eye me-1"></i> Tampilkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            // Hitung total hari dalam setahun (365 atau 366)
            $total_hari = date('L', strtotime("20$tahun-01-01")) ? 366 : 365;

            // Ambil daftar semua tabel untuk tahun ini
            if ($tahun == 25) {
                // Jika tahun 2025, hanya ambil tabel 2025
                $tables_query = "SHOW TABLES LIKE 'tab_%25'";
            } else {
                // Jika tahun 2026, ambil tabel 2025 dan 2026
                $tables_query = "SHOW TABLES WHERE Tables_in_" . $db . " LIKE 'tab_%25' OR Tables_in_" . $db . " LIKE 'tab_%26'";
            }

            $tables_result = $conn->query($tables_query);
            $tables = array();
            if ($tables_result) {
                while ($row = $tables_result->fetch_array()) {
                    $tables[] = $row[0];
                }
            }

            // Hitung total warga
            $total_records = $conn->query("SELECT COUNT(*) FROM warga")->fetch_row()[0];
            $total_pages = ceil($total_records / $records_per_page);

            // Hitung grand total dari semua data (tanpa pagination)
            $grand_total_tabungan = 0;
            $grand_total_jimpitan = 0;
            $grand_total_mushola = 0;
            $grand_total_potongan = 0;
            $grand_total_akhir = 0;

            // Query untuk menghitung grand total
            $sql_all_warga = "SELECT id, nama FROM warga ORDER BY id ASC";
            $result_all_warga = $conn->query($sql_all_warga);

            if ($result_all_warga && $result_all_warga->num_rows > 0) {
                while ($row_warga = $result_all_warga->fetch_assoc()) {
                    $warga_id = $row_warga['id'];
                    $total_tabungan = 0;

                    // Hitung total tabungan dari semua tabel bulanan
                    foreach ($tables as $table) {
                        $sql_total = "SELECT COALESCE(SUM(jumlah), 0) AS total FROM `$table` WHERE warga_id = '$warga_id'";
                        $result_total = $conn->query($sql_total);
                        if ($result_total) {
                            $row_total = $result_total->fetch_assoc();
                            $total_tabungan += $row_total['total'];
                        }
                    }

                    // Hitung Jimpitan/Dansos menggunakan konstanta
                    $jimpitan_dansos = ($total_hari * JIMPITAN_PER_HARI) + (12 * DANSOS_PER_BULAN);

                    // Hitung Dana Mushola menggunakan konstanta
                    $dana_mushola = $total_hari * DANA_MUSHOLA_PER_HARI;

                    // Hitung Total Potongan
                    $total_potongan = $jimpitan_dansos + $dana_mushola;

                    // Hitung Sisa Tabungan
                    $sisa_tabungan = $total_tabungan - $total_potongan;

                    // Hitung Potongan 1% dari sisa tabungan
                    $potongan_1persen = $sisa_tabungan > 0 ? $sisa_tabungan * 0.01 : 0;

                    // Hasil Akhir setelah semua potongan
                    $hasil_akhir = $sisa_tabungan - $potongan_1persen;

                    // Akumulasi grand total
                    $grand_total_tabungan += $total_tabungan;
                    $grand_total_jimpitan += $jimpitan_dansos;
                    $grand_total_mushola += $dana_mushola;
                    $grand_total_potongan += $potongan_1persen;
                    $grand_total_akhir += $hasil_akhir;
                }
            }

            // Ambil daftar semua warga dengan pagination
            $sql_warga = "SELECT id, nama FROM warga ORDER BY id ASC LIMIT $offset, $records_per_page";
            $result_warga = $conn->query($sql_warga);

            if ($result_warga && $result_warga->num_rows > 0) {
                echo '<div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th>Nama</th>
                                    <th width="15%" class="text-center">Tabungan</th>
                                    <th width="15%" class="text-center">Jimpitan/Dansos</th>
                                    <th width="15%" class="text-center">Dana Mushola</th>
                                    <th width="15%" class="text-center">Potongan 1%</th>
                                    <th width="15%" class="text-center">Total Didapat</th>
                                </tr>
                            </thead>
                            <tbody>';

                $no = $offset + 1;
                $page_total_tabungan = 0;
                $page_total_jimpitan = 0;
                $page_total_mushola = 0;
                $page_total_potongan = 0;
                $page_total_akhir = 0;

                while ($row_warga = $result_warga->fetch_assoc()) {
                    $warga_id = $row_warga['id'];
                    $nama = $row_warga['nama'];
                    $total_tabungan = 0;

                    // Hitung total tabungan dari semua tabel bulanan
                    foreach ($tables as $table) {
                        $sql_total = "SELECT COALESCE(SUM(jumlah), 0) AS total FROM `$table` WHERE warga_id = '$warga_id'";
                        $result_total = $conn->query($sql_total);
                        if ($result_total) {
                            $row_total = $result_total->fetch_assoc();
                            $total_tabungan += $row_total['total'];
                        }
                    }

                    // Hitung Jimpitan/Dansos menggunakan konstanta
                    $jimpitan_dansos = ($total_hari * JIMPITAN_PER_HARI) + (12 * DANSOS_PER_BULAN);

                    // Hitung Dana Mushola menggunakan konstanta
                    $dana_mushola = $total_hari * DANA_MUSHOLA_PER_HARI;

                    // Hitung Total Potongan
                    $total_potongan = $jimpitan_dansos + $dana_mushola;

                    // Hitung Sisa Tabungan
                    $sisa_tabungan = $total_tabungan - $total_potongan;

                    // Hitung Potongan 1% dari sisa tabungan
                    $potongan_1persen = $sisa_tabungan > 0 ? $sisa_tabungan * 0.01 : 0;

                    // Hasil Akhir setelah semua potongan
                    $hasil_akhir = $sisa_tabungan - $potongan_1persen;

                    // Akumulasi total halaman
                    $page_total_tabungan += $total_tabungan;
                    $page_total_jimpitan += $jimpitan_dansos;
                    $page_total_mushola += $dana_mushola;
                    $page_total_potongan += $potongan_1persen;
                    $page_total_akhir += $hasil_akhir;

                    echo "<tr>
                            <td class='text-center'>{$no}</td>
                            <td>" . htmlspecialchars($nama) . "</td>
                            <td class='text-end'>Rp " . number_format($total_tabungan, 0, ',', '.') . "</td>
                            <td class='text-end'>Rp " . number_format($jimpitan_dansos, 0, ',', '.') . "</td>
                            <td class='text-end'>Rp " . number_format($dana_mushola, 0, ',', '.') . "</td>
                            <td class='text-end'>Rp " . number_format($potongan_1persen, 0, ',', '.') . "</td>
                            <td class='text-end " . ($hasil_akhir < 0 ? "text-danger fw-bold" : "") . "'>Rp " . number_format($hasil_akhir, 0, ',', '.') . "</td>
                          </tr>";
                    $no++;
                }

                // Baris Grand Total
                echo '<tr class="table-secondary">
                        <th colspan="2" class="text-center">GRAND TOTAL</th>
                        <th class="text-end">Rp ' . number_format($grand_total_tabungan, 0, ',', '.') . '</th>
                        <th class="text-end">Rp ' . number_format($grand_total_jimpitan, 0, ',', '.') . '</th>
                        <th class="text-end">Rp ' . number_format($grand_total_mushola, 0, ',', '.') . '</th>
                        <th class="text-end">Rp ' . number_format($grand_total_potongan, 0, ',', '.') . '</th>
                        <th class="text-end">Rp ' . number_format($grand_total_akhir, 0, ',', '.') . '</th>
                      </tr>';

                echo '</tbody></table></div>';

                echo '<div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>Ini adalah perhitungan semi total karena belum dihitung dari uang keluar (pengambilan dana pribadi)
                      </div>';

                // Pagination
                echo '<nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-2">';
                if ($page > 1) {
                    echo '<li class="page-item">
                            <a class="page-link" href="?tahun=' . $tahun . '&page=1" aria-label="First">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                          </li>
                          <li class="page-item">
                            <a class="page-link" href="?tahun=' . $tahun . '&page=' . ($page - 1) . '" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                          </li>';
                }

                // Tampilkan beberapa halaman sekitar halaman saat ini
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                            <a class="page-link" href="?tahun=' . $tahun . '&page=' . $i . '">' . $i . '</a>
                          </li>';
                }

                if ($page < $total_pages) {
                    echo '<li class="page-item">
                            <a class="page-link" href="?tahun=' . $tahun . '&page=' . ($page + 1) . '" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                          </li>
                          <li class="page-item">
                            <a class="page-link" href="?tahun=' . $tahun . '&page=' . $total_pages . '" aria-label="Last">
                                <span aria-hidden="true">&raquo;&raquo;</span>
                            </a>
                          </li>';
                }

                echo '</ul></nav>';

                echo '<div class="text-center text-muted">
                        <i class="fas fa-file-alt me-1"></i> Halaman ' . $page . ' dari ' . $total_pages . ' | 
                        <i class="fas fa-database me-1"></i> Total Data: ' . $total_records . '
                      </div>';
            } else {
                echo '<div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>Tidak ada data warga.
                      </div>';
            }
            ?>
        </div>
    </div>
</div>

<?php include 'core/footer.php'; ?>
