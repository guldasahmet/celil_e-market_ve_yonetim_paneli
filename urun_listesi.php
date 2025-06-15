<?php
// Oturumu başlat
session_start();

// Veritabanı bağlantısını dahil et
include 'db.php';
// User sınıfını dahil et (eğer kullanılıyorsa, kontrol ediniz)
if (file_exists('User.php')) {
    include 'User.php';
    $currentUser = isset($_SESSION['user_id']) ? new User($mysqli, $_SESSION['user_id']) : null;
} else {
    $currentUser = null;
}

// Tüm ürünleri ve kategorilerini çek
$sql = "SELECT U.urunID, U.urun_ad, U.urun_aciklama, U.birim_fiyat, U.stok_miktari, U.urun_son_kullanma_tarihi, K.kategori_ad,
               M.dosya_yolu AS image_path
        FROM URUN U
        LEFT JOIN KATEGORI K ON U.kategoriID = K.kategoriID
        LEFT JOIN MEDYA_BAGLANTISI MB ON U.urunID = MB.varlikID AND MB.varlik_tipi = 'urun' AND MB.medya_rolu = 'ana'
        LEFT JOIN MEDYA M ON MB.medyaID = M.medyaID
        ORDER BY U.urunID DESC";
$result = $mysqli->query($sql);

$products = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Listesi</title>
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
                    <?php if ($currentUser->hasRole('Çalışan') || $currentUser->hasRole('Admin')): // Ürün Yönetimi, Çalışan veya Admin içindir ?>
                        <a href="urun_yonetimi.php" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Ürün Yönetimi</a>
                    <?php endif; ?>
                    <?php if ($currentUser->hasRole('Admin')): // Yönetim Paneli sadece Admin içindir ?>
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
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Tüm Ürünler</h1>

        <?php if (empty($products)): ?>
            <p class="text-gray-600 text-center text-lg mt-10">Henüz hiç ürün bulunmamaktadır.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden flex flex-col p-6 transform hover:scale-105 transition duration-300">
                        <!-- Ürün Resmi -->
                        <div class="w-full h-48 bg-gray-200 rounded-lg mb-4 flex items-center justify-center text-gray-500 text-sm overflow-hidden">
                            <?php if ($product['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['urun_ad']); ?>" class="max-w-full h-auto object-cover">
                            <?php else: ?>
                                <span class="text-center">Görsel Yok</span>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($product['urun_ad']); ?></h2>
                        <p class="text-gray-600 text-sm mb-2">Kategori: <?php echo htmlspecialchars($product['kategori_ad'] ?: 'Belirtilmemiş'); ?></p>
                        <p class="text-gray-700 text-md flex-grow mb-4"><?php echo htmlspecialchars($product['urun_aciklama']); ?></p>
                        <div class="flex justify-between items-center mt-auto pt-4 border-t border-gray-200">
                            <span class="text-lg font-bold text-blue-600"><?php echo htmlspecialchars(number_format($product['birim_fiyat'], 2, ',', '.')); ?> TL</span>
                            <span class="text-gray-500 text-sm">Stok: <?php echo htmlspecialchars($product['stok_miktari']); ?></span>
                        </div>
                        <?php if ($product['urun_son_kullanma_tarihi']): ?>
                            <p class="text-red-500 text-xs mt-2">Son Kullanma Tarihi: <?php echo htmlspecialchars($product['urun_son_kullanma_tarihi']); ?></p>
                        <?php endif; ?>
                        <!-- Sepete Ekle Butonu - İlgili sepete ekle mantığı burada işlenmeli -->
                        <a href="urun_detay.php?id=<?php echo $product['urunID']; ?>" class="mt-4 bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 text-center">
                            Ürün Detayı
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date("Y"); ?> Süt Ürünleri Marketi. Tüm Hakları Saklıdır.</p>
    </footer>
</body>
</html>
