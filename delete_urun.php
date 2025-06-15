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

// Ürün ID'sini al
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id > 0) {
    // Önce ürünle ilişkili görsel bilgilerini al (varsa)
    $image_sql = "SELECT M.medyaID, M.dosya_yolu, MB.medya_baglantisiID
                  FROM MEDYA_BAGLANTISI MB
                  JOIN MEDYA M ON MB.medyaID = M.medyaID
                  WHERE MB.varlikID = ? AND MB.varlik_tipi = 'urun' AND MB.medya_rolu = 'ana'";
    $image_stmt = $mysqli->prepare($image_sql);
    $image_stmt->bind_param("i", $product_id);
    $image_stmt->execute();
    $image_result = $image_stmt->get_result();
    $image_data = $image_result->fetch_assoc();
    $image_stmt->close();

    // Ürünle ilişkili tüm kayıtları silme sırası (yabancı anahtar bağımlılıklarını göz önünde bulundurarak)
    // Sipariş ürünleri, yorumlar vb. gibi ürüne doğrudan bağlı olabilecek diğer tabloları da temizlemek önemlidir.
    // Bu senaryoda sadece MEDYA ve MEDYA_BAGLANTISI'nı doğrudan siliyorum.
    // Tam bir temizlik için veritabanı şemasında ON DELETE CASCADE kullanmak en iyisidir.

    // 1. MEDYA_BAGLANTISI kaydını sil
    if ($image_data && isset($image_data['medya_baglantisiID'])) {
        $delete_medya_baglanti_sql = "DELETE FROM MEDYA_BAGLANTISI WHERE medya_baglantisiID = ?";
        $delete_medya_baglanti_stmt = $mysqli->prepare($delete_medya_baglanti_sql);
        $delete_medya_baglanti_stmt->bind_param("i", $image_data['medya_baglantisiID']);
        $delete_medya_baglanti_stmt->execute();
        $delete_medya_baglanti_stmt->close();
    }

    // 2. MEDYA kaydını sil ve dosyayı sunucudan kaldır
    if ($image_data && isset($image_data['medyaID'])) {
        $delete_medya_sql = "DELETE FROM MEDYA WHERE medyaID = ?";
        $delete_medya_stmt = $mysqli->prepare($delete_medya_sql);
        $delete_medya_stmt->bind_param("i", $image_data['medyaID']);
        $delete_medya_stmt->execute();
        $delete_medya_stmt->close();

        // Dosyayı sunucudan sil
        if (file_exists($image_data['dosya_yolu'])) {
            unlink($image_data['dosya_yolu']);
        }
    }

    // Yabancı anahtar kontrolleri, eğer veritabanında ON DELETE CASCADE kuruluysa otomatik olarak işlenir.
    // Kurulu değilse, ürünü silmeden önce SEPET_URUN, YORUM gibi diğer tablolardaki bağımlı kayıtları manuel olarak silmeniz gerekir.
    // Basitleştirilmiş bu durum için, ilgili FK'ların CASCADE olduğu veya doğrudan kullanıcı tarafından silinemeyeceği varsayılmaktadır.
    // ON DELETE CASCADE ayarı olmayan URUN'e doğrudan referans veren tablolar için özel DELETE FROM ifadeleri eklemeyi düşünebilirsiniz.
    
    // 3. URUN kaydını sil
    $sql = "DELETE FROM URUN WHERE urunID = ?";
    
    // Sorguyu hazırla
    $stmt = $mysqli->prepare($sql);
    // Parametreyi bağla (i: integer)
    $stmt->bind_param("i", $product_id);
    
    // Sorguyu çalıştır
    if ($stmt->execute()) {
        header("Location: urun_yonetimi.php?status=deleted");
        exit;
    } else {
        header("Location: urun_yonetimi.php?status=error");
        exit;
    }
    // Hazırlanmış ifadeyi kapat
    $stmt->close();
} else {
    header("Location: urun_yonetimi.php?status=error");
    exit;
}
?>
