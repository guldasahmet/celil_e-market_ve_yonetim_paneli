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

// Çalışan ID'sini al
$calisan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($calisan_id > 0) {
    // Çalışana bağlı olabilecek diğer tablolardaki referansları güncelle
    // Örneğin: DEPO tablosunda depo_sorumlusu_calisanID'yi NULL yapabiliriz.
    $update_depo_sql = "UPDATE DEPO SET depo_sorumlusu_calisanID = NULL WHERE depo_sorumlusu_calisanID = ?";
    $update_depo_stmt = $mysqli->prepare($update_depo_sql);
    $update_depo_stmt->bind_param("i", $calisan_id);
    $update_depo_stmt->execute();
    $update_depo_stmt->close();

    // Üretim Tesisi tablosunda bu çalışanı sorumlu olarak gösteren kayıtları NULL'a güncelle
    $update_uretim_tesisi_sql = "UPDATE URETIM_TESISI SET calisanID = NULL WHERE calisanID = ?";
    $update_uretim_tesisi_stmt = $mysqli->prepare($update_uretim_tesisi_sql);
    $update_uretim_tesisi_stmt->bind_param("i", $calisan_id);
    $update_uretim_tesisi_stmt->execute();
    $update_uretim_tesisi_stmt->close();

    // Çiftlik tablosunda bu çalışanı sorumlu olarak gösteren kayıtları NULL'a güncelle
    $update_ciftlik_sql = "UPDATE CIFTLIK SET calisanID = NULL WHERE calisanID = ?";
    $update_ciftlik_stmt = $mysqli->prepare($update_ciftlik_sql);
    $update_ciftlik_stmt->bind_param("i", $calisan_id);
    $update_ciftlik_stmt->execute();
    $update_ciftlik_stmt->close();
    
    // Çalışanı sil
    $delete_calisan_sql = "DELETE FROM CALISAN WHERE calisanID = ?";
    
    $delete_calisan_stmt = $mysqli->prepare($delete_calisan_sql);
    $delete_calisan_stmt->bind_param("i", $calisan_id);
    
    if ($delete_calisan_stmt->execute()) {
        header("Location: calisan_yonetimi.php?status=deleted");
        exit;
    } else {
        header("Location: calisan_yonetimi.php?status=error");
        exit;
    }
    $delete_calisan_stmt->close();
} else {
    header("Location: calisan_yonetimi.php?status=error");
    exit;
}
?>
