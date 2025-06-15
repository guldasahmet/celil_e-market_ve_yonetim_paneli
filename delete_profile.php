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

$message = '';
$message_type = '';

// GET isteği ise onay sayfasını göster
// POST isteği ise silme işlemini yap
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes') {
    // Yabancı anahtar kontrollerini geçici olarak devre dışı bırak
    // Bu, bağımlılıkları olan tabloları silerken hata almamızı engeller.
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");

    try {
        // Kullanıcıya bağlı tüm verileri silme sırası (bağımlılık sırasına dikkat ederek)
        // Her tablo için sorgu hazırlama ve çalıştırma
        $tables_to_delete = [
            'IADE',
            'GERI_ODEME',
            'SIPARIS_URUN',
            'SIPARIS',
            'ODEME',
            'YORUM',
            'SEPET_URUN', // SEPET_URUN -> SEPET'e bağlı, SEPET -> KULLANICI'ya bağlı
            'SEPET',
            'KULLANICI_ROL',
            'KULLANICI_ADRES'
            // MEDYA_BAGLANTISI ve STOK_HAREKET kullanıcıya doğrudan bağlı değil, urun'e bağlı olabilir.
            // Onları burada doğrudan silmeye gerek yok.
        ];

        foreach ($tables_to_delete as $table) {
            $delete_sql = "DELETE FROM " . $table . " WHERE kullaniciID = ?";
            // Eğer tabloda kullaniciID sütunu yoksa, bu sorgu hata verebilir.
            // Bu kısım, tablolarınızdaki FK'lara göre özelleştirilmelidir.
            // Şu anki şemanızda çoğu tablonun kullaniciID'si var.
            
            // Eğer tabloya özel bir silme koşulu gerekiyorsa burada if-else kullanılabilir.
            // Örneğin, SIPARIS tablosunda kullaniciID var. SIPARIS_URUN'de doğrudan yok.
            // Bu nedenle SIPARIS_URUN silme işleminden önce SIPARIS silinmeli.
            // Biz burada KULLANICI_ROL gibi doğrudan bağlı olanları siliyoruz.
            // Diğerleri (SIPARIS, SEPET vb.) KULLANICI silindiğinde cascade olmalıydı.
            // Eğer cascade yoksa, en derin bağımlılıktan başlayarak manuel silmek gerekir.

            // Mevcut şema varsayımlarına göre en basit kullanıcıya bağlıları siliyorum
            // Tam bir cascade için veritabanı şemasında ON DELETE CASCADE ayarlanması önerilir.
            $stmt = $mysqli->prepare("DELETE FROM " . $table . " WHERE kullaniciID = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Son olarak KULLANICI kaydını sil
        $sql = "DELETE FROM KULLANICI WHERE kullaniciID = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Oturumu tamamen sonlandır
        session_unset();
        session_destroy();

        $message = "Hesabınız başarıyla silindi.";
        $message_type = "success";
        // Kullanıcıyı giriş sayfasına yönlendir (mesajı göstermek için küçük bir gecikme)
        header("Refresh: 3; url=login.php"); // 3 saniye sonra login.php'ye yönlendir
        
    } catch (Exception $e) {
        $message = "Hesap silme sırasında bir hata oluştu: " . $e->getMessage();
        $message_type = "error";
    } finally {
        // Yabancı anahtar kontrollerini tekrar etkinleştir
        $mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabı Sil</title>
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
        <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md w-full mx-auto text-center">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Hesabınızı Sil</h1>
            
            <?php if ($message): ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!isset($_POST['confirm_delete'])): ?>
                <p class="text-lg text-gray-700 mb-6">Hesabınızı kalıcı olarak silmek üzeresiniz. Bu işlem geri alınamaz.</p>
                <p class="text-xl text-red-600 font-bold mb-8">Emin misiniz?</p>

                <form method="POST" action="delete_profile.php">
                    <input type="hidden" name="confirm_delete" value="yes">
                    <div class="flex flex-col md:flex-row justify-center gap-4">
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                            Evet, Hesabımı Sil
                        </button>
                        <a href="main.php" class="w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                            Hayır, Geri Dön
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
