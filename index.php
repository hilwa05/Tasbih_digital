<?php

session_start();


// ==========================================
// DEFAULT SESSION
// ==========================================

if (!isset($_SESSION['dzikir'])) {
    $_SESSION['dzikir'] = "Subhanallah";
}

if (!isset($_SESSION['jumlah'])) {
    $_SESSION['jumlah'] = 0;
}

if (!isset($_SESSION['stack'])) {
    $_SESSION['stack'] = [];
}

if (!isset($_SESSION['target_selesai'])) {
    $_SESSION['target_selesai'] = false;
}


// ==========================================
// AMBIL DATA SESSION
// ==========================================

$dzikir = $_SESSION['dzikir'];

$jumlah = (int) $_SESSION['jumlah'];

$target = $_SESSION['target'] ?? null;

$target_selesai = (bool) $_SESSION['target_selesai'];


// ==========================================
// PESAN NOTIFIKASI
// ==========================================

$pesan = $_SESSION['pesan'] ?? "";

unset($_SESSION['pesan']);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Tasbih Digital by Kelompok 2</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>

<div class="container">

    <h1>Tasbih Digital by Kelompok 2</h1>

    <p class="subtitle">
        Dzikir, Target, Riwayat
    </p>


    <!-- =====================================
         CARD TASBIH
    ====================================== -->

    <div class="card">

        <h2>
            <?= htmlspecialchars(
                $dzikir,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h2>


        <!-- TARGET DAN PROGRESS -->

        <?php if ($target !== null && (int) $target > 0): ?>

            <p>
                Target:
                <strong>
                    <?= (int) $target ?>
                </strong>
            </p>

            <p>
                Progress:
                <strong>
                    <?= $jumlah ?> / <?= (int) $target ?>
                </strong>
            </p>

        <?php endif; ?>


        <!-- COUNTER -->

        <div class="counter">
            <?= $jumlah ?>
        </div>


        <!-- =================================
             TOMBOL TAMBAH
        ================================== -->

        <?php if ($target_selesai): ?>

            <button
                type="button"
                class="btn btn-primary"
                disabled
                style="
                    opacity: 0.5;
                    cursor: not-allowed;
                "
            >
                ✓ Target Tercapai
            </button>

        <?php else: ?>

            <form
                action="proses.php"
                method="POST"
            >

                <button
                    type="submit"
                    name="aksi"
                    value="tambah"
                    class="btn btn-primary"
                >
                    +1 Dzikir
                </button>

            </form>

        <?php endif; ?>


        <!-- =================================
             TOMBOL UNDO DAN RESET
        ================================== -->

        <div class="actions">

            <?php if (!$target_selesai): ?>

                <form
                    action="proses.php"
                    method="POST"
                >

                    <button
                        type="submit"
                        name="aksi"
                        value="undo"
                        class="btn btn-warning"
                    >
                        Undo
                    </button>

                </form>

            <?php endif; ?>


            <form
                action="proses.php"
                method="POST"
            >

                <button
                    type="submit"
                    name="aksi"
                    value="reset"
                    class="btn btn-danger"
                >
                    Reset
                </button>

            </form>

        </div>


        <!-- =================================
             PESAN TARGET SELESAI
        ================================== -->

        <?php if ($target_selesai): ?>

            <div style="
                margin-top: 25px;
                padding: 15px;
                border-radius: 10px;
                background: #dcfce7;
                color: #166534;
                font-weight: bold;
            ">

                ✓ Alhamdulillah, target dzikir
                telah tercapai!

            </div>

        <?php endif; ?>

    </div>


    <!-- =====================================
         MENU
    ====================================== -->

    <div class="menu">

        <a href="queue.php">
            Antrean Dzikir
        </a>

        <a href="riwayat.php">
            Riwayat Dzikir
        </a>

        <a href="search.php">
            Cari Riwayat
        </a>

    </div>

</div>


<!-- =========================================
     NOTIFIKASI
========================================== -->

<?php if ($pesan !== ''): ?>

<script>
    alert(<?= json_encode(
        $pesan,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    ) ?>);
</script>

<?php endif; ?>


</body>
</html>
```