<?php
// Oturumu başlat
session_start();

// Veritabanı bağlantısını dahil et
include 'db.php';
// User sınıfını dahil et (rol kontrolü için)
include 'User.php';

$currentUser = null;
if (isset($_SESSION['user_id'])) {
    // Eğer kullanıcı giriş yapmışsa User nesnesini oluştur
    $currentUser = new User($mysqli, $_SESSION['user_id']);
}

$product = null;
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id > 0) {
    // Ürün bilgilerini, kategori adını ve görselin dosya yolunu veritabanından çek
    $sql = "SELECT U.urunID, U.urun_ad, U.urun_aciklama, U.birim_fiyat, U.stok_miktari, U.urun_son_kullanma_tarihi, K.kategori_ad,
                   M.dosya_yolu AS image_path
            FROM URUN U
            LEFT JOIN KATEGORI K ON U.kategoriID = K.kategoriID
            LEFT JOIN MEDYA_BAGLANTISI MB ON U.urunID = MB.varlikID AND MB.varlik_tipi = 'urun' AND MB.medya_rolu = 'ana'
            LEFT JOIN MEDYA M ON MB.medyaID = M.medyaID
            WHERE U.urunID = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

$message = '';
$message_type = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'sepete_eklendi') {
        $message = "Ürün sepetinize başarıyla eklendi!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'stok_yetersiz') {
        $message = "Hata: İstenilen miktarda ürün stokta bulunmamaktadır.";
        $message_type = "error";
    } elseif ($_GET['status'] == 'hata') {
        $message = "Sepete ekleme sırasında bir hata oluştu.";
        $message_type = "error";
    }
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['urun_ad']) : 'Ürün Bulunamadı'; ?> - Süt Ürünleri Marketi</title>
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
    <nav class="bg-white shadow-md p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="urun_listesi.php" class="text-2xl font-bold text-gray-800">Süt Ürünleri Marketi</a>
            <div class="space-x-4">
                <?php if ($currentUser): ?>
                    <a href="main.php" class="text-gray-700 hover:text-blue-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Profilim</a>
                    <a href="sepet.php" class="text-gray-700 hover:text-green-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Sepetim</a>
                    <?php if ($currentUser->hasRole('Çalışan') || $currentUser->hasRole('Admin')): ?>
                        <a href="urun_yonetimi.php" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Ürün Yönetimi</a>
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
        <?php if ($message): ?>
            <div class="
                <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
            <div class="bg-white rounded-xl shadow-lg p-8 md:flex md:space-x-8">
                <!-- Ürün Görseli -->
                <div class="md:w-1/3 flex items-center justify-center bg-gray-50 rounded-lg p-4">
                    <?php if ($product['image_path']): ?>
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['urun_ad']); ?>" class="max-w-full h-auto rounded-lg shadow-md">
                    <?php else: ?>
                        <div class="w-full h-64 bg-gray-200 flex items-center justify-center text-gray-500 text-sm rounded-lg">Görsel Yok</div>
                    <?php endif; ?>
                </div>
                
                <!-- Ürün Bilgileri -->
                <div class="md:w-2/3 mt-6 md:mt-0">
                    <h1 class="text-4xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($product['urun_ad']); ?></h1>
                    <p class="text-gray-500 text-lg mb-4">Kategori: <span class="font-semibold"><?php echo htmlspecialchars($product['kategori_ad'] ?: 'Belirtilmemiş'); ?></span></p>
                    <p class="text-gray-700 text-base leading-relaxed mb-6"><?php echo htmlspecialchars($product['urun_aciklama']); ?></p>
                    
                    <div class="flex items-baseline justify-between mb-6">
                        <span class="text-5xl font-extrabold text-blue-700"><?php echo htmlspecialchars(number_format($product['birim_fiyat'], 2, ',', '.')); ?> TL</span>
                        <span class="text-lg text-gray-600">Stok: <span class="font-bold"><?php echo htmlspecialchars($product['stok_miktari']); ?></span></span>
                    </div>

                    <?php if ($product['urun_son_kullanma_tarihi']): ?>
                        <p class="text-sm text-red-600 mb-6 font-medium">Son Kullanma Tarihi: <?php echo htmlspecialchars($product['urun_son_kullanma_tarihi']); ?></p>
                    <?php endif; ?>

                    <?php if ($currentUser && $currentUser->hasRole('Müşteri')): // Sadece müşteriler sepete ekleyebilir ?>
                        <?php if ($product['stok_miktari'] > 0): ?>
                            <form action="sepet_islem.php" method="POST" class="flex items-center space-x-4">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="urun_id" value="<?php echo $product['urunID']; ?>">
                                <input type="hidden" name="urun_ad" value="<?php echo htmlspecialchars($product['urun_ad']); ?>">
                                <input type="hidden" name="birim_fiyat" value="<?php echo htmlspecialchars($product['birim_fiyat']); ?>">
                                
                                <label for="quantity" class="text-gray-700 font-semibold">Miktar:</label>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stok_miktari']; ?>" class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                                    Sepete Ekle
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline">Ürün stokta bulunmamaktadır.</span>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($currentUser && ($currentUser->hasRole('Çalışan') || $currentUser->hasRole('Admin'))): ?>
                        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">Yönetici/Çalışan olarak sepete ürün ekleyemezsiniz.</span>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline"><a href="login.php" class="underline font-semibold">Giriş yapın</a> veya <a href="register.php" class="underline font-semibold">kayıt olun</a>, sepetinize ürün eklemek için.</span>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            <div class="mt-8 text-center">
                <a href="urun_listesi.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                    Tüm Ürünlere Dön
                </a>
            </div>
        <?php else: ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">Ürün bulunamadı veya geçersiz ürün ID'si.</span>
            </div>
            <div class="mt-8 text-center">
                <a href="urun_listesi.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                    Tüm Ürünlere Dön
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date("Y"); ?> Süt Ürünleri Marketi. Tüm Hakları Saklıdır.</p>
    </footer>
</body>
</html>
