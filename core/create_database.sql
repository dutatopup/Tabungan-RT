-- Dibuat Oleh Agung tri Wahyudi
-- version 5.2.1
-- https://www.phpmyadmin.net/
-- Host: localhost
-- Waktu pembuatan: 06 Apr 2025 pada 00.10
-- Versi server: 8.4.0
-- Versi PHP: 7.4.33

--
-- Struktur dari tabel users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(50) NOT NULL,
    role ENUM('admin', 'user') NOT NULL
);

-- Tambahkan akun admin
INSERT INTO users (username, password, role) VALUES 
('admin', MD5('admin123'), 'admin'),
('user1', MD5('user123'), 'user');


-- Struktur dari tabel warga
CREATE TABLE warga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL
);

-- Struktur dari tabel kas_rt
CREATE TABLE kas_rt (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    masuk INT NOT NULL,
    keluar INT NOT NULL,
    saldo_akhir INT NOT NULL,
    keterangan TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
