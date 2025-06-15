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
    // Yetkisiz erişim durumunda ana sayfaya yönlendir (veya hata mesajı göster)
    header("Location: main.php?error=yetkisiz_erisim");
    exit;
}

// Ürünleri veritabanından çek
$sql = "SELECT urunID, urun_ad, birim_fiyat, stok_miktari, urun_son_kullanma_tarihi FROM URUN ORDER BY urunID DESC";
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
    <title>Ürün Yönetimi</title>
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
    <div class="bg-white p-8 rounded-2xl shadow-lg max-w-4xl w-full">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Ürün Yönetimi</h1>
            <a href="add_urun.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                Yeni Ürün Ekle
            </a>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'added'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">Ürün başarıyla eklendi!</span>
                </div>
            <?php elseif ($_GET['status'] == 'updated'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">Ürün başarıyla güncellendi!</span>
                </div>
            <?php elseif ($_GET['status'] == 'deleted'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">Ürün başarıyla silindi!</span>
                </div>
            <?php elseif ($_GET['status'] == 'error'): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">Bir hata oluştu.</span>
                </div>
            <?php endif; ?>
        <?php elseif (isset($_GET['error']) && $_GET['error'] == 'yetkisiz_erisim'): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Bu sayfaya erişim yetkiniz bulunmamaktadır.</span>
            </div>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <p class="text-gray-600 text-center text-lg">Henüz hiç ürün bulunmamaktadır.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-lg shadow-md">
                    <thead>
                        <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left rounded-tl-lg">ID</th>
                            <th class="py-3 px-6 text-left">Ürün Adı</th>
                            <th class="py-3 px-6 text-left">Birim Fiyatı</th>
                            <th class="py-3 px-6 text-left">Stok Miktarı</th>
                            <th class="py-3 px-6 text-left">Son Kullanma Tarihi</th>
                            <th class="py-3 px-6 text-center rounded-tr-lg">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm font-light">
                        <?php foreach ($products as $product): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo htmlspecialchars($product['urunID']); ?></td>
                                <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($product['urun_ad']); ?></td>
                                <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($product['birim_fiyat']); ?> TL</td>
                                <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($product['stok_miktari']); ?></td>
                                <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($product['urun_son_kullanma_tarihi']); ?></td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex item-center justify-center space-x-2">
                                        <a href="edit_urun.php?id=<?php echo $product['urunID']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1 px-3 rounded-lg text-xs transition duration-300 ease-in-out transform hover:scale-105">Düzenle</a>
                                        <a href="delete_urun.php?id=<?php echo $product['urunID']; ?>" onclick="return confirm('Bu ürünü silmek istediğinizden emin misiniz?')" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded-lg text-xs transition duration-300 ease-in-out transform hover:scale-105">Sil</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <div class="mt-8 text-center">
            <a href="main.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                Ana Sayfaya Dön
            </a>
        </div>
    </div>
</body>
</html>
