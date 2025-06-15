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

$user_id = $_SESSION['user_id'];
$currentUser = new User($mysqli, $user_id);

// Sadece 'Müşteri' rolüne sahip kullanıcılar sepet işlemlerini yapabilir
if (!$currentUser->hasRole('Müşteri')) {
    header("Location: main.php?error=yetkisiz_erisim_sepet_islem");
    exit;
}

// POST ile gelen işlemi al
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'add_to_cart':
        $urun_id = isset($_POST['urun_id']) ? intval($_POST['urun_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        $urun_ad = htmlspecialchars($_POST['urun_ad']);
        $birim_fiyat = floatval($_POST['birim_fiyat']);

        if ($urun_id <= 0 || $quantity <= 0) {
            header("Location: urun_detay.php?id=" . $urun_id . "&status=hata");
            exit;
        }

        // Ürünün stok miktarını kontrol et
        $stok_sql = "SELECT stok_miktari FROM URUN WHERE urunID = ?";
        $stok_stmt = $mysqli->prepare($stok_sql);
        $stok_stmt->bind_param("i", $urun_id);
        $stok_stmt->execute();
        $stok_result = $stok_stmt->get_result();
        $product_stok = $stok_result->fetch_assoc();
        $stok_stmt->close();

        if (!$product_stok || $product_stok['stok_miktari'] < $quantity) {
            header("Location: urun_detay.php?id=" . $urun_id . "&status=stok_yetersiz");
            exit;
        }

        // Kullanıcının aktif sepetini bul veya oluştur
        $sepet_id = null;
        $get_cart_sql = "SELECT sepetID FROM SEPET WHERE kullaniciID = ? AND sepet_durum = 'aktif'";
        $get_cart_stmt = $mysqli->prepare($get_cart_sql);
        $get_cart_stmt->bind_param("i", $user_id);
        $get_cart_stmt->execute();
        $get_cart_result = $get_cart_stmt->get_result();
        $cart_row = $get_cart_result->fetch_assoc();
        $get_cart_stmt->close();

        if ($cart_row) {
            $sepet_id = $cart_row['sepetID'];
        } else {
            // Yeni sepet oluştur
            $create_cart_sql = "INSERT INTO SEPET (kullaniciID, olusturma_tarihi, sepet_durum, guncelleme_tarihi) VALUES (?, NOW(), 'aktif', NOW())";
            $create_cart_stmt = $mysqli->prepare($create_cart_sql);
            $create_cart_stmt->bind_param("i", $user_id);
            $create_cart_stmt->execute();
            $sepet_id = $mysqli->insert_id;
            $create_cart_stmt->close();
        }

        // Sepette ürünün zaten olup olmadığını kontrol et
        $check_item_sql = "SELECT sepet_urunID, adet FROM SEPET_URUN WHERE sepetID = ? AND urunID = ?";
        $check_item_stmt = $mysqli->prepare($check_item_sql);
        $check_item_stmt->bind_param("ii", $sepet_id, $urun_id);
        $check_item_stmt->execute();
        $check_item_result = $check_item_stmt->get_result();
        $cart_item = $check_item_result->fetch_assoc();
        $check_item_stmt->close();

        if ($cart_item) {
            // Ürün sepette varsa adedi güncelle
            $new_quantity = $cart_item['adet'] + $quantity;
            // Güncel stok kontrolü (toplam miktar için)
            if ($product_stok['stok_miktari'] < $new_quantity) {
                header("Location: urun_detay.php?id=" . $urun_id . "&status=stok_yetersiz");
                exit;
            }

            $update_item_sql = "UPDATE SEPET_URUN SET adet = ?, guncelleme_tarihi = NOW() WHERE sepet_urunID = ?";
            $update_item_stmt = $mysqli->prepare($update_item_sql);
            $update_item_stmt->bind_param("ii", $new_quantity, $cart_item['sepet_urunID']);
            $update_item_stmt->execute();
            $update_item_stmt->close();
        } else {
            // Ürün sepette yoksa yeni kayıt ekle
            $insert_item_sql = "INSERT INTO SEPET_URUN (sepetID, urunID, adet, anlik_birim_fiyat) VALUES (?, ?, ?, ?)";
            $insert_item_stmt = $mysqli->prepare($insert_item_sql);
            $insert_item_stmt->bind_param("iiid", $sepet_id, $urun_id, $quantity, $birim_fiyat);
            $insert_item_stmt->execute();
            $insert_item_stmt->close();
        }

        header("Location: urun_detay.php?id=" . $urun_id . "&status=sepete_eklendi");
        break;

    case 'update_quantity':
        $sepet_urun_id = isset($_POST['sepet_urun_id']) ? intval($_POST['sepet_urun_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        if ($sepet_urun_id <= 0 || $quantity <= 0) {
            header("Location: sepet.php?status=hata");
            exit;
        }

        // Sepet ürününe ve ilgili ürünün stokuna ulaş
        $get_item_info_sql = "SELECT SU.adet, U.stok_miktari, U.urunID
                              FROM SEPET_URUN SU
                              JOIN URUN U ON SU.urunID = U.urunID
                              WHERE SU.sepet_urunID = ?";
        $get_item_info_stmt = $mysqli->prepare($get_item_info_sql);
        $get_item_info_stmt->bind_param("i", $sepet_urun_id);
        $get_item_info_stmt->execute();
        $item_info_result = $get_item_info_stmt->get_result();
        $item_info = $item_info_result->fetch_assoc();
        $get_item_info_stmt->close();

        if (!$item_info) {
            header("Location: sepet.php?status=hata");
            exit;
        }

        // Stok kontrolü
        if ($quantity > $item_info['stok_miktari']) {
            header("Location: sepet.php?status=stok_yetersiz");
            exit;
        }

        $update_quantity_sql = "UPDATE SEPET_URUN SET adet = ? WHERE sepet_urunID = ?";
        $update_quantity_stmt = $mysqli->prepare($update_quantity_sql);
        $update_quantity_stmt->bind_param("ii", $quantity, $sepet_urun_id);
        if ($update_quantity_stmt->execute()) {
            header("Location: sepet.php?status=guncellendi");
        } else {
            header("Location: sepet.php?status=hata");
        }
        $update_quantity_stmt->close();
        break;

    case 'remove_from_cart':
        $sepet_urun_id = isset($_POST['sepet_urun_id']) ? intval($_POST['sepet_urun_id']) : 0;

        if ($sepet_urun_id <= 0) {
            header("Location: sepet.php?status=hata");
            exit;
        }

        $delete_item_sql = "DELETE FROM SEPET_URUN WHERE sepet_urunID = ?";
        $delete_item_stmt = $mysqli->prepare($delete_item_sql);
        $delete_item_stmt->bind_param("i", $sepet_urun_id);
        if ($delete_item_stmt->execute()) {
            header("Location: sepet.php?status=silindi");
        } else {
            header("Location: sepet.php?status=hata");
        }
        $delete_item_stmt->close();
        break;
    
    case 'clear_cart':
        // Kullanıcının aktif sepet ID'sini bul
        $get_cart_sql = "SELECT sepetID FROM SEPET WHERE kullaniciID = ? AND sepet_durum = 'aktif'";
        $get_cart_stmt = $mysqli->prepare($get_cart_sql);
        $get_cart_stmt->bind_param("i", $user_id);
        $get_cart_stmt->execute();
        $get_cart_result = $get_cart_stmt->get_result();
        $cart_row = $get_cart_result->fetch_assoc();
        $get_cart_stmt->close();

        if ($cart_row) {
            $sepet_id = $cart_row['sepetID'];
            // Sepet_Urun tablosundan tüm ilgili kayıtları sil
            $clear_items_sql = "DELETE FROM SEPET_URUN WHERE sepetID = ?";
            $clear_items_stmt = $mysqli->prepare($clear_items_sql);
            $clear_items_stmt->bind_param("i", $sepet_id);
            $clear_items_stmt->execute();
            $clear_items_stmt->close();

            // Sepet tablosundaki sepeti de sil (isteğe bağlı, sepeti pasif de yapabiliriz)
            // Bu örnekte tamamen silmeyi tercih ediyoruz.
            $delete_cart_sql = "DELETE FROM SEPET WHERE sepetID = ?";
            $delete_cart_stmt = $mysqli->prepare($delete_cart_sql);
            $delete_cart_stmt->bind_param("i", $sepet_id);
            $delete_cart_stmt->execute();
            $delete_cart_stmt->close();

            header("Location: sepet.php?status=bos");
            exit;
        } else {
            header("Location: sepet.php?status=hata"); // Zaten boş sepet varsa
            exit;
        }
        break;

    default:
        header("Location: sepet.php?status=hata");
        break;
}

$mysqli->close();
?>
