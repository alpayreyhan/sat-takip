-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 05 May 2026, 14:10:00
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `satis_analiz`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bolgeler`
--

CREATE TABLE `bolgeler` (
  `id` int(11) NOT NULL,
  `bolge_adi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `bolgeler`
--

INSERT INTO `bolgeler` (`id`, `bolge_adi`) VALUES
(1, 'Marmara Bölgesi'),
(2, 'Ege Bölgesi'),
(3, 'İç Anadolu Bölgesi'),
(4, 'Akdeniz Bölgesi');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kategoriler`
--

CREATE TABLE `kategoriler` (
  `id` int(11) NOT NULL,
  `kategori_adi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kategoriler`
--

INSERT INTO `kategoriler` (`id`, `kategori_adi`) VALUES
(1, 'Elektronik'),
(2, 'Giyim'),
(3, 'Ev Eşyaları'),
(4, 'Kozmetik');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `satislar`
--

CREATE TABLE `satislar` (
  `id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `bolge_id` int(11) NOT NULL,
  `adet` int(11) NOT NULL,
  `toplam_tutar` decimal(10,2) NOT NULL,
  `satis_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `satislar`
--

INSERT INTO `satislar` (`id`, `urun_id`, `bolge_id`, `adet`, `toplam_tutar`, `satis_tarihi`) VALUES
(1, 1, 1, 2, 70000.00, '2026-04-27 06:58:06'),
(2, 2, 2, 1, 45000.00, '2026-04-24 06:58:06'),
(3, 3, 3, 5, 12500.00, '2026-04-19 06:58:06'),
(4, 4, 1, 3, 13500.00, '2026-04-14 06:58:06'),
(5, 5, 4, 2, 6400.00, '2026-04-11 06:58:06'),
(6, 6, 2, 1, 6500.00, '2026-04-09 06:58:06'),
(7, 7, 3, 4, 21600.00, '2026-04-07 06:58:06'),
(8, 8, 4, 2, 5600.00, '2026-04-04 06:58:06'),
(9, 1, 2, 1, 35000.00, '2026-03-27 06:58:06'),
(10, 2, 1, 2, 90000.00, '2026-03-24 06:58:06'),
(11, 3, 3, 10, 25000.00, '2026-03-19 06:58:06'),
(12, 4, 4, 5, 22500.00, '2026-03-14 06:58:06'),
(13, 5, 1, 4, 12800.00, '2026-03-01 06:58:06'),
(14, 6, 2, 2, 13000.00, '2026-02-24 06:58:06'),
(15, 7, 3, 6, 32400.00, '2026-02-19 06:58:06'),
(16, 8, 4, 3, 8400.00, '2026-01-29 06:58:06'),
(17, 1, 3, 3, 105000.00, '2026-01-24 06:58:06'),
(18, 2, 4, 1, 45000.00, '2025-12-29 06:58:06'),
(19, 3, 1, 8, 20000.00, '2025-12-19 06:58:06'),
(20, 4, 2, 6, 27000.00, '2025-11-29 06:58:06'),
(21, 1, 4, 20, 700000.00, '2026-04-29 08:01:19'),
(22, 1, 4, 20, 700000.00, '2026-04-29 08:01:24'),
(23, 8, 3, 30, 84000.00, '2026-04-29 08:02:08'),
(24, 8, 3, 30, 84000.00, '2026-04-29 08:02:24');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `urunler`
--

CREATE TABLE `urunler` (
  `id` int(11) NOT NULL,
  `urun_adi` varchar(255) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `fiyat` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `urunler`
--

INSERT INTO `urunler` (`id`, `urun_adi`, `kategori_id`, `fiyat`) VALUES
(1, 'Akıllı Telefon Pro', 1, 35000.00),
(2, 'Oyun Bilgisayarı', 1, 45000.00),
(3, 'Kablosuz Kulaklık', 1, 2500.00),
(4, 'Kışlık Kaban', 2, 4500.00),
(5, 'Koşu Ayakkabısı', 2, 3200.00),
(6, 'Porselen Yemek Takımı', 3, 6500.00),
(7, 'Ergonomik Ofis Koltuğu', 3, 5400.00),
(8, 'Erkek Parfüm 100ml', 4, 2800.00),
(9, 'Yüz Bakım Kremi', 4, 1500.00),
(10, 'Akıllı Saat', 1, 7500.00);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `bolgeler`
--
ALTER TABLE `bolgeler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `kategoriler`
--
ALTER TABLE `kategoriler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `satislar`
--
ALTER TABLE `satislar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `urun_id` (`urun_id`),
  ADD KEY `bolge_id` (`bolge_id`);

--
-- Tablo için indeksler `urunler`
--
ALTER TABLE `urunler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `bolgeler`
--
ALTER TABLE `bolgeler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `kategoriler`
--
ALTER TABLE `kategoriler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `satislar`
--
ALTER TABLE `satislar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Tablo için AUTO_INCREMENT değeri `urunler`
--
ALTER TABLE `urunler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `satislar`
--
ALTER TABLE `satislar`
  ADD CONSTRAINT `fk_satis_bolge` FOREIGN KEY (`bolge_id`) REFERENCES `bolgeler` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_satis_urun` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `urunler`
--
ALTER TABLE `urunler`
  ADD CONSTRAINT `fk_urun_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
