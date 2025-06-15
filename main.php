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

// Oturumdan kullanıcı ID'sini al
$user_id = $_SESSION['user_id'];

// User nesnesini oluştur ve kullanıcının rollerini yükle
$currentUser = new User($mysqli, $user_id);

// Kullanıcı bilgilerini veritabanından çekmek için SQL sorgusu
$sql = "SELECT kullanici_adi, email, ad, soyad, gsm_no, dogum_tarihi FROM KULLANICI WHERE kullaniciID = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Kullanıcı bilgileri bulunamazsa (ki bu durumda bir sorun var demektir)
if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'profile_updated') {
        $message = "Profil bilgileriniz başarıyla güncellendi!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'password_updated') {
        $message = "Şifreniz başarıyla güncellendi!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'error') {
        $message = "Bir hata oluştu.";
        $message_type = "error";
    } elseif ($_GET['status'] == 'account_deleted') {
        $message = "Hesabınız başarıyla silindi. Giriş sayfasına yönlendiriliyorsunuz...";
        $message_type = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim</title>
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
                    <!-- Giriş yapmış kullanıcılar için -->
                    <a href="main.php" class="text-gray-700 hover:text-blue-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Profilim</a>
                    <a href="sepet.php" class="text-gray-700 hover:text-green-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Sepetim</a>
                    <?php if ($currentUser->hasRole('Çalışan') || $currentUser->hasRole('Admin')): ?>
                        <a href="urun_yonetimi.php" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Ürün Yönetimi</a>
                        <a href="yonetim_paneli.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Yönetim Paneli</a>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Çıkış Yap</a>
                <?php else: ?>
                    <!-- Bu kısım main.php'de giriş yapmış kullanıcılar olduğu için normalde görünmez -->
                    <a href="login.php" class="text-gray-700 hover:text-blue-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Giriş Yap</a>
                    <a href="register.php" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Kayıt Ol</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-4 flex-grow">
        <div class="bg-white p-8 rounded-2xl shadow-lg max-w-2xl w-full mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Profil Bilgileriniz</h1>
            
            <?php if ($message): ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <p class="text-lg text-gray-700 mb-4 text-center">Hoş geldiniz, <span class="font-semibold"><?php echo htmlspecialchars($user['ad'] . " " . $user['soyad']); ?></span>!</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700 mb-8">
                <div class="flex items-center">
                    <strong class="w-32">Kullanıcı Adı:</strong> <span class="flex-1"><?php echo htmlspecialchars($user['kullanici_adi']); ?></span>
                </div>
                <div class="flex items-center">
                    <strong class="w-32">E-posta:</strong> <span class="flex-1"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="flex items-center">
                    <strong class="w-32">Ad:</strong> <span class="flex-1"><?php echo htmlspecialchars($user['ad']); ?></span>
                </div>
                <div class="flex items-center">
                    <strong class="w-32">Soyad:</strong> <span class="flex-1"><?php echo htmlspecialchars($user['soyad']); ?></span>
                </div>
                <div class="flex items-center">
                    <strong class="w-32">GSM No:</strong> <span class="flex-1"><?php echo htmlspecialchars($user['gsm_no']); ?></span>
                </div>
                <div class="flex items-center">
                    <strong class="w-32">Doğum Tarihi:</strong> <span class="flex-1"><?php echo htmlspecialchars($user['dogum_tarihi']); ?></span>
                </div>
                <div class="flex items-center md:col-span-2">
                    <strong class="w-32">Rolleriniz:</strong> 
                    <span class="flex-1">
                        <?php 
                            // Kullanıcının rollerini virgülle ayırarak göster
                            echo htmlspecialchars(implode(', ', $currentUser->getRoles())); 
                        ?>
                    </span>
                </div>
            </div>

            <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">Hesap İşlemleri</h2>
            <div class="flex flex-col md:flex-row justify-center gap-4">
                <a href="edit_profile.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md text-center">
                    Profili Düzenle
                </a>
                <?php if ($currentUser->hasRole('Çalışan') || $currentUser->hasRole('Admin')): ?>
                    <a href="urun_yonetimi.php" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md text-center">
                        Ürünleri Yönet
                    </a>
                <?php endif; ?>
                <a href="delete_profile.php" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md text-center" onclick="return confirm('Hesabınızı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')">
                    Hesabı Sil
                </a>
                <a href="logout.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md text-center">
                    Çıkış Yap
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
