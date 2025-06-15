-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 15 Haz 2025, 21:11:49
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `epazar_db`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `adres`
--

CREATE TABLE `adres` (
  `adresID` int(11) NOT NULL,
  `il` varchar(50) DEFAULT NULL,
  `ilce` varchar(50) DEFAULT NULL,
  `mahalle` varchar(100) DEFAULT NULL,
  `sokak` varchar(100) DEFAULT NULL,
  `bina_no` varchar(10) DEFAULT NULL,
  `adres_detay` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `adres`
--

INSERT INTO `adres` (`adresID`, `il`, `ilce`, `mahalle`, `sokak`, `bina_no`, `adres_detay`) VALUES
(1, 'Tekirdağ', 'Süleymanpaşa', 'Banarlı Mahallesi', '15. sokak', '24', 'no: 24');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `calisan`
--

CREATE TABLE `calisan` (
  `calisanID` int(11) NOT NULL,
  `kullaniciID` int(11) DEFAULT NULL,
  `calisan_ad` varchar(50) NOT NULL,
  `calisan_soyad` varchar(50) NOT NULL,
  `calisan_email` varchar(100) NOT NULL,
  `calisan_telefon` varchar(20) DEFAULT NULL,
  `calisan_pozisyon` varchar(50) DEFAULT NULL,
  `calistigi_yer_tipi` enum('depo','uretim_tesisi','ciftlik') DEFAULT NULL,
  `calistigi_yerID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `calisan`
--

INSERT INTO `calisan` (`calisanID`, `kullaniciID`, `calisan_ad`, `calisan_soyad`, `calisan_email`, `calisan_telefon`, `calisan_pozisyon`, `calistigi_yer_tipi`, `calistigi_yerID`) VALUES
(1, NULL, 'Burak', 'Yılmaz', 'burakk12@gmail.com', '7777777777', 'muhasebeci', 'depo', 1),
(2, NULL, 'Mustafa', 'Yılmaz', 'mustafaa122@gmail.com', '4444444444', 'Usta', 'uretim_tesisi', 1),
(3, 3, 'Arda', 'Kazan', 'ardakz356@gmail.com', '2222222222', 'Mandıra Müdürü', 'depo', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ciftlik`
--

CREATE TABLE `ciftlik` (
  `ciftlikID` int(11) NOT NULL,
  `ciftlik_adi` varchar(100) NOT NULL,
  `adresID` int(11) DEFAULT NULL,
  `tedarikciID` int(11) DEFAULT NULL,
  `calisanID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `depo`
--

CREATE TABLE `depo` (
  `depoID` int(11) NOT NULL,
  `depo_ad` varchar(100) NOT NULL,
  `kapasite` int(11) DEFAULT NULL,
  `adresID` int(11) DEFAULT NULL,
  `depo_sorumlusu_calisanID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `depo`
--

INSERT INTO `depo` (`depoID`, `depo_ad`, `kapasite`, `adresID`, `depo_sorumlusu_calisanID`) VALUES
(1, 'Banarlı Mandıra Deposu', 1000000, 1, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `geri_odeme`
--

CREATE TABLE `geri_odeme` (
  `geri_odemeID` int(11) NOT NULL,
  `odemeID` int(11) DEFAULT NULL,
  `geri_odeme_tutari` decimal(10,2) DEFAULT NULL,
  `geri_odeme_yontemi` varchar(50) DEFAULT NULL,
  `geri_odeme_tarihi` datetime DEFAULT current_timestamp(),
  `geri_odeme_durumu` varchar(50) DEFAULT NULL,
  `geri_odeme_aciklama` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `iade`
--

CREATE TABLE `iade` (
  `iadeID` int(11) NOT NULL,
  `kullaniciID` int(11) DEFAULT NULL,
  `siparis_urunID` int(11) DEFAULT NULL,
  `geri_odemeID` int(11) DEFAULT NULL,
  `iade_sabebi` text DEFAULT NULL,
  `iade_tarihi` datetime DEFAULT current_timestamp(),
  `iade_durum` varchar(50) DEFAULT NULL,
  `iade_miktari` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kargo`
--

CREATE TABLE `kargo` (
  `kargoID` int(11) NOT NULL,
  `firma_adi` varchar(100) NOT NULL,
  `takip_no` varchar(100) DEFAULT NULL,
  `kargo_tarihi` datetime DEFAULT NULL,
  `kargo_durumu` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kategori`
--

CREATE TABLE `kategori` (
  `kategoriID` int(11) NOT NULL,
  `kategori_ad` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `kategori`
--

INSERT INTO `kategori` (`kategoriID`, `kategori_ad`) VALUES
(1, 'Süt'),
(2, 'Yoğurt'),
(3, 'Peynir'),
(4, 'Tereyağı'),
(5, 'Ayran');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanici`
--

CREATE TABLE `kullanici` (
  `kullaniciID` int(11) NOT NULL,
  `kullanici_adi` varchar(50) NOT NULL,
  `ad` varchar(50) NOT NULL,
  `soyad` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gsm_no` varchar(20) DEFAULT NULL,
  `dogum_tarihi` date DEFAULT NULL,
  `sifre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `kullanici`
--

INSERT INTO `kullanici` (`kullaniciID`, `kullanici_adi`, `ad`, `soyad`, `email`, `gsm_no`, `dogum_tarihi`, `sifre`) VALUES
(1, 'ahmet', 'Ahmet', 'Güldaş', 'ahmetguldas@gmail.com', '5555555555', '2025-06-03', '$2y$10$2edc8FPhT95pqcu/KiDlru9F275d9N4p76pl0XmDN4Jp/V0pTyqz.'),
(2, 'mehmet', 'Mehmet', 'Yılmaz', 'mehmet1234@gmail.com', '6666666666', '2025-06-20', '$2y$10$CsxcgIsbEw7rVHwncJiWru96jue3ljVSsVjSv3xLxpk8483nK4kIS'),
(3, 'arda', 'Arda', 'Kazan', 'ardakz356@gmail.com', '2222222222', '2025-06-02', '$2y$10$kcijr6jIqANHJxv3Wq.YE./n0RsyvA4IDTgf.2XTS84MP99D/Vfae'),
(4, 'sertaç', 'Sertaç', 'Saral', 'sertacss11@gmail.com', '8888888888', '2025-05-30', '$2y$10$jdfXpUuhqeExQRk1OQE8A.pyXIHrGnbooolhzAotcOd1jt/vTZ.uC');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanici_adres`
--

CREATE TABLE `kullanici_adres` (
  `kullanici_adresID` int(11) NOT NULL,
  `adres_adi` varchar(100) DEFAULT NULL,
  `kullaniciID` int(11) DEFAULT NULL,
  `adresID` int(11) DEFAULT NULL,
  `adres_aktiflik` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanici_rol`
--

CREATE TABLE `kullanici_rol` (
  `kullanici_rolID` int(11) NOT NULL,
  `kullaniciID` int(11) DEFAULT NULL,
  `rolID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `kullanici_rol`
--

INSERT INTO `kullanici_rol` (`kullanici_rolID`, `kullaniciID`, `rolID`) VALUES
(1, 1, 1),
(2, 2, 3),
(3, 3, 3),
(4, 3, 2),
(5, 4, 3);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `malzeme`
--

CREATE TABLE `malzeme` (
  `malzemeID` int(11) NOT NULL,
  `malzeme_adi` varchar(100) NOT NULL,
  `malzeme_turu` varchar(50) DEFAULT NULL,
  `malzeme_birim` varchar(20) DEFAULT NULL,
  `malzeme_kaynak_tipi` varchar(50) DEFAULT NULL,
  `malzeme_son_kullanim_tarihi` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `medya`
--

CREATE TABLE `medya` (
  `medyaID` int(11) NOT NULL,
  `dosya_yolu` varchar(255) NOT NULL,
  `dosya_turu` varchar(20) DEFAULT NULL,
  `medya_yuklenme_tarihi` datetime DEFAULT current_timestamp(),
  `aciklama` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `medya`
--

INSERT INTO `medya` (`medyaID`, `dosya_yolu`, `dosya_turu`, `medya_yuklenme_tarihi`, `aciklama`) VALUES
(1, 'uploads/urun_684ef925c0dc2.png', 'image/png', '2025-06-15 19:47:33', 'Ayran ürünü görseli'),
(2, 'uploads/urun_684ef99eec6ff.png', 'image/png', '2025-06-15 19:49:34', 'Süt ürünü görseli'),
(3, 'uploads/urun_684efc7ce50e1.png', 'image/png', '2025-06-15 20:01:48', 'Yoğurt ürünü görseli'),
(4, 'uploads/urun_684f0a01d451b.png', 'image/png', '2025-06-15 20:59:29', 'Kaşar ürünü görseli'),
(5, 'uploads/urun_684f0a9a58ec7.png', 'image/png', '2025-06-15 21:01:38', 'Kaymak ürünü görseli');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `medya_baglantisi`
--

CREATE TABLE `medya_baglantisi` (
  `medya_baglantisiID` int(11) NOT NULL,
  `medyaID` int(11) DEFAULT NULL,
  `varlik_tipi` enum('urun','kullanici','yorum') NOT NULL,
  `varlikID` int(11) NOT NULL,
  `medya_rolu` varchar(50) DEFAULT NULL,
  `medya_eklenme_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `medya_baglantisi`
--

INSERT INTO `medya_baglantisi` (`medya_baglantisiID`, `medyaID`, `varlik_tipi`, `varlikID`, `medya_rolu`, `medya_eklenme_tarihi`) VALUES
(1, 1, 'urun', 10, 'ana', '2025-06-15 19:47:33'),
(2, 2, 'urun', 1, 'ana', '2025-06-15 19:49:34'),
(3, 3, 'urun', 2, 'ana', '2025-06-15 20:01:48'),
(4, 4, 'urun', 11, 'ana', '2025-06-15 20:59:29'),
(5, 5, 'urun', 12, 'ana', '2025-06-15 21:01:38');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `odeme`
--

CREATE TABLE `odeme` (
  `odemeID` int(11) NOT NULL,
  `kullaniciID` int(11) DEFAULT NULL,
  `odeme_tutar` decimal(10,2) DEFAULT NULL,
  `odeme_yontemi` varchar(50) DEFAULT NULL,
  `odeme_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `rol`
--

CREATE TABLE `rol` (
  `rolID` int(11) NOT NULL,
  `rol_adi` varchar(50) NOT NULL,
  `rol_atama_tarihi` date DEFAULT NULL,
  `rol_aktiflik` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `rol`
--

INSERT INTO `rol` (`rolID`, `rol_adi`, `rol_atama_tarihi`, `rol_aktiflik`) VALUES
(1, 'Admin', NULL, 1),
(2, 'Çalışan', NULL, 1),
(3, 'Müşteri', NULL, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sepet`
--

CREATE TABLE `sepet` (
  `sepetID` int(11) NOT NULL,
  `kullaniciID` int(11) DEFAULT NULL,
  `olusturma_tarihi` date DEFAULT NULL,
  `sepet_durum` varchar(50) DEFAULT NULL,
  `guncelleme_tarihi` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sepet_urun`
--

CREATE TABLE `sepet_urun` (
  `sepet_urunID` int(11) NOT NULL,
  `sepetID` int(11) DEFAULT NULL,
  `urunID` int(11) DEFAULT NULL,
  `adet` int(11) DEFAULT NULL,
  `anlik_birim_fiyat` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `siparis`
--

CREATE TABLE `siparis` (
  `siparisID` int(11) NOT NULL,
  `kullaniciID` int(11) DEFAULT NULL,
  `kargoID` int(11) DEFAULT NULL,
  `siparis_tarihi` datetime DEFAULT current_timestamp(),
  `siparis_durum` varchar(50) DEFAULT NULL,
  `adresID` int(11) DEFAULT NULL,
  `odemeID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `siparis_urun`
--

CREATE TABLE `siparis_urun` (
  `siparis_urunID` int(11) NOT NULL,
  `siparisID` int(11) DEFAULT NULL,
  `urunID` int(11) DEFAULT NULL,
  `urun_adet` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `stok_hareket`
--

CREATE TABLE `stok_hareket` (
  `stok_hareketID` int(11) NOT NULL,
  `urunID` int(11) DEFAULT NULL,
  `stok_hareket_tur` enum('Giris','Cikis') NOT NULL,
  `stok_hareket_adet` int(11) DEFAULT NULL,
  `stok_hareket_tarihi` datetime DEFAULT NULL,
  `stok_hareket_aciklama` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tedarikci`
--

CREATE TABLE `tedarikci` (
  `tedarikciID` int(11) NOT NULL,
  `tedarikci_adi` varchar(100) NOT NULL,
  `tedarikci_iletisim` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tedarikci_malzeme`
--

CREATE TABLE `tedarikci_malzeme` (
  `tedarikci_malzemeID` int(11) NOT NULL,
  `tedarikciID` int(11) DEFAULT NULL,
  `malzemeID` int(11) DEFAULT NULL,
  `tedarikci_malzeme_temin_tarihi` date DEFAULT NULL,
  `tedarikci_malzeme_miktari` int(11) DEFAULT NULL,
  `tedarikci_malzeme_birim_fiyat` decimal(10,2) DEFAULT NULL,
  `tedarikci_malzeme_aktiflik` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `uretim`
--

CREATE TABLE `uretim` (
  `uretimID` int(11) NOT NULL,
  `uretim_tarihi` date NOT NULL,
  `uretim_tesisiID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `uretim_ciftlik`
--

CREATE TABLE `uretim_ciftlik` (
  `uretim_ciftlikID` int(11) NOT NULL,
  `uretimID` int(11) DEFAULT NULL,
  `ciftlikID` int(11) DEFAULT NULL,
  `kullanilan_miktar` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `uretim_malzeme`
--

CREATE TABLE `uretim_malzeme` (
  `uretim_malzemeID` int(11) NOT NULL,
  `uretimID` int(11) DEFAULT NULL,
  `malzemeID` int(11) DEFAULT NULL,
  `uretim_malzeme_miktari` int(11) DEFAULT NULL,
  `uretim_malzeme_turu` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `uretim_tesisi`
--

CREATE TABLE `uretim_tesisi` (
  `uretim_tesisID` int(11) NOT NULL,
  `tesis_adi` varchar(100) NOT NULL,
  `adresID` int(11) DEFAULT NULL,
  `uretim_tesis_kapasite` int(11) DEFAULT NULL,
  `calisanID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `uretim_tesisi`
--

INSERT INTO `uretim_tesisi` (`uretim_tesisID`, `tesis_adi`, `adresID`, `uretim_tesis_kapasite`, `calisanID`) VALUES
(1, 'Banarlı Üretim Tesisi ', 1, 100000, 2);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `urun`
--

CREATE TABLE `urun` (
  `urunID` int(11) NOT NULL,
  `urun_ad` varchar(100) NOT NULL,
  `urun_aciklama` text DEFAULT NULL,
  `birim_fiyat` decimal(10,2) DEFAULT NULL,
  `stok_miktari` int(11) DEFAULT NULL,
  `urun_son_kullanma_tarihi` date DEFAULT NULL,
  `kategoriID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `urun`
--

INSERT INTO `urun` (`urunID`, `urun_ad`, `urun_aciklama`, `birim_fiyat`, `stok_miktari`, `urun_son_kullanma_tarihi`, `kategoriID`) VALUES
(1, 'Süt', 'Taze günlük süt ', 40.00, 100, '2025-07-06', 1),
(2, 'Yoğurt', 'Taze mayalı yoğurt', 50.00, 200, '2025-07-05', 2),
(10, 'Ayran', 'Taze Ayran 200Ml', 15.00, 1000, '2025-07-06', 5),
(11, 'Kaşar', 'Taze Kaşar 250g', 180.00, 100, '2025-08-14', 3),
(12, 'Kaymak', 'Taze Kaymak', 130.00, 150, '2025-07-17', 3);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `urun_uretim`
--

CREATE TABLE `urun_uretim` (
  `urun_uretimID` int(11) NOT NULL,
  `urunID` int(11) DEFAULT NULL,
  `uretimID` int(11) DEFAULT NULL,
  `urun_uretim_miktar` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `yorum`
--

CREATE TABLE `yorum` (
  `yorumID` int(11) NOT NULL,
  `kullaniciID` int(11) DEFAULT NULL,
  `urunID` int(11) DEFAULT NULL,
  `yorum_icerik` text NOT NULL,
  `yorum_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `adres`
--
ALTER TABLE `adres`
  ADD PRIMARY KEY (`adresID`);

--
-- Tablo için indeksler `calisan`
--
ALTER TABLE `calisan`
  ADD PRIMARY KEY (`calisanID`),
  ADD UNIQUE KEY `calisan_email` (`calisan_email`),
  ADD UNIQUE KEY `kullaniciID` (`kullaniciID`);

--
-- Tablo için indeksler `ciftlik`
--
ALTER TABLE `ciftlik`
  ADD PRIMARY KEY (`ciftlikID`),
  ADD KEY `adresID` (`adresID`),
  ADD KEY `tedarikciID` (`tedarikciID`),
  ADD KEY `calisanID` (`calisanID`);

--
-- Tablo için indeksler `depo`
--
ALTER TABLE `depo`
  ADD PRIMARY KEY (`depoID`),
  ADD KEY `adresID` (`adresID`),
  ADD KEY `depo_sorumlusu_calisanID` (`depo_sorumlusu_calisanID`);

--
-- Tablo için indeksler `geri_odeme`
--
ALTER TABLE `geri_odeme`
  ADD PRIMARY KEY (`geri_odemeID`),
  ADD UNIQUE KEY `odemeID` (`odemeID`);

--
-- Tablo için indeksler `iade`
--
ALTER TABLE `iade`
  ADD PRIMARY KEY (`iadeID`),
  ADD UNIQUE KEY `geri_odemeID` (`geri_odemeID`),
  ADD KEY `kullaniciID` (`kullaniciID`),
  ADD KEY `siparis_urunID` (`siparis_urunID`);

--
-- Tablo için indeksler `kargo`
--
ALTER TABLE `kargo`
  ADD PRIMARY KEY (`kargoID`),
  ADD UNIQUE KEY `takip_no` (`takip_no`);

--
-- Tablo için indeksler `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`kategoriID`);

--
-- Tablo için indeksler `kullanici`
--
ALTER TABLE `kullanici`
  ADD PRIMARY KEY (`kullaniciID`),
  ADD UNIQUE KEY `kullanici_adi` (`kullanici_adi`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Tablo için indeksler `kullanici_adres`
--
ALTER TABLE `kullanici_adres`
  ADD PRIMARY KEY (`kullanici_adresID`),
  ADD KEY `kullaniciID` (`kullaniciID`),
  ADD KEY `adresID` (`adresID`);

--
-- Tablo için indeksler `kullanici_rol`
--
ALTER TABLE `kullanici_rol`
  ADD PRIMARY KEY (`kullanici_rolID`),
  ADD KEY `kullaniciID` (`kullaniciID`),
  ADD KEY `rolID` (`rolID`);

--
-- Tablo için indeksler `malzeme`
--
ALTER TABLE `malzeme`
  ADD PRIMARY KEY (`malzemeID`);

--
-- Tablo için indeksler `medya`
--
ALTER TABLE `medya`
  ADD PRIMARY KEY (`medyaID`);

--
-- Tablo için indeksler `medya_baglantisi`
--
ALTER TABLE `medya_baglantisi`
  ADD PRIMARY KEY (`medya_baglantisiID`),
  ADD KEY `medyaID` (`medyaID`);

--
-- Tablo için indeksler `odeme`
--
ALTER TABLE `odeme`
  ADD PRIMARY KEY (`odemeID`),
  ADD KEY `kullaniciID` (`kullaniciID`);

--
-- Tablo için indeksler `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`rolID`);

--
-- Tablo için indeksler `sepet`
--
ALTER TABLE `sepet`
  ADD PRIMARY KEY (`sepetID`),
  ADD UNIQUE KEY `kullaniciID` (`kullaniciID`);

--
-- Tablo için indeksler `sepet_urun`
--
ALTER TABLE `sepet_urun`
  ADD PRIMARY KEY (`sepet_urunID`),
  ADD KEY `sepetID` (`sepetID`),
  ADD KEY `urunID` (`urunID`);

--
-- Tablo için indeksler `siparis`
--
ALTER TABLE `siparis`
  ADD PRIMARY KEY (`siparisID`),
  ADD UNIQUE KEY `odemeID` (`odemeID`),
  ADD KEY `kullaniciID` (`kullaniciID`),
  ADD KEY `kargoID` (`kargoID`),
  ADD KEY `adresID` (`adresID`);

--
-- Tablo için indeksler `siparis_urun`
--
ALTER TABLE `siparis_urun`
  ADD PRIMARY KEY (`siparis_urunID`),
  ADD KEY `siparisID` (`siparisID`),
  ADD KEY `urunID` (`urunID`);

--
-- Tablo için indeksler `stok_hareket`
--
ALTER TABLE `stok_hareket`
  ADD PRIMARY KEY (`stok_hareketID`),
  ADD KEY `urunID` (`urunID`);

--
-- Tablo için indeksler `tedarikci`
--
ALTER TABLE `tedarikci`
  ADD PRIMARY KEY (`tedarikciID`);

--
-- Tablo için indeksler `tedarikci_malzeme`
--
ALTER TABLE `tedarikci_malzeme`
  ADD PRIMARY KEY (`tedarikci_malzemeID`),
  ADD KEY `tedarikciID` (`tedarikciID`),
  ADD KEY `malzemeID` (`malzemeID`);

--
-- Tablo için indeksler `uretim`
--
ALTER TABLE `uretim`
  ADD PRIMARY KEY (`uretimID`),
  ADD KEY `uretim_tesisiID` (`uretim_tesisiID`);

--
-- Tablo için indeksler `uretim_ciftlik`
--
ALTER TABLE `uretim_ciftlik`
  ADD PRIMARY KEY (`uretim_ciftlikID`),
  ADD KEY `uretimID` (`uretimID`),
  ADD KEY `ciftlikID` (`ciftlikID`);

--
-- Tablo için indeksler `uretim_malzeme`
--
ALTER TABLE `uretim_malzeme`
  ADD PRIMARY KEY (`uretim_malzemeID`),
  ADD KEY `uretimID` (`uretimID`),
  ADD KEY `malzemeID` (`malzemeID`);

--
-- Tablo için indeksler `uretim_tesisi`
--
ALTER TABLE `uretim_tesisi`
  ADD PRIMARY KEY (`uretim_tesisID`),
  ADD KEY `adresID` (`adresID`),
  ADD KEY `calisanID` (`calisanID`);

--
-- Tablo için indeksler `urun`
--
ALTER TABLE `urun`
  ADD PRIMARY KEY (`urunID`),
  ADD KEY `kategoriID` (`kategoriID`);

--
-- Tablo için indeksler `urun_uretim`
--
ALTER TABLE `urun_uretim`
  ADD PRIMARY KEY (`urun_uretimID`),
  ADD KEY `urunID` (`urunID`),
  ADD KEY `uretimID` (`uretimID`);

--
-- Tablo için indeksler `yorum`
--
ALTER TABLE `yorum`
  ADD PRIMARY KEY (`yorumID`),
  ADD KEY `kullaniciID` (`kullaniciID`),
  ADD KEY `urunID` (`urunID`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `adres`
--
ALTER TABLE `adres`
  MODIFY `adresID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `calisan`
--
ALTER TABLE `calisan`
  MODIFY `calisanID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `ciftlik`
--
ALTER TABLE `ciftlik`
  MODIFY `ciftlikID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `depo`
--
ALTER TABLE `depo`
  MODIFY `depoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `geri_odeme`
--
ALTER TABLE `geri_odeme`
  MODIFY `geri_odemeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `iade`
--
ALTER TABLE `iade`
  MODIFY `iadeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `kargo`
--
ALTER TABLE `kargo`
  MODIFY `kargoID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `kategori`
--
ALTER TABLE `kategori`
  MODIFY `kategoriID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `kullanici`
--
ALTER TABLE `kullanici`
  MODIFY `kullaniciID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `kullanici_adres`
--
ALTER TABLE `kullanici_adres`
  MODIFY `kullanici_adresID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `kullanici_rol`
--
ALTER TABLE `kullanici_rol`
  MODIFY `kullanici_rolID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `malzeme`
--
ALTER TABLE `malzeme`
  MODIFY `malzemeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `medya`
--
ALTER TABLE `medya`
  MODIFY `medyaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `medya_baglantisi`
--
ALTER TABLE `medya_baglantisi`
  MODIFY `medya_baglantisiID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `odeme`
--
ALTER TABLE `odeme`
  MODIFY `odemeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `rol`
--
ALTER TABLE `rol`
  MODIFY `rolID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `sepet`
--
ALTER TABLE `sepet`
  MODIFY `sepetID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `sepet_urun`
--
ALTER TABLE `sepet_urun`
  MODIFY `sepet_urunID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `siparis`
--
ALTER TABLE `siparis`
  MODIFY `siparisID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `siparis_urun`
--
ALTER TABLE `siparis_urun`
  MODIFY `siparis_urunID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `stok_hareket`
--
ALTER TABLE `stok_hareket`
  MODIFY `stok_hareketID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `tedarikci`
--
ALTER TABLE `tedarikci`
  MODIFY `tedarikciID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `tedarikci_malzeme`
--
ALTER TABLE `tedarikci_malzeme`
  MODIFY `tedarikci_malzemeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `uretim`
--
ALTER TABLE `uretim`
  MODIFY `uretimID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `uretim_ciftlik`
--
ALTER TABLE `uretim_ciftlik`
  MODIFY `uretim_ciftlikID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `uretim_malzeme`
--
ALTER TABLE `uretim_malzeme`
  MODIFY `uretim_malzemeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `uretim_tesisi`
--
ALTER TABLE `uretim_tesisi`
  MODIFY `uretim_tesisID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `urun`
--
ALTER TABLE `urun`
  MODIFY `urunID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `urun_uretim`
--
ALTER TABLE `urun_uretim`
  MODIFY `urun_uretimID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `yorum`
--
ALTER TABLE `yorum`
  MODIFY `yorumID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `calisan`
--
ALTER TABLE `calisan`
  ADD CONSTRAINT `fk_calisan_kullanici` FOREIGN KEY (`kullaniciID`) REFERENCES `kullanici` (`kullaniciID`);

--
-- Tablo kısıtlamaları `ciftlik`
--
ALTER TABLE `ciftlik`
  ADD CONSTRAINT `ciftlik_ibfk_1` FOREIGN KEY (`adresID`) REFERENCES `adres` (`adresID`),
  ADD CONSTRAINT `ciftlik_ibfk_2` FOREIGN KEY (`tedarikciID`) REFERENCES `tedarikci` (`tedarikciID`),
  ADD CONSTRAINT `ciftlik_ibfk_3` FOREIGN KEY (`calisanID`) REFERENCES `calisan` (`calisanID`);

--
-- Tablo kısıtlamaları `depo`
--
ALTER TABLE `depo`
  ADD CONSTRAINT `depo_ibfk_1` FOREIGN KEY (`adresID`) REFERENCES `adres` (`adresID`),
  ADD CONSTRAINT `depo_ibfk_2` FOREIGN KEY (`depo_sorumlusu_calisanID`) REFERENCES `calisan` (`calisanID`);

--
-- Tablo kısıtlamaları `geri_odeme`
--
ALTER TABLE `geri_odeme`
  ADD CONSTRAINT `geri_odeme_ibfk_1` FOREIGN KEY (`odemeID`) REFERENCES `odeme` (`odemeID`);

--
-- Tablo kısıtlamaları `iade`
--
ALTER TABLE `iade`
  ADD CONSTRAINT `iade_ibfk_1` FOREIGN KEY (`kullaniciID`) REFERENCES `kullanici` (`kullaniciID`),
  ADD CONSTRAINT `iade_ibfk_2` FOREIGN KEY (`siparis_urunID`) REFERENCES `siparis_urun` (`siparis_urunID`),
  ADD CONSTRAINT `iade_ibfk_3` FOREIGN KEY (`geri_odemeID`) REFERENCES `geri_odeme` (`geri_odemeID`);

--
-- Tablo kısıtlamaları `kullanici_adres`
--
ALTER TABLE `kullanici_adres`
  ADD CONSTRAINT `kullanici_adres_ibfk_1` FOREIGN KEY (`kullaniciID`) REFERENCES `kullanici` (`kullaniciID`),
  ADD CONSTRAINT `kullanici_adres_ibfk_2` FOREIGN KEY (`adresID`) REFERENCES `adres` (`adresID`);

--
-- Tablo kısıtlamaları `kullanici_rol`
--
ALTER TABLE `kullanici_rol`
  ADD CONSTRAINT `kullanici_rol_ibfk_1` FOREIGN KEY (`kullaniciID`) REFERENCES `kullanici` (`kullaniciID`),
  ADD CONSTRAINT `kullanici_rol_ibfk_2` FOREIGN KEY (`rolID`) REFERENCES `rol` (`rolID`);

--
-- Tablo kısıtlamaları `medya_baglantisi`
--
ALTER TABLE `medya_baglantisi`
  ADD CONSTRAINT `medya_baglantisi_ibfk_1` FOREIGN KEY (`medyaID`) REFERENCES `medya` (`medyaID`);

--
-- Tablo kısıtlamaları `odeme`
--
ALTER TABLE `odeme`
  ADD CONSTRAINT `odeme_ibfk_1` FOREIGN KEY (`kullaniciID`) REFERENCES `kullanici` (`kullaniciID`);

--
-- Tablo kısıtlamaları `sepet`
--
ALTER TABLE `sepet`
  ADD CONSTRAINT `sepet_ibfk_1` FOREIGN KEY (`kullaniciID`) REFERENCES `kullanici` (`kullaniciID`);

--
-- Tablo kısıtlamaları `sepet_urun`
--
ALTER TABLE `sepet_urun`
  ADD CONSTRAINT `sepet_urun_ibfk_1` FOREIGN KEY (`sepetID`) REFERENCES `sepet` (`sepetID`),
  ADD CONSTRAINT `sepet_urun_ibfk_2` FOREIGN KEY (`urunID`) REFERENCES `urun` (`urunID`);

--
-- Tablo kısıtlamaları `siparis`
--
ALTER TABLE `siparis`
  ADD CONSTRAINT `siparis_ibfk_1` FOREIGN KEY (`kullaniciID`) REFERENCES `kullanici` (`kullaniciID`),
  ADD CONSTRAINT `siparis_ibfk_2` FOREIGN KEY (`kargoID`) REFERENCES `kargo` (`kargoID`),
  ADD CONSTRAINT `siparis_ibfk_3` FOREIGN KEY (`adresID`) REFERENCES `adres` (`adresID`),
  ADD CONSTRAINT `siparis_ibfk_4` FOREIGN KEY (`odemeID`) REFERENCES `odeme` (`odemeID`);

--
-- Tablo kısıtlamaları `siparis_urun`
--
ALTER TABLE `siparis_urun`
  ADD CONSTRAINT `siparis_urun_ibfk_1` FOREIGN KEY (`siparisID`) REFERENCES `siparis` (`siparisID`),
  ADD CONSTRAINT `siparis_urun_ibfk_2` FOREIGN KEY (`urunID`) REFERENCES `urun` (`urunID`);

--
-- Tablo kısıtlamaları `stok_hareket`
--
ALTER TABLE `stok_hareket`
  ADD CONSTRAINT `stok_hareket_ibfk_1` FOREIGN KEY (`urunID`) REFERENCES `urun` (`urunID`);

--
-- Tablo kısıtlamaları `tedarikci_malzeme`
--
ALTER TABLE `tedarikci_malzeme`
  ADD CONSTRAINT `tedarikci_malzeme_ibfk_1` FOREIGN KEY (`tedarikciID`) REFERENCES `tedarikci` (`tedarikciID`),
  ADD CONSTRAINT `tedarikci_malzeme_ibfk_2` FOREIGN KEY (`malzemeID`) REFERENCES `malzeme` (`malzemeID`);

--
-- Tablo kısıtlamaları `uretim`
--
ALTER TABLE `uretim`
  ADD CONSTRAINT `uretim_ibfk_1` FOREIGN KEY (`uretim_tesisiID`) REFERENCES `uretim_tesisi` (`uretim_tesisID`);

--
-- Tablo kısıtlamaları `uretim_ciftlik`
--
ALTER TABLE `uretim_ciftlik`
  ADD CONSTRAINT `uretim_ciftlik_ibfk_1` FOREIGN KEY (`uretimID`) REFERENCES `uretim` (`uretimID`),
  ADD CONSTRAINT `uretim_ciftlik_ibfk_2` FOREIGN KEY (`ciftlikID`) REFERENCES `ciftlik` (`ciftlikID`);

--
-- Tablo kısıtlamaları `uretim_malzeme`
--
ALTER TABLE `uretim_malzeme`
  ADD CONSTRAINT `uretim_malzeme_ibfk_1` FOREIGN KEY (`uretimID`) REFERENCES `uretim` (`uretimID`),
  ADD CONSTRAINT `uretim_malzeme_ibfk_2` FOREIGN KEY (`malzemeID`) REFERENCES `malzeme` (`malzemeID`);

--
-- Tablo kısıtlamaları `uretim_tesisi`
--
ALTER TABLE `uretim_tesisi`
  ADD CONSTRAINT `uretim_tesisi_ibfk_1` FOREIGN KEY (`adresID`) REFERENCES `adres` (`adresID`),
  ADD CONSTRAINT `uretim_tesisi_ibfk_2` FOREIGN KEY (`calisanID`) REFERENCES `calisan` (`calisanID`);

--
-- Tablo kısıtlamaları `urun`
--
ALTER TABLE `urun`
  ADD CONSTRAINT `urun_ibfk_1` FOREIGN KEY (`kategoriID`) REFERENCES `kategori` (`kategoriID`);

--
-- Tablo kısıtlamaları `urun_uretim`
--
ALTER TABLE `urun_uretim`
  ADD CONSTRAINT `urun_uretim_ibfk_1` FOREIGN KEY (`urunID`) REFERENCES `urun` (`urunID`),
  ADD CONSTRAINT `urun_uretim_ibfk_2` FOREIGN KEY (`uretimID`) REFERENCES `uretim` (`uretimID`);

--
-- Tablo kısıtlamaları `yorum`
--
ALTER TABLE `yorum`
  ADD CONSTRAINT `yorum_ibfk_1` FOREIGN KEY (`kullaniciID`) REFERENCES `kullanici` (`kullaniciID`),
  ADD CONSTRAINT `yorum_ibfk_2` FOREIGN KEY (`urunID`) REFERENCES `urun` (`urunID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
