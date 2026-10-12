<?php

require_once "config/database.php";

$keyword = "";
$result = null;

if (isset($_GET['keyword'])) {

    $keyword = trim($_GET['keyword']);

    $search = "%" . $keyword . "%";

    $stmt = $conn->prepare("
        SELECT *
        FROM riwayat
        WHERE dzikir LIKE ?
        ORDER BY waktu DESC
    ");

    $stmt->bind_param("s", $search);

    $stmt->execute();

    $result = $stmt->get_result();

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Cari Riwayat Dzikir</title>

    <link
        rel="stylesheet"
        href="assets/style.css">

</head>

<body>

<div class="container">

    <h1>Cari Riwayat Dzikir</h1>

    <p class="subtitle">
        Searching riwayat dzikir
    </p>


    <!-- FORM PENCARIAN -->

    <div class="card">

        <form method="GET">

            <input
                type="text"
                name="keyword"
                placeholder="Masukkan nama dzikir..."
                value="<?= htmlspecialchars($keyword) ?>"
                required
            >

            <br><br>

            <button
                type="submit"
                class="btn btn-primary">

                Cari Riwayat

            </button>

        </form>

    </div>


    <!-- HASIL PENCARIAN -->

    <?php if ($result !== null): ?>

        <div class="card">

            <h2>Hasil Pencarian</h2>

            <?php if ($result->num_rows > 0): ?>

                <p>
                    Ditemukan
                    <strong><?= $result->num_rows ?></strong>
                    riwayat.
                </p>

                <table
                    style="
                    width:100%;
                    border-collapse:collapse;
                    "
                >

                    <thead>

                        <tr>

                            <th style="padding:10px;">
                                No
                            </th>

                            <th style="padding:10px;">
                                Dzikir
                            </th>

                            <th style="padding:10px;">
                                Jumlah
                            </th>

                            <th style="padding:10px;">
                                Waktu
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php

                    $no = 1;

                    // ==================================
                    // TRAVERSAL HASIL SEARCHING
                    // ==================================

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
                                <?= $row['jumlah'] ?> kali
                            </td>

                            <td style="padding:10px;">
                                <?= $row['waktu'] ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <p>
                    ❌ Riwayat dzikir
                    <strong>
                        <?= htmlspecialchars($keyword) ?>
                    </strong>
                    tidak ditemukan.
                </p>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- MENU -->

    <div class="menu">

        <a href="index.php">
            ← Tasbih
        </a>

        <a href="riwayat.php">
            Riwayat
        </a>

        <a href="queue.php">
            Antrean
        </a>

    </div>

</div>

</body>

</html>