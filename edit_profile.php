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
$currentUser = new User($mysqli, $user_id); // Mevcut kullanıcıyı kontrol etmek için

$message = '';
$message_type = '';

// Kullanıcı bilgilerini önden doldurmak için çek
$sql = "SELECT kullanici_adi, email, ad, soyad, gsm_no, dogum_tarihi FROM KULLANICI WHERE kullaniciID = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

if (!$user_data) {
    // Kullanıcı bilgileri bulunamazsa oturumu kapat ve yönlendir
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Form POST metodu ile gönderildiğinde profil güncelleme işlemini yap
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = htmlspecialchars($_POST['kullanici_adi']);
    $email = htmlspecialchars($_POST['email']);
    $ad = htmlspecialchars($_POST['ad']);
    $soyad = htmlspecialchars($_POST['soyad']);
    $gsm_no = htmlspecialchars($_POST['gsm_no']);
    $dogum_tarihi = htmlspecialchars($_POST['dogum_tarihi']);
    $password = $_POST['password']; // Yeni şifre (boş bırakılabilir)
    $password_confirm = $_POST['password_confirm']; // Yeni şifre tekrarı

    // Kullanıcı adı veya e-postanın başka bir kullanıcı tarafından kullanılıp kullanılmadığını kontrol et
    $check_duplicate_sql = "SELECT kullaniciID FROM KULLANICI WHERE (kullanici_adi = ? OR email = ?) AND kullaniciID != ?";
    $check_duplicate_stmt = $mysqli->prepare($check_duplicate_sql);
    $check_duplicate_stmt->bind_param("ssi", $kullanici_adi, $email, $user_id);
    $check_duplicate_stmt->execute();
    $duplicate_result = $check_duplicate_stmt->get_result();

    if ($duplicate_result->num_rows > 0) {
        $message = "Hata: Kullanıcı adı veya e-posta zaten kullanımda.";
        $message_type = "error";
    } elseif (!empty($password) && $password !== $password_confirm) {
        $message = "Hata: Şifreler eşleşmiyor.";
        $message_type = "error";
    } else {
        // SQL sorgusunu hazırla
        $update_sql = "UPDATE KULLANICI SET kullanici_adi = ?, email = ?, ad = ?, soyad = ?, gsm_no = ?, dogum_tarihi = ? WHERE kullaniciID = ?";
        
        // Eğer şifre de güncellenecekse sorguyu değiştir
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $update_sql = "UPDATE KULLANICI SET kullanici_adi = ?, email = ?, ad = ?, soyad = ?, gsm_no = ?, dogum_tarihi = ?, sifre = ? WHERE kullaniciID = ?";
            $stmt_update = $mysqli->prepare($update_sql);
            $stmt_update->bind_param("sssssssi", $kullanici_adi, $email, $ad, $soyad, $gsm_no, $dogum_tarihi, $hashed_password, $user_id);
        } else {
            $stmt_update = $mysqli->prepare($update_sql);
            $stmt_update->bind_param("ssssssi", $kullanici_adi, $email, $ad, $soyad, $gsm_no, $dogum_tarihi, $user_id);
        }

        if ($stmt_update->execute()) {
            // Güncelleme sonrası kullanıcı bilgilerini tekrar çek (navbar'daki ismin güncellenmesi için vs.)
            $sql = "SELECT kullanici_adi, email, ad, soyad, gsm_no, dogum_tarihi FROM KULLANICI WHERE kullaniciID = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();

            // Eğer şifre güncellendiyse ayrı bir mesaj göster
            if (!empty($password)) {
                header("Location: main.php?status=password_updated");
            } else {
                header("Location: main.php?status=profile_updated");
            }
            exit;
        } else {
            $message = "Profil güncelleme sırasında bir hata oluştu: " . $stmt_update->error;
            $message_type = "error";
        }
        $stmt_update->close();
    }
    $check_duplicate_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profili Düzenle</title>
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
        <div class="bg-white p-8 rounded-2xl shadow-lg max-w-2xl w-full mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Profili Düzenle</h1>
            
            <?php if ($message): ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="edit_profile.php" class="space-y-4">
                <div>
                    <label for="kullanici_adi" class="block text-gray-700 text-sm font-semibold mb-2">Kullanıcı Adı:</label>
                    <input type="text" id="kullanici_adi" name="kullanici_adi" value="<?php echo htmlspecialchars($user_data['kullanici_adi']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">E-posta:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="ad" class="block text-gray-700 text-sm font-semibold mb-2">Ad:</label>
                    <input type="text" id="ad" name="ad" value="<?php echo htmlspecialchars($user_data['ad']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="soyad" class="block text-gray-700 text-sm font-semibold mb-2">Soyad:</label>
                    <input type="text" id="soyad" name="soyad" value="<?php echo htmlspecialchars($user_data['soyad']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="gsm_no" class="block text-gray-700 text-sm font-semibold mb-2">GSM No:</label>
                    <input type="text" id="gsm_no" name="gsm_no" value="<?php echo htmlspecialchars($user_data['gsm_no']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="dogum_tarihi" class="block text-gray-700 text-sm font-semibold mb-2">Doğum Tarihi:</label>
                    <input type="date" id="dogum_tarihi" name="dogum_tarihi" value="<?php echo htmlspecialchars($user_data['dogum_tarihi']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="border-t border-gray-200 pt-4 mt-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Şifre Değiştir (İsteğe Bağlı)</h2>
                    <div>
                        <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Yeni Şifre:</label>
                        <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Şifreyi değiştirmek istemiyorsanız boş bırakın.</p>
                    </div>
                    <div class="mt-4">
                        <label for="password_confirm" class="block text-gray-700 text-sm font-semibold mb-2">Yeni Şifre Tekrarı:</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-between items-center gap-4 mt-6">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                        Profili Güncelle
                    </button>
                    <a href="main.php" class="w-full text-center bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
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
