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

$message = '';
$message_type = '';
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;
$current_image_path = null;

// Ürün bilgilerini çek
if ($product_id > 0) {
    // Ürün bilgilerini ve varsa görsel bilgisini çek
    $sql = "SELECT U.urunID, U.urun_ad, U.urun_aciklama, U.birim_fiyat, U.stok_miktari, U.urun_son_kullanma_tarihi, U.kategoriID,
                   M.dosya_yolu, M.medyaID, MB.medya_baglantisiID
            FROM URUN U
            LEFT JOIN MEDYA_BAGLANTISI MB ON U.urunID = MB.varlikID AND MB.varlik_tipi = 'urun' AND MB.medya_rolu = 'ana'
            LEFT JOIN MEDYA M ON MB.medyaID = M.medyaID
            WHERE U.urunID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        $message = "Ürün bulunamadı.";
        $message_type = "error";
    } else {
        $current_image_path = $product['dosya_yolu'];
    }
} else {
    $message = "Geçersiz ürün ID'si.";
    $message_type = "error";
}

// Kategorileri veritabanından çek
$categories_sql = "SELECT kategoriID, kategori_ad FROM KATEGORI ORDER BY kategori_ad ASC";
$categories_result = $mysqli->query($categories_sql);
$categories = [];
if ($categories_result->num_rows > 0) {
    while($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Form POST metodu ile gönderildiğinde ürün güncelleme işlemini yap
if ($_SERVER["REQUEST_METHOD"] == "POST" && $product) {
    // Formdan gelen verileri al ve temizle
    $urun_ad = htmlspecialchars($_POST['urun_ad']);
    $urun_aciklama = htmlspecialchars($_POST['urun_aciklama']);
    $birim_fiyat = floatval($_POST['birim_fiyat']);
    $stok_miktari = intval($_POST['stok_miktari']);
    $urun_son_kullanma_tarihi = htmlspecialchars($_POST['urun_son_kullanma_tarihi']);
    $kategoriID = intval($_POST['kategoriID']);

    // Ürünü güncellemek için SQL sorgusu
    $sql = "UPDATE URUN SET urun_ad = ?, urun_aciklama = ?, birim_fiyat = ?, stok_miktari = ?, urun_son_kullanma_tarihi = ?, kategoriID = ? WHERE urunID = ?";
    
    // Sorguyu hazırla
    $stmt = $mysqli->prepare($sql);
    // Parametreleri bağla
    $stmt->bind_param("ssdissi", $urun_ad, $urun_aciklama, $birim_fiyat, $stok_miktari, $urun_son_kullanma_tarihi, $kategoriID, $product_id);
    
    // Sorguyu çalıştır
    if ($stmt->execute()) {
        $update_successful = true;

        // Görsel güncelleme işlemi
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['product_image']['tmp_name'];
            $file_name = $_FILES['product_image']['name'];
            $file_size = $_FILES['product_image']['size'];
            $file_type = $_FILES['product_image']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024; // 5 MB

            if (in_array($file_ext, $allowed_extensions) && $file_size <= $max_file_size) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_file_name = uniqid('urun_') . '.' . $file_ext;
                $dest_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_path, $dest_path)) {
                    // Mevcut görsel varsa dosyasını sil
                    if ($current_image_path && file_exists($current_image_path)) {
                        unlink($current_image_path);
                    }

                    // MEDYA ve MEDYA_BAGLANTISI kayıtlarını güncelle veya yeni ekle/sil
                    if (isset($product['medyaID'])) {
                        // Mevcut medya kaydını güncelle
                        $medya_sql = "UPDATE MEDYA SET dosya_yolu = ?, dosya_turu = ?, aciklama = ? WHERE medyaID = ?";
                        $medya_stmt = $mysqli->prepare($medya_sql);
                        $aciklama_val = $urun_ad . " ürünü görseli"; // Concatenated string'i değişkene atandı
                        $medya_stmt->bind_param("sssi", $dest_path, $file_type, $aciklama_val, $product['medyaID']);
                        $medya_stmt->execute();
                        $medya_stmt->close();

                        // MEDYA_BAGLANTISI kaydını güncelle (varlikID'si aynı kalacak)
                        // Bu kısım genellikle MEDYAID değiştiğinde gerekli olur ama burada MEDYAID değişmez, dosya_yolu değişir.
                        // Yine de, MEDYA_BAGLANTISI'nda medyaID'nin doğru olduğundan emin olalım.
                        // Bu kısmı da call_user_func_array ile güvenli hale getirelim.
                        $medya_baglanti_sql_update = "UPDATE MEDYA_BAGLANTISI SET medyaID = ? WHERE medya_baglantisiID = ?";
                        $medya_baglanti_stmt_update = $mysqli->prepare($medya_baglanti_sql_update);
                        $params_update = array($product['medyaID'], $product['medya_baglantisiID']);
                        $types_update = "ii";
                        call_user_func_array(array($medya_baglanti_stmt_update, 'bind_param'), array_merge(array($types_update), $params_update));
                        $medya_baglanti_stmt_update->execute();
                        $medya_baglanti_stmt_update->close();


                    } else {
                        // Ürünün daha önce görseli yoksa yeni medya kaydı ekle
                        $medya_sql = "INSERT INTO MEDYA (dosya_yolu, dosya_turu, aciklama) VALUES (?, ?, ?)";
                        $medya_stmt = $mysqli->prepare($medya_sql);
                        $aciklama_val = $urun_ad . " ürünü görseli"; // Concatenated string'i değişkene atandı
                        $medya_stmt->bind_param("sss", $dest_path, $file_type, $aciklama_val);
                        $medya_stmt->execute();
                        $new_medya_id = $mysqli->insert_id;
                        $medya_stmt->close();

                        // MEDYA_BAGLANTISI kaydını ekle
                        $medya_baglanti_sql = "INSERT INTO MEDYA_BAGLANTISI (medyaID, varlik_tipi, varlikID, medya_rolu) VALUES (?, ?, ?, ?)";
                        $medya_baglanti_stmt = $mysqli->prepare($medya_baglanti_sql);
                        $varlik_tipi_val = 'urun';
                        $medya_rolu_val = 'ana';
                        
                        // Düzeltme: call_user_func_array kullanılarak referans sorunu çözüldü
                        $params = array($new_medya_id, $varlik_tipi_val, $product_id, $medya_rolu_val);
                        $types = "isis"; // int, string, int, string
                        call_user_func_array(array($medya_baglanti_stmt, 'bind_param'), array_merge(array($types), $params));
                        
                        $medya_baglanti_stmt->execute();
                        $medya_baglanti_stmt->close();
                    }
                    $message = "Ürün ve görsel başarıyla güncellendi!";
                    $message_type = "success";

                } else {
                    $message = "Görsel yükleme sırasında bir hata oluştu.";
                    $message_type = "error";
                    $update_successful = false; // Görsel yükleme hatası olursa genel durumu başarısız yap
                }
            } else {
                $message = "Görsel formatı desteklenmiyor veya dosya boyutu çok büyük (Max 5MB). Desteklenen formatlar: JPG, JPEG, PNG, GIF.";
                $message_type = "error";
                $update_successful = false; // Görsel validasyon hatası olursa genel durumu başarısız yap
            }
        }
        
        if ($update_successful) {
            header("Location: urun_yonetimi.php?status=updated");
            exit;
        }

    } else {
        $message = "Ürün güncelleme sırasında bir hata oluştu: " . $stmt->error;
        $message_type = "error";
    }
    // Hazırlanmış ifadeyi kapat
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Düzenle</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="bg-white shadow-md p-4 w-full">
        <div class="container mx-auto flex justify-between items-center">
            <a href="urun_listesi.php" class="text-2xl font-bold text-gray-800">Süt Ürünleri Marketi</a>
            <div class="space-x-4">
                <?php if ($currentUser): ?>
                    <a href="main.php" class="text-gray-700 hover:text-blue-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Profilim</a>
                    <a href="sepet.php" class="text-gray-700 hover:text-green-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Sepetim</a>
                    <?php if ($currentUser->hasRole('Çalışan') || $currentUser->hasRole('Admin')): ?>
                        <a href="urun_yonetimi.php" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Ürün Yönetimi</a>
                        <a href="yonetim_paneli.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Yönetim Paneli</a>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Çıkış Yap</a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-700 hover:text-blue-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Giriş Yap</a>
                    <a href="register.php" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Kayıt Ol</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-4 flex-grow">
        <div class="bg-white p-8 rounded-2xl shadow-lg max-w-2xl w-full mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Ürün Düzenle</h1>
            <?php if ($message): ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($product): ?>
                <form method="POST" action="edit_urun.php?id=<?php echo $product_id; ?>" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label for="urun_ad" class="block text-gray-700 text-sm font-semibold mb-2">Ürün Adı:</label>
                        <input type="text" id="urun_ad" name="urun_ad" value="<?php echo htmlspecialchars($product['urun_ad']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="urun_aciklama" class="block text-gray-700 text-sm font-semibold mb-2">Ürün Açıklaması:</label>
                        <textarea id="urun_aciklama" name="urun_aciklama" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($product['urun_aciklama']); ?></textarea>
                    </div>
                    <div>
                        <label for="birim_fiyat" class="block text-gray-700 text-sm font-semibold mb-2">Birim Fiyatı:</label>
                        <input type="number" step="0.01" id="birim_fiyat" name="birim_fiyat" value="<?php echo htmlspecialchars($product['birim_fiyat']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="stok_miktari" class="block text-gray-700 text-sm font-semibold mb-2">Stok Miktarı:</label>
                        <input type="number" id="stok_miktari" name="stok_miktari" value="<?php echo htmlspecialchars($product['stok_miktari']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="urun_son_kullanma_tarihi" class="block text-gray-700 text-sm font-semibold mb-2">Son Kullanma Tarihi:</label>
                        <input type="date" id="urun_son_kullanma_tarihi" name="urun_son_kullanma_tarihi" value="<?php echo htmlspecialchars($product['urun_son_kullanma_tarihi']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="kategoriID" class="block text-gray-700 text-sm font-semibold mb-2">Kategori:</label>
                        <select id="kategoriID" name="kategoriID" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Kategori Seçin</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['kategoriID']); ?>"
                                    <?php echo ($category['kategoriID'] == $product['kategoriID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['kategori_ad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="product_image" class="block text-gray-700 text-sm font-semibold mb-2">Ürün Görseli (JPG, PNG, GIF - Max 5MB):</label>
                        <?php if ($current_image_path): ?>
                            <div class="mb-2">
                                <p class="text-sm text-gray-600">Mevcut Görsel:</p>
                                <img src="<?php echo htmlspecialchars($current_image_path); ?>" alt="Mevcut Ürün Görseli" class="w-32 h-32 object-cover rounded-lg shadow-md border border-gray-200">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="product_image" name="product_image" accept=".jpg,.jpeg,.png,.gif" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php if ($current_image_path): ?>
                            <p class="text-xs text-gray-500 mt-1">Yeni bir görsel seçmezseniz mevcut görsel korunur.</p>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-between items-center gap-4">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                            Ürünü Güncelle
                        </button>
                        <a href="urun_yonetimi.php" class="w-full text-center bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                            İptal
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date("Y"); ?> Süt Ürünleri Marketi. Tüm Hakları Saklıdır.</p>
    </footer>
</body>
</html>
