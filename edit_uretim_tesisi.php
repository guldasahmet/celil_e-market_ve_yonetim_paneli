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

$message = '';
$message_type = '';
$tesis_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$facility_data = null;

// Mevcut adresleri çek
$adresler_sql = "SELECT adresID, il, ilce, mahalle, sokak, bina_no FROM ADRES ORDER BY il, ilce, mahalle ASC";
$adresler_result = $mysqli->query($adresler_sql);
$adresler = [];
if ($adresler_result->num_rows > 0) {
    while($row = $adresler_result->fetch_assoc()) {
        $adresler[] = $row;
    }
}

// Mevcut çalışanları çek (sadece ad ve soyad)
$calisanlar_sql = "SELECT calisanID, calisan_ad, calisan_soyad FROM CALISAN ORDER BY calisan_ad, calisan_soyad ASC";
$calisanlar_result = $mysqli->query($calisanlar_sql);
$calisanlar = [];
if ($calisanlar_result->num_rows > 0) {
    while($row = $calisanlar_result->fetch_assoc()) {
        $calisanlar[] = $row;
    }
}


// Üretim tesisi bilgilerini çek
if ($tesis_id > 0) {
    $sql = "SELECT uretim_tesisID, tesis_adi, uretim_tesis_kapasite, adresID, calisanID FROM URETIM_TESISI WHERE uretim_tesisID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $tesis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $facility_data = $result->fetch_assoc();
    $stmt->close();

    if (!$facility_data) {
        $message = "Üretim tesisi bulunamadı.";
        $message_type = "error";
    }
} else {
    $message = "Geçersiz Tesis ID'si.";
    $message_type = "error";
}

// Form POST metodu ile gönderildiğinde üretim tesisi güncelleme işlemini yap
if ($_SERVER["REQUEST_METHOD"] == "POST" && $facility_data) {
    $tesis_adi = htmlspecialchars($_POST['tesis_adi']);
    $kapasite = !empty($_POST['kapasite']) ? intval($_POST['kapasite']) : NULL;
    $adresID = !empty($_POST['adresID']) ? intval($_POST['adresID']) : NULL;
    $calisanID = !empty($_POST['calisanID']) ? intval($_POST['calisanID']) : NULL;

    // Tesis adının başka bir tesis tarafından kullanılıp kullanılmadığını kontrol et
    $check_tesis_sql = "SELECT uretim_tesisID FROM URETIM_TESISI WHERE tesis_adi = ? AND uretim_tesisID != ?";
    $check_tesis_stmt = $mysqli->prepare($check_tesis_sql);
    $check_tesis_stmt->bind_param("si", $tesis_adi, $tesis_id);
    $check_tesis_stmt->execute();
    $check_tesis_result = $check_tesis_stmt->get_result();

    if ($check_tesis_result->num_rows > 0) {
        $message = "Hata: Bu tesis adı başka bir üretim tesisi tarafından kullanılıyor.";
        $message_type = "error";
    } else {
        // Üretim tesisi bilgilerini güncelle
        $update_sql = "UPDATE URETIM_TESISI SET tesis_adi = ?, adresID = ?, uretim_tesis_kapasite = ?, calisanID = ? WHERE uretim_tesisID = ?";
        $update_stmt = $mysqli->prepare($update_sql);
        
        // Parametre tiplerini ve değerleri call_user_func_array ile bağla
        $params = array($tesis_adi, $adresID, $kapasite, $calisanID, $tesis_id);
        $types = "siiii"; 
        
        call_user_func_array(array($update_stmt, 'bind_param'), array_merge(array($types), $params));
        
        if ($update_stmt->execute()) {
            header("Location: uretim_tesisi_yonetimi.php?status=updated");
            exit;
        } else {
            $message = "Üretim tesisi güncelleme sırasında bir hata oluştu: " . $update_stmt->error;
            $message_type = "error";
        }
        $update_stmt->close();
    }
    $check_tesis_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üretim Tesisi Düzenle</title>
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
                <?php if ($currentUser) { ?>
                    <a href="main.php" class="text-gray-700 hover:text-blue-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Profilim</a>
                    <a href="sepet.php" class="text-gray-700 hover:text-green-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Sepetim</a>
                    <?php if ($currentUser->hasRole('Çalışan') || $currentUser->hasRole('Admin')) { ?>
                        <a href="urun_yonetimi.php" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Ürün Yönetimi</a>
                        <a href="yonetim_paneli.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Yönetim Paneli</a>
                    <?php } ?>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Çıkış Yap</a>
                <?php } else { ?>
                    <a href="login.php" class="text-gray-700 hover:text-blue-600 font-semibold px-3 py-2 rounded-lg transition duration-300">Giriş Yap</a>
                    <a href="register.php" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Kayıt Ol</a>
                <?php } ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-4 flex-grow">
        <div class="bg-white p-8 rounded-2xl shadow-lg max-w-2xl w-full mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Üretim Tesisi Düzenle</h1>
            <?php if ($message) { ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php } ?>

            <?php if ($facility_data) { ?>
                <form method="POST" action="edit_uretim_tesisi.php?id=<?php echo $tesis_id; ?>" class="space-y-4">
                    <div>
                        <label for="tesis_adi" class="block text-gray-700 text-sm font-semibold mb-2">Tesis Adı:</label>
                        <input type="text" id="tesis_adi" name="tesis_adi" value="<?php echo htmlspecialchars($facility_data['tesis_adi']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="kapasite" class="block text-gray-700 text-sm font-semibold mb-2">Kapasite (kg/gün veya litre/gün):</label>
                        <input type="number" id="kapasite" name="kapasite" value="<?php echo htmlspecialchars($facility_data['uretim_tesis_kapasite']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="adresID" class="block text-gray-700 text-sm font-semibold mb-2">Adres:</label>
                        <select id="adresID" name="adresID" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Seçiniz (İsteğe Bağlı)</option>
                            <?php foreach ($adresler as $adres) { ?>
                                <option value="<?php echo htmlspecialchars($adres['adresID']); ?>"
                                    <?php echo ($adres['adresID'] == $facility_data['adresID']) ? 'selected' : ''; ?>>
                                    <?php 
                                        $adres_str = "";
                                        if ($adres['il']) $adres_str .= htmlspecialchars($adres['il']);
                                        if ($adres['ilce']) $adres_str .= ", " . htmlspecialchars($adres['ilce']);
                                        if ($adres['mahalle']) $adres_str .= ", " . htmlspecialchars($adres['mahalle']);
                                        if ($adres['sokak']) $adres_str .= ", " . htmlspecialchars($adres['sokak']);
                                        if ($adres['bina_no']) $adres_str .= " No:" . htmlspecialchars($adres['bina_no']);
                                        echo $adres_str;
                                    ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label for="calisanID" class="block text-gray-700 text-sm font-semibold mb-2">Sorumlu Çalışan:</label>
                        <select id="calisanID" name="calisanID" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Seçiniz (İsteğe Bağlı)</option>
                            <?php foreach ($calisanlar as $calisan) { ?>
                                <option value="<?php echo htmlspecialchars($calisan['calisanID']); ?>"
                                    <?php echo ($calisan['calisanID'] == $facility_data['calisanID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($calisan['calisan_ad'] . " " . $calisan['calisan_soyad']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="flex justify-between items-center gap-4 mt-6">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                            Tesis Bilgilerini Güncelle
                        </button>
                        <a href="uretim_tesisi_yonetimi.php" class="w-full text-center bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                            İptal
                        </a>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date("Y"); ?> Süt Ürünleri Marketi. Tüm Hakları Saklıdır.</p>
    </footer>
</body>
</html>
