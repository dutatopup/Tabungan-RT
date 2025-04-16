<?php
include 'core/koneksi.php';

$term = $_GET['term'] ?? '';

$query = $conn->prepare("SELECT id, nama FROM warga WHERE nama LIKE ? ORDER BY nama ASC");
$searchTerm = '%' . $term . '%';
$query->bind_param("s", $searchTerm);
$query->execute();
$result = $query->get_result();

$response = [];
while ($row = $result->fetch_assoc()) {
    $response[] = [
        'id' => $row['id'],
        'label' => $row['nama'],
        'value' => $row['nama']
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>