<?php
// User.php
// Kullanıcıya ait rol bilgilerini yönetmek için yardımcı sınıf

class User {
    private $mysqli; // Veritabanı bağlantısı
    private $userId; // Oturumdaki kullanıcının ID'si
    private $roles = []; // Kullanıcının sahip olduğu roller

    /**
     * Kurucu metot. Veritabanı bağlantısını ve kullanıcı ID'sini alır,
     * ardından kullanıcının rollerini yükler.
     *
     * @param mysqli $mysqli Veritabanı bağlantı nesnesi
     * @param int $userId Giriş yapmış kullanıcının ID'si
     */
    public function __construct($mysqli, $userId) {
        $this->mysqli = $mysqli;
        $this->userId = $userId;
        $this->loadRoles();
    }

    /**
     * Veritabanından kullanıcının rollerini yükler.
     * KULLANICI_ROL ve ROL tablolarını birleştirerek rol adlarını çeker.
     */
    private function loadRoles() {
        // SQL sorgusu: Kullanıcının sahip olduğu rol adlarını getir
        $sql = "SELECT R.rol_adi
                FROM KULLANICI_ROL KR
                JOIN ROL R ON KR.rolID = R.rolID
                WHERE KR.kullaniciID = ?";
        
        // Sorguyu hazırla
        $stmt = $this->mysqli->prepare($sql);
        // Parametreleri bağla (i: integer)
        $stmt->bind_param("i", $this->userId);
        // Sorguyu çalıştır
        $stmt->execute();
        // Sonuçları al
        $result = $stmt->get_result();

        // Her bir rol adını $roles dizisine ekle
        while ($row = $result->fetch_assoc()) {
            $this->roles[] = $row['rol_adi'];
        }
        // Hazırlanmış ifadeyi kapat
        $stmt->close();
    }

    /**
     * Kullanıcının belirli bir role sahip olup olmadığını kontrol eder.
     *
     * @param string $roleName Kontrol edilecek rol adı (örn: 'Admin', 'Çalışan', 'Müşteri')
     * @return bool Kullanıcı belirtilen role sahipse true, aksi takdirde false
     */
    public function hasRole($roleName) {
        return in_array($roleName, $this->roles);
    }

    /**
     * Kullanıcının ID'sini döndürür.
     *
     * @return int Kullanıcı ID'si
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * Kullanıcının tüm rollerini bir dizi olarak döndürür.
     *
     * @return array Kullanıcının sahip olduğu rol adlarının dizisi
     */
    public function getRoles() {
        return $this->roles;
    }
}
?>
