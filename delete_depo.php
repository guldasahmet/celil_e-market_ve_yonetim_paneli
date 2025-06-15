<?php
// Oturumu başlat
session_start();

// Veritabanı bağlantısını dahil et
include 'db.php';
// User sınıfını dahil et
include 'User.php';

// Kullanıcı oturumu başlatılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// User nesnesini oluştur
$currentUser = new User($mysqli, $_SESSION['user_id']);

// Yetkilendirme kontrolü: Sadece Çalışan veya Admin rolüne sahip kullanıcılar erişebilir
if (!$currentUser->hasRole('Çalışan') && !$currentUser->hasRole('Admin')) {
    header("Location: main.php?error=yetkisiz_erisim");
    exit;
}

// Depo ID'sini al
$depo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($depo_id > 0) {
    // Depo ile ilişkili adresID'yi al
    $get_adres_id_sql = "SELECT adresID FROM DEPO WHERE depoID = ?";
    $get_adres_id_stmt = $mysqli->prepare($get_adres_id_sql);
    $get_adres_id_stmt->bind_param("i", $depo_id);
    $get_adres_id_stmt->execute();
    $result = $get_adres_id_stmt->get_result();
    $depo_data = $result->fetch_assoc();
    $get_adres_id_stmt->close();

    $adres_id = null;
    if ($depo_data) {
        $adres_id = $depo_data['adresID'];
    }

    // Yabancı anahtar kontrollerini geçici olarak devre dışı bırak
    // Depo tablosunu silerken bağlı tablolardan hata almamızı engeller.
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");

    try {
        // İlk olarak DEPO kaydını sil
        $delete_depo_sql = "DELETE FROM DEPO WHERE depoID = ?";
        $delete_depo_stmt = $mysqli->prepare($delete_depo_sql);
        $delete_depo_stmt->bind_param("i", $depo_id);
        $delete_depo_stmt->execute();
        $delete_depo_stmt->close();

        // Eğer bir adresID varsa, ilişkili ADRES kaydını da sil
        if ($adres_id) {
            $delete_adres_sql = "DELETE FROM ADRES WHERE adresID = ?";
            $delete_adres_stmt = $mysqli->prepare($delete_adres_sql);
            $delete_adres_stmt->bind_param("i", $adres_id);
            $delete_adres_stmt->execute();
            $delete_adres_stmt->close();
        }

        header("Location: depo_yonetimi.php?status=deleted");
        exit;

    } catch (Exception $e) {
        // Hata oluşursa
        error_log("Depo silme hatası: " . $e->getMessage());
        header("Location: depo_yonetimi.php?status=error");
        exit;
    } finally {
        // Yabancı anahtar kontrollerini tekrar etkinleştir
        $mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");
    }

} else {
    header("Location: depo_yonetimi.php?status=error");
    exit;
}
?>
