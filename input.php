<?php
session_start();
include 'core/koneksi.php';
include 'core/headers.php';

// Cek apakah user sudah login
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit;
}

// Ambil daftar warga dari database
$warga_result = $conn->query("SELECT * FROM warga ORDER BY id ASC");
$warga_list = [];
while ($row = $warga_result->fetch_assoc()) {
    $warga_list[] = $row;
}

// Set tanggal default ke hari ini
$tanggal_hari_ini = date('Y-m-d');

// Inisialisasi variabel pesan
$success_message = '';
$info_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $warga_id = $_POST['warga_id']; // Sekarang menggunakan ID langsung
    $tanggal = $_POST['tanggal'];
    $tabungan = $_POST['tabungan'];

    $bulan = strtolower(date("F", strtotime($tanggal)));
    $tahun = date("y", strtotime($tanggal));
    $nama_tabel = "tab_{$bulan}{$tahun}";

    // Buat tabel dengan struktur baru yang diusulkan
    $conn->query("CREATE TABLE IF NOT EXISTS `$nama_tabel` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        warga_id INT NOT NULL,
        tanggal DATE NOT NULL,
        jumlah INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (warga_id) REFERENCES warga(id) ON DELETE CASCADE,
        INDEX (warga_id),
        INDEX (tanggal)
    )");

    // Cek apakah data sudah ada di tanggal tersebut
    $cek_duplikasi = $conn->query("SELECT id FROM `$nama_tabel` WHERE warga_id = '$warga_id' AND tanggal = '$tanggal'");

    if ($cek_duplikasi->num_rows > 0) {
        // Jika sudah ada, tampilkan notifikasi
        $tabungan_id = $cek_duplikasi->fetch_assoc()['id'];
        $nama_warga = $conn->query("SELECT nama FROM warga WHERE id = '$warga_id'")->fetch_assoc()['nama'];
        echo "<script>
                if (confirm('Data tabungan untuk $nama_warga pada tanggal $tanggal sudah ada. Ingin mengeditnya?')) {
                    window.location.href = 'edit.php?id=$tabungan_id&tabel=$nama_tabel';
                } else {
                    window.location.href = 'input.php';
                }
              </script>";
    } else {
        $nama_warga = $conn->query("SELECT nama FROM warga WHERE id = '$warga_id'")->fetch_assoc()['nama'];
        // Jika belum ada, masukkan data baru
        $conn->query("INSERT INTO `$nama_tabel` (warga_id, tanggal, jumlah) VALUES ('$warga_id', '$tanggal', '$tabungan')");
        $success_message = "Tabungan $nama_warga berhasil disimpan!";
    }
}
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <p class="mb-0"><i class="fas fa-user-edit me-2"></i>Input Tabungan</p>
        </div>
        <div class="card-body">
            <!-- Tampilkan pesan sukses jika ada -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Tampilkan pesan info jika ada -->
            <?php if (!empty($info_message)): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($info_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="nama" class="form-label"><i class="fas fa-user me-1 text-primary"></i> Nama:</label>
                    <input type="text" class="form-control" id="nama" name="nama" required autocomplete="off">
                    <input type="hidden" id="warga_id" name="warga_id"> <!-- Untuk menyimpan ID warga -->
                    <div class="invalid-feedback">Silakan pilih nama warga</div>
                    <div id="nama-error" class="text-danger small d-none">Nama tidak ditemukan</div>
                </div>

                <div class="mb-3">
                    <label for="tanggal" class="form-label"><i class="fas fa-calendar-alt me-1 text-primary"></i> Tanggal:</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= htmlspecialchars($tanggal_hari_ini); ?>" required>
                    <div class="invalid-feedback">Silakan isi tanggal</div>
                </div>

                <div class="mb-3">
                    <label for="tabungan" class="form-label"><i class="fa-solid fa-coins text-primary"></i> Tabungan:</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="tabungan" name="tabungan" required oninput="formatRupiah(this)">
                        <div class="invalid-feedback">Silakan isi jumlah tabungan</div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Bootstrap form validation
    (function() {
        'use strict'

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
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
<script>
$(function() {
    // Autocomplete untuk input nama
    $("#nama").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "cari_warga.php",
                dataType: "json",
                data: {
                    term: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2, // Minimal 2 karakter baru mencari
        select: function(event, ui) {
            // Set nilai ID warga ketika nama dipilih
            $("#warga_id").val(ui.item.id);
            $("#nama-error").addClass("d-none");
        },
        change: function(event, ui) {
            // Validasi jika nilai diubah manual
            if (!ui.item) {
                $("#warga_id").val("");
                $("#nama-error").removeClass("d-none");
            }
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        // Custom tampilan item autocomplete
        return $("<li>")
            .append(`<div>${item.label}</div>`)
            .appendTo(ul);
    };

    // Validasi form
    $("form").submit(function(e) {
        if (!$("#warga_id").val()) {
            e.preventDefault();
            $("#nama").addClass("is-invalid");
            $("#nama-error").removeClass("d-none");
        }
    });
});
</script>

<?php include 'core/footer.php'; ?>
