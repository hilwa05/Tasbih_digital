<?php

require_once "config/database.php";

// Ambil semua riwayat
$query = "SELECT * FROM riwayat ORDER BY waktu DESC";

$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Dzikir</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="container">

    <h1>Riwayat Dzikir</h1>

    <p class="subtitle">
        Menampilkan seluruh riwayat sesi dzikir
    </p>

    <div class="card">

        <?php if ($result->num_rows > 0): ?>

            <table style="width:100%; border-collapse:collapse;">

                <thead>

                    <tr>
                        <th style="padding:10px;">No</th>
                        <th style="padding:10px;">Dzikir</th>
                        <th style="padding:10px;">Jumlah</th>
                        <th style="padding:10px;">Waktu</th>
                    </tr>

                </thead>

                <tbody>

                <?php

                $no = 1;

                // ===================================
                // TRAVERSAL
                // ===================================

                foreach ($result as $row):

                ?>

                    <tr>

                        <td style="padding:10px;">
                            <?= $no++ ?>
                        </td>

                        <td style="padding:10px;">
                            <?= htmlspecialchars($row['dzikir']) ?>
                        </td>

                        <td style="padding:10px;">
                            <?= $row['jumlah'] ?>
                        </td>

                        <td style="padding:10px;">
                            <?= $row['waktu'] ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <p>Belum ada riwayat dzikir.</p>

        <?php endif; ?>

    </div>

    <div class="menu">

        <a href="index.php">← Kembali ke Tasbih</a>

        <a href="queue.php">Antrean Dzikir</a>

        <a href="search.php">Cari Riwayat</a>

    </div>

</div>

</body>

</html>