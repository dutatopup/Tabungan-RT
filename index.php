<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<title>Jadwal Shalat dan Imsakiyah</title>
	<link rel="stylesheet" href="assets/style_jadwal.css" type="text/css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
	<?php
	// Tanggal Sekarang
	$tgl_url = "https://api.myquran.com/v2/cal/hijr/?adj=-1";

	// Ambil data JSON dari API
	$response = file_get_contents($tgl_url);
	$data = json_decode($response, true); // Decode JSON ke array PHP

	// Ambil data yang diperlukan
	$hari = $data['data']['date'][0];         // Hari dalam bahasa Indonesia
	$tanggal_masehi_raw = $data['data']['date'][2]; // Tanggal Masehi (format awal: 01-04-2025)
	$tanggal_hijriah = $data['data']['date'][1];    // Tanggal Hijriah

	// Ubah format tanggal Masehi menjadi "1 April 2025"
	$dateObj = DateTime::createFromFormat('d-m-Y', $tanggal_masehi_raw);
	$tanggal_masehi = $dateObj->format('j F Y'); // Format tanpa leading zero

	// Tampilkan hasil

	?>
	<center>
		<h2>Jadwal Shalat dan Imsakiyah</h2>
		<h3><?php echo "<span style='color: blue;'>$hari, $tanggal_masehi</span> | <span style='color: green;'>$tanggal_hijriah</span>"; ?></h3>
	</center>


	<?php // Jadwal Sholat

	$api_url = 'https://api.myquran.com/v2/sholat/kota/semua';

	// membaca JSON dari url
	$kota = file_get_contents($api_url);

	// Decode data JSON data menjadi array PHP
	$response_kota = json_decode($kota);

	// Mengakses data yang ada dalam object 'data'
	$list_kota = $response_kota->data;

	if (isset($_GET['kota'])) {
		$kota_terpilih = $_GET['kota'];
	} else {
		$kota_terpilih = '1407';
	}

	$tahun = date("Y"); // Ambil tahun saat ini
	$bulan = date("n"); // Ambil bulan saat ini (tanpa leading zero)

	?>

	<center>
		<form method="get" action="">
			<select name="kota" onchange="this.form.submit()">
				<?php
				foreach ($list_kota as $k) {
				?>
					<option <?php if ($kota_terpilih == $k->id) {
								echo "selected='selected'";
							} ?> value="<?php echo $k->id ?>"><?php echo $k->lokasi ?></option>
				<?php
				}
				?>

			</select>
		</form>
	</center>

	<br>

	<div class="kotak">
		<div class="imsakiyah">

			<table>
				<tr>
					<th width="200px">Tanggal</th>
					<th>Imsak</th>
					<th>Subuh</th>
					<th>Dzuhur</th>
					<th>Ashar</th>
					<th>Maghrib</th>
					<th>Isya</th>
				</tr>
				<?php

				// tentukan bulan puasa
				$api_url = 'https://api.myquran.com/v2/sholat/jadwal/' . $kota_terpilih . '/' . $tahun . '/' . $bulan;

				// membaca JSON dari url
				$json_data = file_get_contents($api_url);

				// Decode data JSON data menjadi array PHP
				$response_data = json_decode($json_data);

				// Mengakses data yang ada dalam object 'data'
				$jadwal_shalat = $response_data->data;

				foreach ($jadwal_shalat->jadwal as $jadwal) {
				?>
					<tr>
						<th><?php echo $jadwal->tanggal; ?></th>
						<td><?php echo $jadwal->imsak; ?></td>
						<td><?php echo $jadwal->subuh; ?></td>
						<td><?php echo $jadwal->dzuhur; ?></td>
						<td><?php echo $jadwal->ashar; ?></td>
						<td><?php echo $jadwal->maghrib; ?></td>
						<td><?php echo $jadwal->isya; ?></td>
					</tr>
				<?php
				}
				?>
			</table>
		</div>

	</div>

</body>

</html>