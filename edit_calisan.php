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
$calisan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$employee_data = null;

// Mevcut kullanıcıları çek
$kullanicilar_sql = "SELECT kullaniciID, kullanici_adi, ad, soyad, email, gsm_no FROM KULLANICI ORDER BY kullanici_adi ASC";
$kullanicilar_result = $mysqli->query($kullanicilar_sql);
$kullanicilar = [];
if ($kullanicilar_result->num_rows > 0) {
    while($row = $kullanicilar_result->fetch_assoc()) {
        $kullanicilar[] = $row;
    }
}

// Mevcut depoları, üretim tesislerini ve çiftlikleri çek
$depolar_sql = "SELECT depoID, depo_ad FROM DEPO ORDER BY depo_ad ASC";
$depolar_result = $mysqli->query($depolar_sql);
$depolar = [];
if ($depolar_result->num_rows > 0) {
    while($row = $depolar_result->fetch_assoc()) {
        $depolar[] = $row;
    }
}

$uretim_tesisleri_sql = "SELECT uretim_tesisID, tesis_adi FROM URETIM_TESISI ORDER BY tesis_adi ASC";
$uretim_tesisleri_result = $mysqli->query($uretim_tesisleri_sql);
$uretim_tesisleri = [];
if ($uretim_tesisleri_result->num_rows > 0) {
    while($row = $uretim_tesisleri_result->fetch_assoc()) {
        $uretim_tesisleri[] = $row;
    }
}

$ciftlikler_sql = "SELECT ciftlikID, ciftlik_adi FROM CIFTLIK ORDER BY ciftlik_adi ASC";
$ciftlikler_result = $mysqli->query($ciftlikler_sql);
$ciftlikler = [];
if ($ciftlikler_result->num_rows > 0) {
    while($row = $ciftlikler_result->fetch_assoc()) {
        $ciftlikler[] = $row;
    }
}


// Çalışan bilgilerini çek
if ($calisan_id > 0) {
    $sql = "SELECT calisanID, kullaniciID, calisan_ad, calisan_soyad, calisan_email, calisan_telefon, calisan_pozisyon, calistigi_yer_tipi, calistigi_yerID FROM CALISAN WHERE calisanID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $calisan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee_data = $result->fetch_assoc();
    $stmt->close();

    if (!$employee_data) {
        $message = "Çalışan bulunamadı.";
        $message_type = "error";
    }
} else {
    $message = "Geçersiz Çalışan ID'si.";
    $message_type = "error";
}

// Form POST metodu ile gönderildiğinde çalışan güncelleme işlemini yap
if ($_SERVER["REQUEST_METHOD"] == "POST" && $employee_data) {
    $calisan_ad = htmlspecialchars($_POST['calisan_ad']);
    $calisan_soyad = htmlspecialchars($_POST['calisan_soyad']);
    $calisan_email = htmlspecialchars($_POST['calisan_email']);
    $calisan_telefon = htmlspecialchars($_POST['calisan_telefon']);
    $calisan_pozisyon = htmlspecialchars($_POST['calisan_pozisyon']);
    $calistigi_yer_tipi = !empty($_POST['calistigi_yer_tipi']) ? htmlspecialchars($_POST['calistigi_yer_tipi']) : NULL;
    $calistigi_yerID = !empty($_POST['calistigi_yerID']) ? intval($_POST['calistigi_yerID']) : NULL;
    $kullaniciID = !empty($_POST['kullaniciID']) ? intval($_POST['kullaniciID']) : NULL;

    // E-posta adresinin veya kullaniciID'nin başka bir çalışan tarafından kullanılıp kullanılmadığını kontrol et
    $check_sql = "SELECT calisanID FROM CALISAN WHERE (calisan_email = ? OR kullaniciID = ?) AND calisanID != ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("sii", $calisan_email, $kullaniciID, $calisan_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $message = "Hata: Bu e-posta adresi veya bağlı kullanıcı başka bir çalışan tarafından kullanılıyor.";
        $message_type = "error";
    } else {
        // Çalışan bilgilerini güncelle
        $update_sql = "UPDATE CALISAN SET kullaniciID = ?, calisan_ad = ?, calisan_soyad = ?, calisan_email = ?, calisan_telefon = ?, calisan_pozisyon = ?, calistigi_yer_tipi = ?, calistigi_yerID = ? WHERE calisanID = ?";
        $update_stmt = $mysqli->prepare($update_sql);
        
        $params = array($kullaniciID, $calisan_ad, $calisan_soyad, $calisan_email, $calisan_telefon, $calisan_pozisyon, $calistigi_yer_tipi, $calistigi_yerID, $calisan_id);
        $types = "issssssii"; 
        
        call_user_func_array([$update_stmt, 'bind_param'], array_merge([$types], $params));
        
        if ($update_stmt->execute()) {
            // Eğer bir kullaniciID seçildiyse, bu kullanıcıya "Çalışan" rolünü ata
            if ($kullaniciID !== NULL) {
                $get_calisan_rol_id_sql = "SELECT rolID FROM ROL WHERE rol_adi = 'Çalışan'";
                $calisan_rol_result = $mysqli->query($get_calisan_rol_id_sql);
                
                if ($calisan_rol_result && $calisan_rol_result->num_rows > 0) {
                    $calisan_rol_row = $calisan_rol_result->fetch_assoc();
                    $calisan_rol_id = $calisan_rol_row['rolID'];

                    $check_user_role_sql = "SELECT COUNT(*) FROM KULLANICI_ROL WHERE kullaniciID = ? AND rolID = ?";
                    $check_user_role_stmt = $mysqli->prepare($check_user_role_sql);
                    $check_user_role_stmt->bind_param("ii", $kullaniciID, $calisan_rol_id);
                    $check_user_role_stmt->execute();
                    $role_exists = false;
                    $check_user_role_stmt->bind_result($count);
                    $check_user_role_stmt->fetch();
                    if ($count > 0) {
                        $role_exists = true;
                    }
                    $check_user_role_stmt->close();

                    if (!$role_exists) {
                        $assign_calisan_role_sql = "INSERT INTO KULLANICI_ROL (kullaniciID, rolID) VALUES (?, ?)";
                        $assign_calisan_role_stmt = $mysqli->prepare($assign_calisan_role_sql);
                        $assign_calisan_role_stmt->bind_param("ii", $kullaniciID, $calisan_rol_id);
                        $assign_calisan_role_stmt->execute();
                        $assign_calisan_role_stmt->close();
                    }
                } else {
                    error_log("Hata: 'Çalışan' rolü veritabanında bulunamadı.");
                }
            }
            header("Location: calisan_yonetimi.php?status=updated");
            exit;
        } else {
            $message = "Çalışan güncelleme sırasında bir hata oluştu: " . $update_stmt->error;
            $message_type = "error";
        }
        $update_stmt->close();
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çalışan Düzenle</title>
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
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Çalışan Düzenle</h1>
            <?php if ($message) { ?>
                <div class="
                    <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>
                    px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php } ?>

            <?php if ($employee_data) { ?>
                <form method="POST" action="edit_calisan.php?id=<?php echo $calisan_id; ?>" class="space-y-4">
                    <div>
                        <label for="kullaniciID" class="block text-gray-700 text-sm font-semibold mb-2">Bağlanacak Kullanıcı (İsteğe Bağlı):</label>
                        <select id="kullaniciID" name="kullaniciID" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="fillEmployeeFields()">
                            <option value="">Seçiniz (Kullanıcı Yok)</option>
                            <?php foreach ($kullanicilar as $kullanici) { ?>
                                <option value="<?php echo htmlspecialchars($kullanici['kullaniciID']); ?>"
                                    data-ad="<?php echo htmlspecialchars($kullanici['ad']); ?>"
                                    data-soyad="<?php echo htmlspecialchars($kullanici['soyad']); ?>"
                                    data-email="<?php echo htmlspecialchars($kullanici['email']); ?>"
                                    data-telefon="<?php echo htmlspecialchars($kullanici['gsm_no']); ?>"
                                    <?php echo ($kullanici['kullaniciID'] == $employee_data['kullaniciID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kullanici['kullanici_adi'] . " (" . $kullanici['ad'] . " " . $kullanici['soyad'] . ")"); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Eğer mevcut bir kullanıcıyı seçerseniz, Ad, Soyad, E-posta ve Telefon alanları otomatik doldurulabilir.</p>
                    </div>
                    <div>
                        <label for="calisan_ad" class="block text-gray-700 text-sm font-semibold mb-2">Ad:</label>
                        <input type="text" id="calisan_ad" name="calisan_ad" value="<?php echo htmlspecialchars($employee_data['calisan_ad']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="calisan_soyad" class="block text-gray-700 text-sm font-semibold mb-2">Soyad:</label>
                        <input type="text" id="calisan_soyad" name="calisan_soyad" value="<?php echo htmlspecialchars($employee_data['calisan_soyad']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="calisan_email" class="block text-gray-700 text-sm font-semibold mb-2">E-posta:</label>
                        <input type="email" id="calisan_email" name="calisan_email" value="<?php echo htmlspecialchars($employee_data['calisan_email']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="calisan_telefon" class="block text-gray-700 text-sm font-semibold mb-2">Telefon:</label>
                        <input type="text" id="calisan_telefon" name="calisan_telefon" value="<?php echo htmlspecialchars($employee_data['calisan_telefon']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="calisan_pozisyon" class="block text-gray-700 text-sm font-semibold mb-2">Pozisyon:</label>
                        <input type="text" id="calisan_pozisyon" name="calisan_pozisyon" value="<?php echo htmlspecialchars($employee_data['calisan_pozisyon']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <h2 class="text-xl font-bold text-gray-700 mb-4">Çalıştığı Yer Bilgisi (İsteğe Bağlı)</h2>
                        <div>
                            <label for="calistigi_yer_tipi" class="block text-gray-700 text-sm font-semibold mb-2">Çalıştığı Yer Tipi:</label>
                            <select id="calistigi_yer_tipi" name="calistigi_yer_tipi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="updateCalistigiYerDropdown()">
                                <option value="">Seçiniz</option>
                                <option value="depo" <?php echo ($employee_data['calistigi_yer_tipi'] == 'depo') ? 'selected' : ''; ?>>Depo</option>
                                <option value="uretim_tesisi" <?php echo ($employee_data['calistigi_yer_tipi'] == 'uretim_tesisi') ? 'selected' : ''; ?>>Üretim Tesisi</option>
                                <option value="ciftlik" <?php echo ($employee_data['calistigi_yer_tipi'] == 'ciftlik') ? 'selected' : ''; ?>>Çiftlik</option>
                            </select>
                        </div>
                        <div class="mt-4" id="calistigi_yer_id_container">
                            <label for="calistigi_yerID" class="block text-gray-700 text-sm font-semibold mb-2">Çalıştığı Yer Adı:</label>
                            <select id="calistigi_yerID" name="calistigi_yerID" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Önce Yer Tipi Seçin</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-between items-center gap-4 mt-6">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                            Çalışan Bilgilerini Güncelle
                        </button>
                        <a href="calisan_yonetimi.php" class="w-full text-center bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
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

    <script>
        // PHP'den gelen verileri JavaScript değişkenlerine ata
        const depolar = <?php echo json_encode($depolar); ?>;
        const uretimTesisleri = <?php echo json_encode($uretim_tesisleri); ?>;
        const ciftlikler = <?php echo json_encode($ciftlikler); ?>;
        const kullanicilarData = <?php echo json_encode($kullanicilar); ?>;
        const currentCalistigiYerID = <?php echo json_encode($employee_data['calistigi_yerID']); ?>;

        const kullaniciMap = new Map();
        kullanicilarData.forEach(k => kullaniciMap.set(k.kullaniciID, k));

        function updateCalistigiYerDropdown(initialLoad = false) {
            const yerTipiSelect = document.getElementById('calistigi_yer_tipi');
            const yerIDSelect = document.getElementById('calistigi_yerID');
            const selectedYerTipi = yerTipiSelect.value;

            yerIDSelect.innerHTML = '<option value="">Seçiniz</option>'; // Önceki seçenekleri temizle

            let data = [];
            let idKey = '';
            let nameKey = '';

            if (selectedYerTipi === 'depo') {
                data = depolar;
                idKey = 'depoID';
                nameKey = 'depo_ad';
            } else if (selectedYerTipi === 'uretim_tesisi') {
                data = uretimTesisleri;
                idKey = 'uretim_tesisID';
                nameKey = 'tesis_adi';
            } else if (selectedYerTipi === 'ciftlik') {
                data = ciftlikler;
                idKey = 'ciftlikID';
                nameKey = 'ciftlik_adi';
            }

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item[idKey];
                option.textContent = item[nameKey];
                yerIDSelect.appendChild(option);
            });

            // İlk yüklemede mevcut değeri seç
            if (initialLoad && currentCalistigiYerID !== null) {
                yerIDSelect.value = currentCalistigiYerID;
            }
        }

        function fillEmployeeFields() {
            const kullaniciSelect = document.getElementById('kullaniciID');
            const selectedOption = kullaniciSelect.options[kullaniciSelect.selectedIndex];

            const adInput = document.getElementById('calisan_ad');
            const soyadInput = document.getElementById('calisan_soyad');
            const emailInput = document.getElementById('calisan_email');
            const telefonInput = document.getElementById('calisan_telefon');

            if (selectedOption.value) {
                const selectedKullanici = kullaniciMap.get(parseInt(selectedOption.value));
                adInput.value = selectedKullanici.ad || '';
                soyadInput.value = selectedKullanici.soyad || '';
                emailInput.value = selectedKullanici.email || '';
                telefonInput.value = selectedKullanici.gsm_no || '';
            } else {
                // Seçim kaldırılırsa veya yeni çalışan seçilirse alanları boşalt (veya mevcut değerleri koru)
                // Bu 'edit' sayfasında, mevcut employee_data'yı korumak daha mantıklı olabilir.
                // Şimdilik boşaltma yapıyorum, isterseniz değişebiliriz.
                adInput.value = '<?php echo htmlspecialchars($employee_data['calisan_ad']); ?>';
                soyadInput.value = '<?php echo htmlspecialchars($employee_data['calisan_soyad']); ?>';
                emailInput.value = '<?php echo htmlspecialchars($employee_data['calisan_email']); ?>';
                telefonInput.value = '<?php echo htmlspecialchars($employee_data['calisan_telefon']); ?>';
            }
        }

        // Sayfa yüklendiğinde dropdown'ı güncelle ve mevcut değeri seç
        window.onload = function() {
            updateCalistigiYerDropdown(true);
        };
    </script>
</body>
</html>
