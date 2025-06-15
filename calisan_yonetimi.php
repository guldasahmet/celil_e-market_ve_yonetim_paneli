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
        $message = "Çalışan başarıyla eklendi!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'updated') {
        $message = "Çalışan bilgileri başarıyla güncellendi!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'deleted') {
        $message = "Çalışan başarıyla silindi!";
        $message_type = "success";
    } elseif ($_GET['status'] == 'error') {
        $message = "Bir hata oluştu.";
        $message_type = "error";
    }
}

// Tüm çalışanları, çalıştıkları yeri ve bağlı oldukları kullanıcıları veritabanından çek
$sql = "SELECT C.calisanID, C.calisan_ad, C.calisan_soyad, C.calisan_email, C.calisan_telefon, C.calisan_pozisyon,
               C.calistigi_yer_tipi, C.calistigi_yerID, C.kullaniciID,
               K.kullanici_adi AS bagli_kullanici_adi,
               D.depo_ad AS depo_adi,
               UT.tesis_adi AS uretim_tesis_adi,
               CF.ciftlik_adi AS ciftlik_adi
        FROM CALISAN C
        LEFT JOIN KULLANICI K ON C.kullaniciID = K.kullaniciID
        LEFT JOIN DEPO D ON C.calistigi_yer_tipi = 'depo' AND C.calistigi_yerID = D.depoID
        LEFT JOIN URETIM_TESISI UT ON C.calistigi_yer_tipi = 'uretim_tesisi' AND C.calistigi_yerID = UT.uretim_tesisID
        LEFT JOIN CIFTLIK CF ON C.calistigi_yer_tipi = 'ciftlik' AND C.calistigi_yerID = CF.ciftlikID
        ORDER BY C.calisanID DESC";
$result = $mysqli->query($sql);

$employees = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çalışan Yönetimi</title>
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
                <h1 class="text-3xl font-bold text-gray-800">Çalışan Yönetimi</h1>
                <a href="add_calisan.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                    Yeni Çalışan Ekle
                </a>
            </div>

            <?php if ($message) { ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php } ?>

            <?php if (empty($employees)) { ?>
                <p class="text-gray-600 text-center text-lg mt-10">Henüz hiç çalışan bulunmamaktadır.</p>
            <?php } else { ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg shadow-md">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left rounded-tl-lg">ID</th>
                                <th class="py-3 px-6 text-left">Ad Soyad</th>
                                <th class="py-3 px-6 text-left">E-posta</th>
                                <th class="py-3 px-6 text-left">Telefon</th>
                                <th class="py-3 px-6 text-left">Pozisyon</th>
                                <th class="py-3 px-6 text-left">Çalıştığı Yer</th>
                                <th class="py-3 px-6 text-left">Bağlı Kullanıcı</th>
                                <th class="py-3 px-6 text-center rounded-tr-lg">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm font-light">
                            <?php foreach ($employees as $employee) { ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo htmlspecialchars($employee['calisanID']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($employee['calisan_ad'] . " " . $employee['calisan_soyad']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($employee['calisan_email']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($employee['calisan_telefon']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($employee['calisan_pozisyon']); ?></td>
                                    <td class="py-3 px-6 text-left">
                                        <?php
                                            $calistigi_yer = "Belirtilmemiş";
                                            if ($employee['calistigi_yer_tipi'] == 'depo' && $employee['depo_adi']) {
                                                $calistigi_yer = "Depo: " . htmlspecialchars($employee['depo_adi']);
                                            } elseif ($employee['calistigi_yer_tipi'] == 'uretim_tesisi' && $employee['uretim_tesis_adi']) {
                                                $calistigi_yer = "Üretim Tesisi: " . htmlspecialchars($employee['uretim_tesis_adi']);
                                            } elseif ($employee['calistigi_yer_tipi'] == 'ciftlik' && $employee['ciftlik_adi']) {
                                                $calistigi_yer = "Çiftlik: " . htmlspecialchars($employee['ciftlik_adi']);
                                            }
                                            echo $calistigi_yer;
                                        ?>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <?php echo $employee['bagli_kullanici_adi'] ? htmlspecialchars($employee['bagli_kullanici_adi']) : 'Yok'; ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center space-x-2">
                                            <a href="edit_calisan.php?id=<?php echo $employee['calisanID']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1 px-3 rounded-lg text-xs transition duration-300 ease-in-out transform hover:scale-105">Düzenle</a>
                                            <a href="delete_calisan.php?id=<?php echo $employee['calisanID']; ?>" onclick="return confirm('Bu çalışanı silmek istediğinizden emin misiniz?')" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded-lg text-xs transition duration-300 ease-in-out transform hover:scale-105">Sil</a>
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
