<?php
require_once 'koneksi.php';

$tanggal_mulai   = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

$labels           = [];
$data_pendapatan  = [];
$rekap            = [];
$jumlah_pelanggan = 0;
$total_pendapatan = 0;

if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
    $mulai   = mysqli_real_escape_string($koneksi, $tanggal_mulai);
    $selesai = mysqli_real_escape_string($koneksi, $tanggal_selesai);

    $query_harian = "SELECT DATE(waktu_transaksi) AS tanggal, SUM(total) AS total_harian
                     FROM transaksi
                     WHERE waktu_transaksi BETWEEN '$mulai' AND '$selesai'
                     GROUP BY DATE(waktu_transaksi)
                     ORDER BY tanggal ASC";
    $res_harian = mysqli_query($koneksi, $query_harian);

    while ($row = mysqli_fetch_assoc($res_harian)) {
        $labels[]          = $row['tanggal'];
        $data_pendapatan[] = (int) $row['total_harian'];
        $rekap[]           = $row;
    }

    $query_total = "SELECT COUNT(DISTINCT pelanggan_id) AS jml_pelanggan,
                           COALESCE(SUM(total), 0) AS total_semua
                    FROM transaksi
                    WHERE waktu_transaksi BETWEEN '$mulai' AND '$selesai'";
    $row_total = mysqli_fetch_assoc(mysqli_query($koneksi, $query_total));

    $jumlah_pelanggan = (int) $row_total['jml_pelanggan'];
    $total_pendapatan = (int) $row_total['total_semua'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <h2>Filter Laporan Penjualan</h2>
        <form method="GET" action="">
            <label>Dari:</label>
            <input type="date" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai) ?>" required>
            <label>Sampai:</label>
            <input type="date" name="tanggal_selesai" value="<?= htmlspecialchars($tanggal_selesai) ?>" required>
            <button type="submit">Tampilkan</button>
        </form>
    </div>

    <?php if (!empty($tanggal_mulai) && !empty($tanggal_selesai)): ?>
        <?php if (!empty($rekap)): ?>

            <br class="no-print">
            <div class="no-print">
                <button type="button" onclick="window.print()">Cetak</button>
                <button type="button" onclick="window.location.href='export.php?tanggal_mulai=<?= urlencode($tanggal_mulai) ?>&tanggal_selesai=<?= urlencode($tanggal_selesai) ?>'">Excel</button>
            </div>
            <h3>Grafik Penjualan Harian (<?= htmlspecialchars($tanggal_mulai) ?> s/d <?= htmlspecialchars($tanggal_selesai) ?>)</h3>
            <div style="width: 600px; height: 300px;">
                <canvas id="grafikPenjualan"></canvas>
            </div>

            <script>
                const ctx = document.getElementById('grafikPenjualan').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: [{
                            label: 'Total Pendapatan (Rp)',
                            data: <?= json_encode($data_pendapatan) ?>,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            x: { title: { display: true, text: 'Tanggal Transaksi' } },
                            y: { beginAtZero: true, title: { display: true, text: 'Total Pendapatan' } }
                        }
                    }
                });
            </script>

            <h3>Tabel Rekapitulasi Penjualan</h3>
            <table border="1" cellpadding="8" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Total Pendapatan</th>
                        <th>Tanggal Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($rekap as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>Rp <?= number_format($row['total_harian'], 0, ',', '.') ?></td>
                            <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
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

        <?php else: ?>
            <p>Tidak ada transaksi pada rentang tanggal tersebut.</p>
        <?php endif; ?>
    <?php endif; ?>

</body>
</html>