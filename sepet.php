<?php
// Oturumu başlat
session_start();

// Veritabanı bağlantısını dahil et
include 'db.php';
// User sınıfını dahil et (rol kontrolü için)
include 'User.php';

// Kullanıcı oturumu başlatılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// User nesnesini oluştur
$currentUser = new User($mysqli, $_SESSION['user_id']);

// Sadece 'Müşteri' rolüne sahip kullanıcılar sepete erişebilir
if (!$currentUser->hasRole('Müşteri')) {
    header("Location: main.php?error=yetkisiz_erisim_sepet");
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$cart_total = 0;
$message = '';
$message_type = '';

// GET isteği ile gelen status mesajlarını kontrol et
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'guncellendi') {
        $message = "Sepetiniz başarıyla güncellendi!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'silindi') {
        $message = "Ürün sepetinizden kaldırıldı!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'stok_yetersiz') {
        $message = "Hata: İstenilen miktarda ürün stokta bulunmamaktadır.";
        $message_type = "error";
    } elseif ($_GET['status'] == 'hata') {
        $message = "Bir hata oluştu.";
        $message_type = "error";
    } elseif ($_GET['status'] == 'bos') {
        $message = "Sepetiniz boşaltıldı!";
        $message_type = "success";
    }
}

// Kullanıcının sepetini ve sepetindeki ürünleri veritabanından çek
$sql = "SELECT SU.sepet_urunID, SU.adet, SU.anlik_birim_fiyat,
               U.urunID, U.urun_ad, U.stok_miktari,
               M.dosya_yolu AS image_path
        FROM SEPET S
        JOIN SEPET_URUN SU ON S.sepetID = SU.sepetID
        JOIN URUN U ON SU.urunID = U.urunID
        LEFT JOIN MEDYA_BAGLANTISI MB ON U.urunID = MB.varlikID AND MB.varlik_tipi = 'urun' AND MB.medya_rolu = 'ana'
        LEFT JOIN MEDYA M ON MB.medyaID = M.medyaID
        WHERE S.kullaniciID = ? AND S.sepet_durum = 'aktif'"; // Sadece aktif sepetleri çekiyoruz
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $cart_total += ($row['adet'] * $row['anlik_birim_fiyat']);
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sepetim - Süt Ürünleri Marketi</title>
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
        <div class="bg-white p-8 rounded-2xl shadow-lg max-w-4xl w-full mx-auto">
            <h1 class="text-4xl font-bold text-gray-800 mb-8 text-center">Sepetim</h1>
            
            <?php if ($message): ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <p class="text-gray-600 text-center text-xl mt-10">Sepetinizde ürün bulunmamaktadır. Hemen ürünleri incelemeye başlayın!</p>
                <div class="mt-8 text-center">
                    <a href="urun_listesi.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                        Ürünlere Göz At
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto mb-8">
                    <table class="min-w-full bg-white rounded-lg">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Ürün</th>
                                <th class="py-3 px-6 text-left">Fiyat</th>
                                <th class="py-3 px-6 text-center">Miktar</th>
                                <th class="py-3 px-6 text-left">Toplam</th>
                                <th class="py-3 px-6 text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm font-light">
                            <?php foreach ($cart_items as $item): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6 text-left whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="mr-3">
                                                <?php if ($item['image_path']): ?>
                                                    <img class="w-12 h-12 rounded-lg object-cover" src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['urun_ad']); ?>">
                                                <?php else: ?>
                                                    <div class="w-12 h-12 rounded-lg bg-gray-200 flex items-center justify-center text-xs">Görsel</div>
                                                <?php endif; ?>
                                            </div>
                                            <span class="font-medium"><?php echo htmlspecialchars($item['urun_ad']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars(number_format($item['anlik_birim_fiyat'], 2, ',', '.')); ?> TL</td>
                                    <td class="py-3 px-6 text-center">
                                        <form action="sepet_islem.php" method="POST" class="flex items-center justify-center space-x-2">
                                            <input type="hidden" name="action" value="update_quantity">
                                            <input type="hidden" name="sepet_urun_id" value="<?php echo $item['sepet_urunID']; ?>">
                                            <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['adet']); ?>" min="1" max="<?php echo htmlspecialchars($item['stok_miktari']); ?>" class="w-20 px-2 py-1 border border-gray-300 rounded-lg text-center focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-1 px-2 rounded-lg text-xs transition duration-200">Güncelle</button>
                                        </form>
                                    </td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars(number_format($item['adet'] * $item['anlik_birim_fiyat'], 2, ',', '.')); ?> TL</td>
                                    <td class="py-3 px-6 text-center">
                                        <form action="sepet_islem.php" method="POST" onsubmit="return confirm('Bu ürünü sepetinizden kaldırmak istediğinizden emin misiniz?');">
                                            <input type="hidden" name="action" value="remove_from_cart">
                                            <input type="hidden" name="sepet_urun_id" value="<?php echo $item['sepet_urunID']; ?>">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded-lg text-xs transition duration-200">Kaldır</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end items-center mb-6">
                    <span class="text-2xl font-bold text-gray-800">Toplam Tutar: <?php echo htmlspecialchars(number_format($cart_total, 2, ',', '.')); ?> TL</span>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <a href="urun_listesi.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                        Alışverişe Devam Et
                    </a>
                    <form action="sepet_islem.php" method="POST" onsubmit="return confirm('Sepetinizi tamamen boşaltmak istediğinizden emin misiniz?');">
                        <input type="hidden" name="action" value="clear_cart">
                        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                            Sepeti Boşalt
                        </button>
                    </form>
                    <a href="siparis_tamamla.php" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                        Siparişi Tamamla
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date("Y"); ?> Süt Ürünleri Marketi. Tüm Hakları Saklıdır.</p>
    </footer>
</body>
</html>
