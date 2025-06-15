<?php
// Oturumu başlat
session_start();

// Tüm oturum değişkenlerini temizle
session_unset(); 

// Oturumu tamamen sonlandır
session_destroy(); 

// Giriş sayfasına yönlendir
header("Location: login.php");
exit;
?>
