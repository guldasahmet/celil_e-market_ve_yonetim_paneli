<?php
$servername = ""; // veya veritabanınızın IP adresi
$username = ""; // veritabanı kullanıcı adı
$password = ""; // veritabanı şifresi
$dbname = ""; // oluşturduğunuz veritabanı ismi

// Veritabanı bağlantısı
$mysqli = new mysqli($servername, $username, $password, $dbname);

// Bağlantı kontrolü
if ($mysqli->connect_error) {
    die("Bağlantı başarısız: " . $mysqli->connect_error);
}
?>