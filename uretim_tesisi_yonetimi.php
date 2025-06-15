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
    header("Location: main.php?error=yetkisiz_erisim"); // Yetkisiz erişim durumunda ana sayfaya yönlendir
    exit;
}

$message = '';
$message_type = '';

// GET isteği ile gelen status mesajlarını kontrol et
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'added') {
        $message = "Üretim tesisi başarıyla eklendi!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'updated') {
        $message = "Üretim tesisi bilgileri başarıyla güncellendi!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'deleted') {
        $message = "Üretim tesisi başarıyla silindi!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'error') {
        $message = "Bir hata oluştu.";
        $message_type = "error";
    }
}

// Tüm üretim tesislerini, adreslerini ve sorumlu çalışanlarını veritabanından çek
$sql = "SELECT UT.uretim_tesisID, UT.tesis_adi, UT.uretim_tesis_kapasite,
               A.il, A.ilce, A.mahalle, A.sokak, A.bina_no, A.adres_detay,
               C.calisan_ad, C.calisan_soyad
        FROM URETIM_TESISI UT
        LEFT JOIN ADRES A ON UT.adresID = A.adresID
        LEFT JOIN CALISAN C ON UT.calisanID = C.calisanID
        ORDER BY UT.uretim_tesisID DESC";
$result = $mysqli->query($sql);

$production_facilities = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $production_facilities[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üretim Tesisi Yönetimi</title>
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
        <div class="bg-white p-8 rounded-2xl shadow-lg max-w-5xl w-full mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Üretim Tesisi Yönetimi</h1>
                <a href="add_uretim_tesisi.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                    Yeni Tesis Ekle
                </a>
            </div>

            <?php if ($message) { ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php } ?>

            <?php if (empty($production_facilities)) { ?>
                <p class="text-gray-600 text-center text-lg mt-10">Henüz hiç üretim tesisi bulunmamaktadır.</p>
            <?php } else { ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg shadow-md">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left rounded-tl-lg">ID</th>
                                <th class="py-3 px-6 text-left">Tesis Adı</th>
                                <th class="py-3 px-6 text-left">Kapasite</th>
                                <th class="py-3 px-6 text-left">Adres</th>
                                <th class="py-3 px-6 text-left">Sorumlu Çalışan</th>
                                <th class="py-3 px-6 text-center rounded-tr-lg">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm font-light">
                            <?php foreach ($production_facilities as $facility) { ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo htmlspecialchars($facility['uretim_tesisID']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($facility['tesis_adi']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($facility['uretim_tesis_kapasite']); ?></td>
                                    <td class="py-3 px-6 text-left">
                                        <?php 
                                            $adres_str = "";
                                            if ($facility['il']) $adres_str .= htmlspecialchars($facility['il']);
                                            if ($facility['ilce']) $adres_str .= ", " . htmlspecialchars($facility['ilce']);
                                            if ($facility['mahalle']) $adres_str .= ", " . htmlspecialchars($facility['mahalle']);
                                            if ($facility['sokak']) $adres_str .= ", " . htmlspecialchars($facility['sokak']);
                                            if ($facility['bina_no']) $adres_str .= " No:" . htmlspecialchars($facility['bina_no']);
                                            if ($adres_str == "") $adres_str = "Adres Bilgisi Yok";
                                            echo $adres_str;
                                        ?>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <?php echo ($facility['calisan_ad'] && $facility['calisan_soyad']) ? htmlspecialchars($facility['calisan_ad'] . " " . $facility['calisan_soyad']) : 'Atanmamış'; ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center space-x-2">
                                            <a href="edit_uretim_tesisi.php?id=<?php echo $facility['uretim_tesisID']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1 px-3 rounded-lg text-xs transition duration-300 ease-in-out transform hover:scale-105">Düzenle</a>
                                            <a href="delete_uretim_tesisi.php?id=<?php echo $facility['uretim_tesisID']; ?>" onclick="return confirm('Bu üretim tesisini silmek istediğinizden emin misiniz?')" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded-lg text-xs transition duration-300 ease-in-out transform hover:scale-105">Sil</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
            <div class="mt-8 text-center">
                <a href="yonetim_paneli.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                    Yönetim Paneline Dön
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
