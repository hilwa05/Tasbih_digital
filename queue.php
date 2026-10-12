<?php

session_start();

require_once "config/database.php";

// ==========================================
// INISIALISASI SESSION
// ==========================================

if (!isset($_SESSION['stack'])) {
    $_SESSION['stack'] = [];
}

if (!isset($_SESSION['jumlah'])) {
    $_SESSION['jumlah'] = 0;
}

if (!isset($_SESSION['dzikir'])) {
    $_SESSION['dzikir'] = "Subhanallah";
}

if (!isset($_SESSION['target_selesai'])) {
    $_SESSION['target_selesai'] = false;
}


// ==========================================
// AKTIFKAN ANTREAN
// ==========================================

function aktifkanAntrean($data)
{
    $_SESSION['queue_id'] = (int) $data['id'];
    $_SESSION['dzikir'] = $data['dzikir'];
    $_SESSION['target'] = (int) $data['target'];

    // Mulai hitungan baru
    $_SESSION['jumlah'] = 0;
    $_SESSION['stack'] = [];

    // Target antrean baru belum selesai
    $_SESSION['target_selesai'] = false;
}


// ==========================================
// AMBIL ANTREAN MENUNGGU TERDEPAN
// ==========================================

function aktifkanAntreanBerikutnya($conn)
{
    $stmt = $conn->prepare("
        SELECT id, dzikir, target
        FROM queue_dzikir
        WHERE status = 'menunggu'
        ORDER BY id ASC
        LIMIT 1
    ");

    if (!$stmt || !$stmt->execute()) {
        if ($stmt) {
            $stmt->close();
        }

        return false;
    }

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if (!$data) {
        return false;
    }

    // Hanya aktifkan jika status masih menunggu
    $stmt = $conn->prepare("
        UPDATE queue_dzikir
        SET status = 'aktif'
        WHERE id = ?
          AND status = 'menunggu'
    ");

    if (!$stmt) {
        return false;
    }

    $id = (int) $data['id'];

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $berhasil = ($stmt->affected_rows === 1);

    $stmt->close();

    if (!$berhasil) {
        return false;
    }

    aktifkanAntrean($data);

    return true;
}


// ==========================================
// SINKRONISASI ANTREAN AKTIF
// ==========================================

// Pulihkan sesi apabila antrean sudah aktif
// di database tetapi belum tercatat di session.

if (!isset($_SESSION['queue_id'])) {

    $stmt = $conn->prepare("
        SELECT id, dzikir, target
        FROM queue_dzikir
        WHERE status = 'aktif'
        ORDER BY id ASC
        LIMIT 1
    ");

    if ($stmt && $stmt->execute()) {

        $result = $stmt->get_result();
        $data_aktif = $result->fetch_assoc();

        if ($data_aktif) {
            aktifkanAntrean($data_aktif);
        }

        $stmt->close();
    }
}


// ==========================================
// TAMBAH ANTREAN
// ==========================================

if (isset($_POST['tambah_queue'])) {

    $dzikir = trim($_POST['dzikir'] ?? '');
    $target = filter_var(
        $_POST['target'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($dzikir !== '' && $target !== false && $target > 0) {

        $stmt = $conn->prepare("
            INSERT INTO queue_dzikir (dzikir, target)
            VALUES (?, ?)
        ");

        if ($stmt) {

            $stmt->bind_param("si", $dzikir, $target);

            if ($stmt->execute()) {
                $_SESSION['pesan'] =
                    "Antrean dzikir berhasil ditambahkan.";
            } else {
                $_SESSION['pesan'] =
                    "Gagal menambahkan antrean dzikir.";
            }

            $stmt->close();

        } else {

            $_SESSION['pesan'] =
                "Gagal menyiapkan penambahan antrean.";
        }

    } else {

        $_SESSION['pesan'] =
            "Nama dzikir dan target harus diisi dengan benar.";
    }

    header("Location: queue.php");
    exit;
}


// ==========================================
// PILIH ANTREAN TERDEPAN
// ==========================================

if (isset($_POST['pilih'])) {

    // Jangan mengganti antrean yang masih aktif
    if (isset($_SESSION['queue_id'])) {

        $_SESSION['pesan'] =
            "Selesaikan antrean aktif terlebih dahulu.";

        header("Location: queue.php");
        exit;
    }

    $berhasil = aktifkanAntreanBerikutnya($conn);

    if ($berhasil) {

        $_SESSION['pesan'] =
            "Antrean berhasil diaktifkan.";

        header("Location: index.php");
        exit;

    } else {

        $_SESSION['pesan'] =
            "Tidak ada antrean menunggu yang dapat diaktifkan.";

        header("Location: queue.php");
        exit;
    }
}


// ==========================================
// SELESAIKAN ANTREAN SECARA MANUAL
// ==========================================

if (isset($_POST['selesai'])) {

    if (isset($_SESSION['queue_id'])) {

        $id = (int) $_SESSION['queue_id'];

        $stmt = $conn->prepare("
            UPDATE queue_dzikir
            SET status = 'selesai'
            WHERE id = ?
              AND status = 'aktif'
        ");

        $berhasil = false;

        if ($stmt) {

            $stmt->bind_param("i", $id);
            $stmt->execute();

            $berhasil = ($stmt->affected_rows === 1);

            $stmt->close();
        }

        if ($berhasil) {

            // Lepaskan antrean lama
            unset($_SESSION['queue_id']);

            // Siapkan penghitung
            $_SESSION['jumlah'] = 0;
            $_SESSION['stack'] = [];

            // Tandai sesi lama selesai sementara
            $_SESSION['target_selesai'] = true;

            // Langsung aktifkan antrean berikutnya
            if (aktifkanAntreanBerikutnya($conn)) {

                $_SESSION['pesan'] =
                    "Antrean selesai. Antrean berikutnya telah diaktifkan.";

                header("Location: index.php");
                exit;

            } else {

                $_SESSION['pesan'] =
                    "Antrean selesai. Tidak ada antrean berikutnya.";

                header("Location: queue.php");
                exit;
            }

        } else {

            $_SESSION['pesan'] =
                "Antrean gagal diselesaikan atau statusnya sudah berubah.";
        }

    } else {

        $_SESSION['pesan'] =
            "Tidak ada antrean aktif.";
    }

    header("Location: queue.php");
    exit;
}


// ==========================================
// AMBIL DAFTAR ANTREAN MENUNGGU
// ==========================================

$stmt = $conn->prepare("
    SELECT id, dzikir, target, status
    FROM queue_dzikir
    WHERE status = 'menunggu'
    ORDER BY id ASC
");

$stmt->execute();

$result = $stmt->get_result();


// ==========================================
// TAMPILAN HALAMAN
// ==========================================

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Antrean Dzikir</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>

<div class="container">

    <h1>Antrean Dzikir</h1>

    <p class="subtitle">
        Queue — FIFO (First In, First Out)
    </p>


    <?php if (!empty($_SESSION['pesan'])): ?>

        <div class="card">
            <p>
                <?= htmlspecialchars(
                    $_SESSION['pesan'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        </div>

        <?php unset($_SESSION['pesan']); ?>

    <?php endif; ?>


    <!-- FORM TAMBAH ANTREAN -->

    <div class="card">

        <h2>Tambah Target Dzikir</h2>

        <form method="POST" action="queue.php">

            <input
                type="text"
                name="dzikir"
                placeholder="Contoh: Tahmid"
                maxlength="255"
                required
            >

            <br><br>

            <input
                type="number"
                name="target"
                placeholder="Target"
                min="1"
                required
            >

            <br><br>

            <button
                type="submit"
                name="tambah_queue"
                class="btn btn-primary"
            >
                + Tambah Antrean
            </button>

        </form>

    </div>


    <!-- DAFTAR ANTREAN -->

    <div class="card">

        <h2>Daftar Antrean Menunggu</h2>

        <?php if ($result->num_rows > 0): ?>

            <?php
            $no = 1;
            $antrean_pertama = true;
            ?>

            <?php while ($row = $result->fetch_assoc()): ?>

                <div style="
                    padding: 15px;
                    margin-bottom: 10px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                ">

                    <strong>
                        <?= $no++ ?>.
                        <?= htmlspecialchars(
                            $row['dzikir'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>

                    <br>

                    Target:
                    <?= (int) $row['target'] ?>

                    <br>

                    Status:
                    <?= htmlspecialchars(
                        $row['status'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>


                    <?php if (
                        $antrean_pertama &&
                        !isset($_SESSION['queue_id'])
                    ): ?>

                        <br><br>

                        <form method="POST" action="queue.php">

                            <button
                                type="submit"
                                name="pilih"
                                class="btn btn-primary"
                            >
                                ▶ Pilih Antrean Ini
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

                <?php $antrean_pertama = false; ?>

            <?php endwhile; ?>

        <?php else: ?>

            <p>Antrean menunggu masih kosong.</p>

        <?php endif; ?>

        <?php $stmt->close(); ?>

    </div>


    <!-- ANTREAN AKTIF -->

    <?php if (isset($_SESSION['queue_id'])): ?>

        <div class="card">

            <h2>Dzikir Sedang Aktif</h2>

            <h3>
                <?= htmlspecialchars(
                    $_SESSION['dzikir'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h3>

            <p>
                Target:
                <?= (int) ($_SESSION['target'] ?? 0) ?>
            </p>

            <p>
                Hitungan saat ini:
                <?= (int) $_SESSION['jumlah'] ?>
            </p>

            <form method="POST" action="queue.php">

                <button
                    type="submit"
                    name="selesai"
                    class="btn btn-primary"
                    onclick="return confirm(
                        'Selesaikan antrean ini dan lanjutkan ke antrean berikutnya?'
                    );"
                >
                    ✓ Selesaikan Dzikir
                </button>

            </form>

        </div>

    <?php endif; ?>


    <!-- MENU -->

    <div class="menu">

        <a href="index.php">
            ← Kembali ke Tasbih
        </a>

        <a href="riwayat.php">
            Riwayat
        </a>

        <a href="search.php">
            Cari Riwayat
        </a>

    </div>

</div>

</body>
</html>
```