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

// Kategorileri veritabanından çek
$categories_sql = "SELECT kategoriID, kategori_ad FROM KATEGORI ORDER BY kategori_ad ASC";
$categories_result = $mysqli->query($categories_sql);
$categories = [];
if ($categories_result->num_rows > 0) {
    while($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Form POST metodu ile gönderildiğinde ürün ekleme işlemini yap
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri al ve temizle
    $urun_ad = htmlspecialchars($_POST['urun_ad']);
    $urun_aciklama = htmlspecialchars($_POST['urun_aciklama']);
    $birim_fiyat = floatval($_POST['birim_fiyat']);
    $stok_miktari = intval($_POST['stok_miktari']);
    $urun_son_kullanma_tarihi = htmlspecialchars($_POST['urun_son_kullanma_tarihi']);
    $kategoriID = intval($_POST['kategoriID']);

    // Yeni ürünü URUN tablosuna eklemek için SQL sorgusu
    $sql = "INSERT INTO URUN (urun_ad, urun_aciklama, birim_fiyat, stok_miktari, urun_son_kullanma_tarihi, kategoriID)
            VALUES (?, ?, ?, ?, ?, ?)";
    
    // Sorguyu hazırla
    $stmt = $mysqli->prepare($sql);
    // Parametreleri bağla (s: string, s: string, d: double, i: integer, s: string, i: integer)
    $stmt->bind_param("ssdisi", $urun_ad, $urun_aciklama, $birim_fiyat, $stok_miktari, $urun_son_kullanma_tarihi, $kategoriID);
    
    // Sorguyu çalıştır
    if ($stmt->execute()) {
        $new_urun_id = $mysqli->insert_id; // Yeni eklenen ürünün ID'sini al

        // Görsel yükleme işlemi
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['product_image']['tmp_name'];
            $file_name = $_FILES['product_image']['name'];
            $file_size = $_FILES['product_image']['size'];
            $file_type = $_FILES['product_image']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024; // 5 MB

            if (in_array($file_ext, $allowed_extensions) && $file_size <= $max_file_size) {
                $upload_dir = 'uploads/'; // Görsellerin yükleneceği klasör
                // Klasör yoksa oluştur
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_file_name = uniqid('urun_') . '.' . $file_ext; // Benzersiz dosya adı oluştur
                $dest_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_path, $dest_path)) {
                    // MEDYA tablosuna kaydet
                    $medya_sql = "INSERT INTO MEDYA (dosya_yolu, dosya_turu, aciklama) VALUES (?, ?, ?)";
                    $medya_stmt = $mysqli->prepare($medya_sql);
                    $aciklama_val = $urun_ad . " ürünü görseli"; // Concatenated string'i değişkene atandı
                    $medya_stmt->bind_param("sss", $dest_path, $file_type, $aciklama_val);
                    $medya_stmt->execute();
                    $new_medya_id = $mysqli->insert_id;
                    $medya_stmt->close();

                    // MEDYA_BAGLANTISI tablosuna kaydet (ürün ile görseli bağla)
                    $medya_baglanti_sql = "INSERT INTO MEDYA_BAGLANTISI (medyaID, varlik_tipi, varlikID, medya_rolu) VALUES (?, ?, ?, ?)";
                    $medya_baglanti_stmt = $mysqli->prepare($medya_baglanti_sql);
                    
                    // DÜZELTME: bind_param için doğru tip dizesi ve değişkenler
                    $varlik_tipi_val = 'urun';
                    $medya_rolu_val = 'ana'; 
                    // Sıralama: medyaID(i), varlik_tipi(s), varlikID(i), medya_rolu(s)
                    // Hatanın sık tekrar etmesi nedeniyle daha sağlam call_user_func_array yöntemi kullanıldı.
                    $params = array($new_medya_id, $varlik_tipi_val, $new_urun_id, $medya_rolu_val);
                    $types = "isis";
                    call_user_func_array(array($medya_baglanti_stmt, 'bind_param'), array_merge(array($types), $params));
                    
                    $medya_baglanti_stmt->execute();
                    $medya_baglanti_stmt->close();

                    $message = "Ürün ve görsel başarıyla eklendi!";
                    $message_type = "success";

                } else {
                    $message = "Görsel yükleme sırasında bir hata oluştu.";
                    $message_type = "error";
                }
            } else {
                $message = "Görsel formatı desteklenmiyor veya dosya boyutu çok büyük (Max 5MB). Desteklenen formatlar: JPG, JPEG, PNG, GIF.";
                $message_type = "error";
            }
        } else {
            // Görsel yüklenmediyse bile ürünü eklemeye devam et
            $message = "Ürün başarıyla eklendi (Görsel yüklenmedi).";
            $message_type = "success";
        }
        header("Location: urun_yonetimi.php?status=added");
        exit;

    } else {
        $message = "Ürün ekleme sırasında bir hata oluştu: " . $stmt->error;
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
    <title>Yeni Ürün Ekle</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center p-4">
    <!-- Navbar -->
    <nav class="bg-white shadow-md p-4 w-full">
        <div class="container mx-auto flex justify-between items-center">
            <a href="urun_listesi.php" class="text-2xl font-bold text-gray-800">Süt Ürünleri Marketi</a>
            <div class="space-x-4">
                <?php if ($currentUser) { ?>
                    <a href="main.php" class="text-gray-700 hover:text-blue-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Profilim</a>
                    <a href="sepet.php" class="text-gray-700 hover:text-green-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Sepetim</a>
                    <?php if ($currentUser->hasRole('Çalışan') || $currentUser->hasRole('Admin')) { ?>
                        <a href="urun_yonetimi.php" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Ürün Yönetimi</a>
                    <?php } ?>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Çıkış Yap</a>
                <?php } else { ?>
                    <a href="login.php" class="text-gray-700 hover:text-blue-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Giriş Yap</a>
                    <a href="register.php" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Kayıt Ol</a>
                <?php } ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-4 flex-grow">
        <div class="bg-white p-8 rounded-2xl shadow-lg max-w-2xl w-full mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Yeni Ürün Ekle</h1>
            <?php if ($message) { ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php } ?>
            <form method="POST" action="add_urun.php" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="urun_ad" class="block text-gray-700 text-sm font-semibold mb-2">Ürün Adı:</label>
                    <input type="text" id="urun_ad" name="urun_ad" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="urun_aciklama" class="block text-gray-700 text-sm font-semibold mb-2">Ürün Açıklaması:</label>
                    <textarea id="urun_aciklama" name="urun_aciklama" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label for="birim_fiyat" class="block text-gray-700 text-sm font-semibold mb-2">Birim Fiyatı:</label>
                    <input type="number" step="0.01" id="birim_fiyat" name="birim_fiyat" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="stok_miktari" class="block text-gray-700 text-sm font-semibold mb-2">Stok Miktarı:</label>
                    <input type="number" id="stok_miktari" name="stok_miktari" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="urun_son_kullanma_tarihi" class="block text-gray-700 text-sm font-semibold mb-2">Son Kullanma Tarihi:</label>
                    <input type="date" id="urun_son_kullanma_tarihi" name="urun_son_kullanma_tarihi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="kategoriID" class="block text-gray-700 text-sm font-semibold mb-2">Kategori:</label>
                    <select id="kategoriID" name="kategoriID" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Kategori Seçin</option>
                        <?php foreach ($categories as $category) { ?>
                            <option value="<?php echo htmlspecialchars($category['kategoriID']); ?>">
                                <?php echo htmlspecialchars($category['kategori_ad']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label for="product_image" class="block text-gray-700 text-sm font-semibold mb-2">Ürün Görseli (JPG, PNG, GIF - Max 5MB):</label>
                    <input type="file" id="product_image" name="product_image" accept=".jpg,.jpeg,.png,.gif" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex justify-between items-center gap-4">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                        Ürün Ekle
                    </button>
                    <a href="urun_yonetimi.php" class="w-full text-center bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                        İptal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date("Y"); ?> Süt Ürünleri Marketi. Tüm Hakları Saklıdır.</p>
    </footer>
</body>
</html>
