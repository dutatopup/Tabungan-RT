<?php
// core/functions.php
function generateNamaTabel($bulan, $tahun) {
    $bulanInggris = [
        1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april',
        5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august',
        9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december'
    ];
    
    $bulan = strtolower($bulan);
    $tahun2digit = substr($tahun, -2);
    return "tab_" . $bulanInggris[(int)$bulan] . $tahun2digit;
}

function parseNamaTabel($nama_tabel) {
    $without_prefix = str_replace('tab_', '', strtolower($nama_tabel));
    $bulan = preg_replace('/[0-9]+/', '', $without_prefix);
    $tahun = '20' . preg_replace('/[^0-9]/', '', $without_prefix);
    
    $bulan_map = [
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
        'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
        'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12
    ];
    
    return [
        'bulan_angka' => $bulan_map[$bulan] ?? null,
        'bulan_nama' => $bulan,
        'tahun' => $tahun
    ];
}

function getBulanOptions($conn) {
    $query = "SHOW TABLES LIKE 'tab_%'";
    $result = $conn->query($query);
    $tables = [];
    
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
    }
    
    // Parse semua tabel dan buat array dengan bulan dan tahun
    $bulanTahun = [];
    foreach ($tables as $table) {
        $parsed = parseNamaTabel($table);
        if ($parsed['bulan_angka']) {
            $bulanTahun[] = [
                'nama_tabel' => $table,
                'bulan_angka' => $parsed['bulan_angka'],
                'tahun' => $parsed['tahun'],
                'label' => date('F Y', strtotime($parsed['tahun'] . '-' . $parsed['bulan_angka'] . '-01'))
            ];
        }
    }
    
    // Urutkan berdasarkan tahun dan bulan
    usort($bulanTahun, function($a, $b) {
        if ($a['tahun'] == $b['tahun']) {
            return $a['bulan_angka'] - $b['bulan_angka'];
        }
        return $a['tahun'] - $b['tahun'];
    });
    
    return $bulanTahun;
}
?>