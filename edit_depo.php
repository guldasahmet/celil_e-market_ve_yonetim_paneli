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
$depo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$depo_data = null;

// Mevcut çalışanları çek (depo sorumlusu atamak için)
$calisanlar_sql = "SELECT calisanID, calisan_ad, calisan_soyad FROM CALISAN ORDER BY calisan_ad ASC";
$calisanlar_result = $mysqli->query($calisanlar_sql);
$calisanlar = [];
if ($calisanlar_result->num_rows > 0) {
    while($row = $calisanlar_result->fetch_assoc()) {
        $calisanlar[] = $row;
    }
}

// Depo bilgilerini ve adresini çek
if ($depo_id > 0) {
    $sql = "SELECT D.depoID, D.depo_ad, D.kapasite, D.depo_sorumlusu_calisanID, D.adresID,
                   A.il, A.ilce, A.mahalle, A.sokak, A.bina_no, A.adres_detay
            FROM DEPO D
            JOIN ADRES A ON D.adresID = A.adresID
            WHERE D.depoID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $depo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $depo_data = $result->fetch_assoc();
    $stmt->close();

    if (!$depo_data) {
        $message = "Depo bulunamadı.";
        $message_type = "error";
    }
} else {
    $message = "Geçersiz Depo ID'si.";
    $message_type = "error";
}

// Form POST metodu ile gönderildiğinde depo güncelleme işlemini yap
if ($_SERVER["REQUEST_METHOD"] == "POST" && $depo_data) {
    $depo_ad = htmlspecialchars($_POST['depo_ad']);
    $kapasite = intval($_POST['kapasite']);
    $depo_sorumlusu_calisanID = !empty($_POST['depo_sorumlusu_calisanID']) ? intval($_POST['depo_sorumlusu_calisanID']) : NULL;
    
    // Adres bilgileri
    $il = htmlspecialchars($_POST['il']);
    $ilce = htmlspecialchars($_POST['ilce']);
    $mahalle = htmlspecialchars($_POST['mahalle']);
    $sokak = htmlspecialchars($_POST['sokak']);
    $bina_no = htmlspecialchars($_POST['bina_no']);
    $adres_detay = htmlspecialchars($_POST['adres_detay']);

    // Adres bilgilerini güncelle
    $update_adres_sql = "UPDATE ADRES SET il = ?, ilce = ?, mahalle = ?, sokak = ?, bina_no = ?, adres_detay = ? WHERE adresID = ?";
    $update_adres_stmt = $mysqli->prepare($update_adres_sql);
    $update_adres_stmt->bind_param("ssssssi", $il, $ilce, $mahalle, $sokak, $bina_no, $adres_detay, $depo_data['adresID']);
    $adres_update_success = $update_adres_stmt->execute();
    $update_adres_stmt->close();

    // Depo bilgilerini güncelle
    $update_depo_sql = "UPDATE DEPO SET depo_ad = ?, kapasite = ?, depo_sorumlusu_calisanID = ? WHERE depoID = ?";
    $update_depo_stmt = $mysqli->prepare($update_depo_sql);
    // depo_sorumlusu_calisanID için dinamik tip bağlama
    if ($depo_sorumlusu_calisanID === NULL) {
        $update_depo_stmt->bind_param("siii", $depo_ad, $kapasite, $depo_sorumlusu_calisanID, $depo_id); // 'i' olarak bağla, PHP 8.1+ NULL'u int'e dönüştürür.
    } else {
        $update_depo_stmt->bind_param("siii", $depo_ad, $kapasite, $depo_sorumlusu_calisanID, $depo_id);
    }
    
    $depo_update_success = $update_depo_stmt->execute();
    $update_depo_stmt->close();

    if ($adres_update_success && $depo_update_success) {
        header("Location: depo_yonetimi.php?status=updated");
        exit;
    } else {
        $message = "Depo güncelleme sırasında bir hata oluştu. Adres Hatası: " . ($adres_update_success ? "Yok" : $mysqli->error) . " Depo Hatası: " . ($depo_update_success ? "Yok" : $mysqli->error);
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depo Düzenle</title>
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
        <div class="bg-white p-8 rounded-2xl shadow-lg max-w-2xl w-full mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Depo Düzenle</h1>
            <?php if ($message): ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($depo_data): ?>
                <form method="POST" action="edit_depo.php?id=<?php echo $depo_id; ?>" class="space-y-4">
                    <h2 class="text-xl font-bold text-gray-700 border-b pb-2 mb-4">Depo Bilgileri</h2>
                    <div>
                        <label for="depo_ad" class="block text-gray-700 text-sm font-semibold mb-2">Depo Adı:</label>
                        <input type="text" id="depo_ad" name="depo_ad" value="<?php echo htmlspecialchars($depo_data['depo_ad']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="kapasite" class="block text-gray-700 text-sm font-semibold mb-2">Kapasite:</label>
                        <input type="number" id="kapasite" name="kapasite" value="<?php echo htmlspecialchars($depo_data['kapasite']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="depo_sorumlusu_calisanID" class="block text-gray-700 text-sm font-semibold mb-2">Depo Sorumlusu (Çalışan):</label>
                        <select id="depo_sorumlusu_calisanID" name="depo_sorumlusu_calisanID" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Atama Yapma</option>
                            <?php foreach ($calisanlar as $calisan): ?>
                                <option value="<?php echo htmlspecialchars($calisan['calisanID']); ?>"
                                    <?php echo ($calisan['calisanID'] == $depo_data['depo_sorumlusu_calisanID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($calisan['calisan_ad'] . " " . $calisan['calisan_soyad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h2 class="text-xl font-bold text-gray-700 border-b pb-2 mb-4 mt-8">Adres Bilgileri</h2>
                    <div>
                        <label for="il" class="block text-gray-700 text-sm font-semibold mb-2">İl:</label>
                        <input type="text" id="il" name="il" value="<?php echo htmlspecialchars($depo_data['il']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="ilce" class="block text-gray-700 text-sm font-semibold mb-2">İlçe:</label>
                        <input type="text" id="ilce" name="ilce" value="<?php echo htmlspecialchars($depo_data['ilce']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="mahalle" class="block text-gray-700 text-sm font-semibold mb-2">Mahalle:</label>
                        <input type="text" id="mahalle" name="mahalle" value="<?php echo htmlspecialchars($depo_data['mahalle']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="sokak" class="block text-gray-700 text-sm font-semibold mb-2">Sokak:</label>
                        <input type="text" id="sokak" name="sokak" value="<?php echo htmlspecialchars($depo_data['sokak']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="bina_no" class="block text-gray-700 text-sm font-semibold mb-2">Bina No:</label>
                        <input type="text" id="bina_no" name="bina_no" value="<?php echo htmlspecialchars($depo_data['bina_no']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="adres_detay" class="block text-gray-700 text-sm font-semibold mb-2">Adres Detayları (Açık Adres):</label>
                        <textarea id="adres_detay" name="adres_detay" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($depo_data['adres_detay']); ?></textarea>
                    </div>

                    <div class="flex justify-between items-center gap-4 mt-6">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                            Depo Bilgilerini Güncelle
                        </button>
                        <a href="depo_yonetimi.php" class="w-full text-center bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                            İptal
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
