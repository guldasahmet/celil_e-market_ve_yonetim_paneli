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
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli</title>
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
            <h1 class="text-4xl font-bold text-gray-800 mb-8 text-center">Yönetim Paneli</h1>
            
            <?php if (isset($_GET['error']) && $_GET['error'] == 'yetkisiz_erisim'): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">Bu sayfaya erişim yetkiniz bulunmamaktadır.</span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Depo Yönetimi Kartı -->
                <div class="bg-blue-100 p-6 rounded-xl shadow-md flex flex-col items-center justify-center text-center transform hover:scale-105 transition duration-300">
                    <h2 class="text-2xl font-bold text-blue-800 mb-4">Depo Yönetimi</h2>
                    <p class="text-blue-700 mb-4">Depo bilgilerini görüntüle, ekle, düzenle veya sil.</p>
                    <a href="depo_yonetimi.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-300">
                        Depoları Yönet
                    </a>
                </div>

                <!-- Çalışan Yönetimi Kartı -->
                <div class="bg-green-100 p-6 rounded-xl shadow-md flex flex-col items-center justify-center text-center transform hover:scale-105 transition duration-300">
                    <h2 class="text-2xl font-bold text-green-800 mb-4">Çalışan Yönetimi</h2>
                    <p class="text-green-700 mb-4">Çalışan bilgilerini görüntüle, ekle, düzenle veya sil.</p>
                    <a href="calisan_yonetimi.php" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-300">
                        Çalışanları Yönet
                    </a>
                </div>

                <!-- Üretim Tesisi Yönetimi Kartı (Şimdi Aktif) -->
                <div class="bg-yellow-100 p-6 rounded-xl shadow-md flex flex-col items-center justify-center text-center transform hover:scale-105 transition duration-300">
                    <h2 class="text-2xl font-bold text-yellow-800 mb-4">Üretim Tesisi Yönetimi</h2>
                    <p class="text-yellow-700 mb-4">Üretim tesisi bilgilerini görüntüle, ekle, düzenle veya sil.</p>
                    <a href="uretim_tesisi_yonetimi.php" class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-300">
                        Tesisleri Yönet
                    </a>
                </div>
                 <!-- Tedarikçi Yönetimi Kartı (Gelecek) -->
                <div class="bg-red-100 p-6 rounded-xl shadow-md flex flex-col items-center justify-center text-center transform hover:scale-105 transition duration-300">
                    <h2 class="text-2xl font-bold text-red-800 mb-4">Tedarikçi Yönetimi</h2>
                    <p class="text-red-700 mb-4">Tedarikçi bilgilerini görüntüle, ekle, düzenle veya sil.</p>
                    <a href="#" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-300 opacity-50 cursor-not-allowed">
                        Tedarikçileri Yönet (Yakında)
                    </a>
                </div>
                <!-- Çiftlik Yönetimi Kartı (Gelecek) -->
                <div class="bg-orange-100 p-6 rounded-xl shadow-md flex flex-col items-center justify-center text-center transform hover:scale-105 transition duration-300">
                    <h2 class="text-2xl font-bold text-orange-800 mb-4">Çiftlik Yönetimi</h2>
                    <p class="text-orange-700 mb-4">Çiftlik bilgilerini görüntüle, ekle, düzenle veya sil.</p>
                    <a href="#" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-300 opacity-50 cursor-not-allowed">
                        Çiftlikleri Yönet (Yakında)
                    </a>
                </div>

                <!-- Ürün Yönetimi Kartı (Zaten Mevcut) -->
                <div class="bg-purple-100 p-6 rounded-xl shadow-md flex flex-col items-center justify-center text-center transform hover:scale-105 transition duration-300">
                    <h2 class="text-2xl font-bold text-purple-800 mb-4">Ürün Yönetimi</h2>
                    <p class="text-purple-700 mb-4">Ürün ekle, düzenle, sil ve stokları yönet.</p>
                    <a href="urun_yonetimi.php" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-300">
                        Ürünleri Yönet
                    </a>
                </div>
            </div>

            <div class="mt-8 text-center">
                <a href="main.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                    Profile Geri Dön
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date("Y"); ?> Süt Ürünleri Marketi. Tüm Hakları Saklıdır.</p>
    </footer>
</body>
</html>
