<?php
ob_start();
require_once 'koneksi.php';

$tanggal_mulai   = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

if (empty($tanggal_mulai) || empty($tanggal_selesai)) {
    die("Rentang tanggal tidak valid.");
}

$mulai   = mysqli_real_escape_string($koneksi, $tanggal_mulai);
$selesai = mysqli_real_escape_string($koneksi, $tanggal_selesai);

$filename = "Laporan_Penjualan_" . $tanggal_mulai . "_sd_" . $tanggal_selesai . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$query_harian = "SELECT DATE(waktu_transaksi) AS tanggal, SUM(total) AS total_harian
                 FROM transaksi
                 WHERE waktu_transaksi BETWEEN '$mulai' AND '$selesai'
                 GROUP BY DATE(waktu_transaksi)
                 ORDER BY tanggal ASC";
$res_harian = mysqli_query($koneksi, $query_harian);

$query_total = "SELECT COUNT(DISTINCT pelanggan_id) AS jml_pelanggan,
                       COALESCE(SUM(total), 0) AS total_semua
                FROM transaksi
                WHERE waktu_transaksi BETWEEN '$mulai' AND '$selesai'";
$row_total = mysqli_fetch_assoc(mysqli_query($koneksi, $query_total));

$jumlah_pelanggan = (int) $row_total['jml_pelanggan'];
$total_pendapatan = (int) $row_total['total_semua'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
</head>
<body>
    <h3>Rekap Laporan Penjualan (<?= htmlspecialchars($tanggal_mulai) ?> s/d <?= htmlspecialchars($tanggal_selesai) ?>)</h3>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Total Pendapatan</th>
                <th>Tanggal Transaksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($row = mysqli_fetch_assoc($res_harian)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>Rp <?= number_format($row['total_harian'], 0, ',', '.') ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <br>

    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>Jumlah Pelanggan</th>
                <th>Jumlah Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><b><?= $jumlah_pelanggan ?> Orang</b></td>
                <td><b>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></b></td>
            </tr>
        </tbody>
    </table>
</body>
</html>