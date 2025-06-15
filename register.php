<?php
// Oturumu başlat
session_start();

// Veritabanı bağlantısını dahil et
include 'db.php';

$message = '';
$message_type = '';

// Form POST metodu ile gönderildiğinde kayıt işlemini yap
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri al ve temizle
    $kullanici_adi = htmlspecialchars($_POST['kullanici_adi']);
    $email = htmlspecialchars($_POST['email']);
    $ad = htmlspecialchars($_POST['ad']);
    $soyad = htmlspecialchars($_POST['soyad']);
    $gsm_no = htmlspecialchars($_POST['gsm_no']);
    $dogum_tarihi = htmlspecialchars($_POST['dogum_tarihi']);
    
    // Şifreyi PASSWORD_BCRYPT algoritması ile hash'le
    $sifre = password_hash($_POST['sifre'], PASSWORD_BCRYPT); 

    // Kullanıcı adı veya e-postanın zaten var olup olmadığını kontrol et
    $check_sql = "SELECT kullaniciID FROM KULLANICI WHERE kullanici_adi = ? OR email = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("ss", $kullanici_adi, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Kullanıcı adı veya e-posta zaten varsa hata mesajı göster
        $message = "Hata: Kullanıcı adı veya e-posta zaten kayıtlı.";
        $message_type = "error";
    } else {
        // Yeni kullanıcıyı KULLANICI tablosuna eklemek için SQL sorgusu
        $sql = "INSERT INTO KULLANICI (kullanici_adi, email, ad, soyad, gsm_no, dogum_tarihi, sifre)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        // Sorguyu hazırla
        $stmt = $mysqli->prepare($sql);
        // Parametreleri bağla
        $stmt->bind_param("sssssss", $kullanici_adi, $email, $ad, $soyad, $gsm_no, $dogum_tarihi, $sifre);
        
        // Sorguyu çalıştır
        if ($stmt->execute()) {
            $new_user_id = $mysqli->insert_id; // Yeni eklenen kullanıcının ID'sini al

            // Yeni kullanıcıya 'Müşteri' rolünü atama
            $rol_id_sql = "SELECT rolID FROM ROL WHERE rol_adi = 'Müşteri'";
            $rol_result = $mysqli->query($rol_id_sql);
            if ($rol_result && $rol_result->num_rows > 0) {
                $rol_row = $rol_result->fetch_assoc();
                $musteri_rol_id = $rol_row['rolID'];

                $assign_role_sql = "INSERT INTO KULLANICI_ROL (kullaniciID, rolID) VALUES (?, ?)";
                $assign_stmt = $mysqli->prepare($assign_role_sql);
                $assign_stmt->bind_param("ii", $new_user_id, $musteri_rol_id);
                $assign_stmt->execute();
                $assign_stmt->close();
            } else {
                // 'Müşteri' rolü bulunamazsa bir hata mesajı loglayabiliriz veya gösterebiliriz.
                error_log("Hata: 'Müşteri' rolü veritabanında bulunamadı.");
            }

            $message = "Kayıt başarılı! Şimdi <a href='login.php' class='text-blue-600 hover:underline'>giriş yapabilirsiniz</a>.";
            $message_type = "success";
        } else {
            // Hata durumunda hata mesajını göster
            $message = "Kayıt sırasında bir hata oluştu: " . $stmt->error;
            $message_type = "error";
        }
    }
    // Hazırlanmış ifadeyi kapat
    $stmt->close();
    if (isset($check_stmt)) {
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
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
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Kayıt Ol</h1>
        <?php if (isset($message)): ?>
            <div class="
                <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>
        <form method="POST" action="register.php" class="space-y-4">
            <div>
                <label for="kullanici_adi" class="block text-gray-700 text-sm font-semibold mb-2">Kullanıcı Adı:</label>
                <input type="text" id="kullanici_adi" name="kullanici_adi" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">E-posta:</label>
                <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="ad" class="block text-gray-700 text-sm font-semibold mb-2">Ad:</label>
                <input type="text" id="ad" name="ad" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="soyad" class="block text-gray-700 text-sm font-semibold mb-2">Soyad:</label>
                <input type="text" id="soyad" name="soyad" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="gsm_no" class="block text-gray-700 text-sm font-semibold mb-2">GSM No (Örn: 5xx1234567):</label>
                <input type="text" id="gsm_no" name="gsm_no" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="dogum_tarihi" class="block text-gray-700 text-sm font-semibold mb-2">Doğum Tarihi:</label>
                <input type="date" id="dogum_tarihi" name="dogum_tarihi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="sifre" class="block text-gray-700 text-sm font-semibold mb-2">Şifre:</label>
                <input type="password" id="sifre" name="sifre" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                Kayıt Ol
            </button>
        </form>
        <p class="mt-6 text-center text-gray-600">Zaten bir hesabınız var mı? <a href="login.php" class="text-blue-600 hover:underline">Giriş Yap</a></p>
    </div>
</body>
</html>
