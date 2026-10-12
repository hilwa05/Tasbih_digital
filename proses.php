<?php

session_start();
require_once "config/database.php";


// ==========================================
// INISIALISASI SESSION
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

$aksi = $_POST['aksi'] ?? '';


// ==========================================
// FUNGSI AKTIFKAN ANTREAN BERIKUTNYA
// ==========================================

function aktifkanBerikutnya($conn)
{
    $stmt = $conn->prepare("
        SELECT id, dzikir, target
        FROM queue_dzikir
        WHERE status = 'menunggu'
        ORDER BY id ASC
        LIMIT 1
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception("Gagal mengambil antrean berikutnya.");
    }

    $stmt->execute();
    $stmt->bind_result($id, $dzikir, $target);

    $adaAntrean = $stmt->fetch();
    $stmt->close();

    if (!$adaAntrean) {
        unset($_SESSION['queue_id']);
        return false;
    }

    $stmt = $conn->prepare("
        UPDATE queue_dzikir
        SET status = 'aktif'
        WHERE id = ?
          AND status = 'menunggu'
    ");

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan antrean.");
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new Exception("Antrean berikutnya gagal diaktifkan.");
    }

    $stmt->close();

    // Perbarui session ke antrean baru
    $_SESSION['queue_id'] = (int) $id;
    $_SESSION['dzikir'] = $dzikir;
    $_SESSION['target'] = (int) $target;

    // Mulai hitungan baru
    $_SESSION['jumlah'] = 0;
    $_SESSION['stack'] = [];
    $_SESSION['target_selesai'] = false;

    return true;
}


// ==========================================
// TAMBAH HITUNGAN
// ==========================================

if ($aksi === 'tambah') {

    if ($_SESSION['target_selesai'] === true) {

        $_SESSION['pesan'] =
            "Target sudah selesai. Silakan pilih atau mulai sesi berikutnya.";

        header("Location: index.php");
        exit;
    }

    // Simpan nilai sebelumnya untuk Undo
    $_SESSION['stack'][] = (int) $_SESSION['jumlah'];

    // Tambah hitungan
    $_SESSION['jumlah']++;

    $target = isset($_SESSION['target'])
        ? (int) $_SESSION['target']
        : 0;

    // Jika belum ada target atau target belum tercapai,
    // cukup perbarui hitungan.
    if ($target <= 0 || $_SESSION['jumlah'] < $target) {
        header("Location: index.php");
        exit;
    }

    // ======================================
    // TARGET TERCAPAI
    // ======================================

    $dzikir = $_SESSION['dzikir'];
    $jumlah = (int) $_SESSION['jumlah'];

    $adaAntreanAktif = isset($_SESSION['queue_id']);

    try {

        $conn->begin_transaction();

        // Simpan riwayat sesi yang selesai
        $stmt = $conn->prepare("
            INSERT INTO riwayat (dzikir, jumlah)
            VALUES (?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Gagal menyiapkan riwayat.");
        }

        $stmt->bind_param("si", $dzikir, $jumlah);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Riwayat gagal disimpan.");
        }

        $stmt->close();

        // Jika sesi berasal dari antrean,
        // tandai antrean saat ini selesai.
        if ($adaAntreanAktif) {

            $queue_id = (int) $_SESSION['queue_id'];

            $stmt = $conn->prepare("
                UPDATE queue_dzikir
                SET status = 'selesai'
                WHERE id = ?
                  AND status = 'aktif'
            ");

            if (!$stmt) {
                throw new Exception("Gagal menyiapkan status antrean.");
            }

            $stmt->bind_param("i", $queue_id);
            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                $stmt->close();
                throw new Exception(
                    "Status antrean aktif tidak berhasil diperbarui."
                );
            }

            $stmt->close();

            // Aktifkan antrean berikutnya dalam transaksi yang sama.
            $adaBerikutnya = aktifkanBerikutnya($conn);

        } else {

            // Sesi dzikir biasa, bukan dari antrean
            $_SESSION['target_selesai'] = true;
            $adaBerikutnya = false;
        }

        $conn->commit();

        if ($adaAntreanAktif) {

            if ($adaBerikutnya) {

                $_SESSION['pesan'] =
                    "Alhamdulillah! Target tercapai. Antrean berikutnya otomatis aktif.";

            } else {

                // Semua antrean sudah selesai
                $_SESSION['target_selesai'] = true;

                $_SESSION['pesan'] =
                    "Alhamdulillah! Antrean selesai. Tidak ada antrean berikutnya.";

            }

        } else {

            $_SESSION['pesan'] =
                "Alhamdulillah, target dzikir berhasil dicapai.";

        }

    } catch (Throwable $e) {

        $conn->rollback();

        // Kembalikan hitungan jika proses database gagal
        if (!empty($_SESSION['stack'])) {
            $_SESSION['jumlah'] =
                (int) array_pop($_SESSION['stack']);
        }

        $_SESSION['pesan'] =
            "Proses gagal. Silakan coba lagi. " . $e->getMessage();
    }

    header("Location: index.php");
    exit;
}


// ==========================================
// UNDO
// ==========================================

elseif ($aksi === 'undo') {

    if ($_SESSION['target_selesai'] === false) {

        if (!empty($_SESSION['stack'])) {

            $_SESSION['jumlah'] =
                (int) array_pop($_SESSION['stack']);

        }
    }

    header("Location: index.php");
    exit;
}


// ==========================================
// RESET
// ==========================================

elseif ($aksi === 'reset') {

    // Simpan hitungan parsial sebagai riwayat manual,
    // hanya jika target belum selesai.
    if (
        $_SESSION['target_selesai'] === false &&
        (int) $_SESSION['jumlah'] > 0
    ) {

        $dzikir = $_SESSION['dzikir'];
        $jumlah = (int) $_SESSION['jumlah'];

        $stmt = $conn->prepare("
            INSERT INTO riwayat (dzikir, jumlah)
            VALUES (?, ?)
        ");

        if ($stmt) {

            $stmt->bind_param("si", $dzikir, $jumlah);

            if ($stmt->execute()) {

                $_SESSION['pesan'] =
                    "Riwayat hitungan berhasil disimpan.";

            } else {

                $_SESSION['pesan'] =
                    "Riwayat gagal disimpan.";

            }

            $stmt->close();

        } else {

            $_SESSION['pesan'] =
                "Gagal menyiapkan penyimpanan riwayat.";

        }
    }

    // Reset hitungan tanpa menghapus antrean aktif
    $_SESSION['jumlah'] = 0;
    $_SESSION['stack'] = [];

    // Jika target sebelumnya sudah tercapai dan tidak ada
    // antrean aktif, izinkan sesi dzikir biasa dimulai lagi.
    if (!isset($_SESSION['queue_id'])) {
        $_SESSION['target_selesai'] = false;
    }

    header("Location: index.php");
    exit;
}


// ==========================================
// AKSI TIDAK DIKENAL
// ==========================================

header("Location: index.php");
exit;

?>
```