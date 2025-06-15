<?php
// Oturumu başlat
session_start();

// Kullanıcının login durumuna bakılmaksızın doğrudan ürün listesi sayfasına yönlendir
// Bu, e-ticaret sitelerinin ana sayfasının genellikle ürünleri gösterdiği yaklaşıma uygundur.
header("Location: urun_listesi.php");
exit;
?>
