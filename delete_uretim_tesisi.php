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

// Üretim Tesisi ID'sini al
$tesis_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($tesis_id > 0) {
    // Üretim tesisine bağlı olabilecek diğer tablolardaki referansları güncelle
    // Örneğin: URETIM tablosunda uretim_tesisiID'yi NULL yapabiliriz veya ilgili üretim kayıtlarını silebiliriz.
    // VERİTABANI ŞEMANIZA GÖRE BURAYI DÜZENLEMENİZ GEREKEBİLİR!
    // Bu örnekte, basitlik adına, FOREIGN KEY ON DELETE CASCADE olduğunu veya manuel olarak temizlenmesi gerektiğini varsayıyoruz.
    // Eğer CASCADE ayarlı değilse ve URETIM tablosunda bu tesise bağlı kayıtlar varsa, silme işlemi başarısız olur.
    
    // URETIM tablosunda bu tesise bağlı kayıtların tesisID'sini NULL yap (örnek)
    $update_uretim_sql = "UPDATE URETIM SET uretim_tesisiID = NULL WHERE uretim_tesisiID = ?";
    $update_uretim_stmt = $mysqli->prepare($update_uretim_sql);
    $update_uretim_stmt->bind_param("i", $tesis_id);
    $update_uretim_stmt->execute();
    $update_uretim_stmt->close();

    // Üretim tesisini sil
    $delete_tesis_sql = "DELETE FROM URETIM_TESISI WHERE uretim_tesisID = ?";
    
    // Sorguyu hazırla
    $delete_tesis_stmt = $mysqli->prepare($delete_tesis_sql);
    // Parametreyi bağla (i: integer)
    $delete_tesis_stmt->bind_param("i", $tesis_id);
    
    // Sorguyu çalıştır
    if ($delete_tesis_stmt->execute()) {
        header("Location: uretim_tesisi_yonetimi.php?status=deleted");
        exit;
    } else {
        header("Location: uretim_tesisi_yonetimi.php?status=error");
        exit;
    }
    // Hazırlanmış ifadeyi kapat
    $delete_tesis_stmt->close();
} else {
    header("Location: uretim_tesisi_yonetimi.php?status=error");
    exit;
}
?>
