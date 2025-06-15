<?php
// Oturumu başlat
session_start();

// Veritabanı bağlantısını dahil et
include 'db.php';
// User sınıfını dahil et (rol kontrolü için)
include 'User.php'; // User.php dosyasının dahil edildiğinden emin olun

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
// Bu kısım artık rol bazlı yönlendirmeye devredildiği için, sadece ana sayfa olarak urun_listesi.php'ye yönlendirebiliriz.
// Ancak daha detaylı rol kontrolü aşağıda yapılacak.
if (isset($_SESSION['user_id'])) {
    // Giriş yapmış kullanıcılar için User nesnesini oluştur
    $currentUserCheck = new User($mysqli, $_SESSION['user_id']);
    if ($currentUserCheck->hasRole('Müşteri')) {
        header("Location: urun_listesi.php"); // Müşteriler ürün listesine
    } else {
        header("Location: main.php"); // Diğer roller ana sayfaya
    }
    exit;
}

// Form POST metodu ile gönderildiğinde giriş işlemini yap
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen kullanıcı adı ve şifreyi al ve temizle
    $kullanici_adi = htmlspecialchars($_POST['kullanici_adi']);
    $sifre = $_POST['sifre'];

    // Veritabanından kullanıcı bilgilerini çekmek için SQL sorgusu
    $sql = "SELECT kullaniciID, kullanici_adi, sifre FROM KULLANICI WHERE kullanici_adi = ?";
    
    // Sorguyu hazırla
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $kullanici_adi);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Kullanıcı bulunduysa ve şifre doğruysa
    if ($user && password_verify($sifre, $user['sifre'])) {
        // Oturum değişkenlerini ayarla
        $_SESSION['user_id'] = $user['kullaniciID'];
        $_SESSION['user_name'] = $user['kullanici_adi'];
        
        // Kullanıcıya yeni bir oturum kimliği ata (Session Fixation önleme)
        session_regenerate_id(true); 

        // Kullanıcının rollerini kontrol etmek için User nesnesini oluştur
        $loggedInUser = new User($mysqli, $_SESSION['user_id']);

        // Rol bazlı yönlendirme
        if ($loggedInUser->hasRole('Müşteri')) {
            header("Location: urun_listesi.php"); // Müşteriler doğrudan ürün listesine
        } else {
            header("Location: main.php"); // Diğer roller (Çalışan, Admin) main.php'ye
        }
        exit;
    } else {
        // Kullanıcı adı veya şifre hatalı ise mesaj göster
        $error_message = "Kullanıcı adı veya şifre hatalı.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md w-full">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Giriş Yap</h1>
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        <form method="POST" action="login.php" class="space-y-6">
            <div>
                <label for="kullanici_adi" class="block text-gray-700 text-sm font-semibold mb-2">Kullanıcı Adı:</label>
                <input type="text" id="kullanici_adi" name="kullanici_adi" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="sifre" class="block text-gray-700 text-sm font-semibold mb-2">Şifre:</label>
                <input type="password" id="sifre" name="sifre" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                Giriş Yap
            </button>
        </form>
        <p class="mt-6 text-center text-gray-600">Hesabınız yok mu? <a href="register.php" class="text-blue-600 hover:underline">Kayıt Ol</a></p>
    </div>
</body>
</html>
