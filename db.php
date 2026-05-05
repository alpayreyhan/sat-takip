<?php
// db.php
// Veritabanı bağlantı ayarları
$host = 'localhost';
$dbname = 'satis_analiz';
$username = 'root'; // XAMPP varsayılan kullanıcı adı
$password = ''; // XAMPP varsayılan şifresi (genellikle boştur)
$charset = 'utf8mb4';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// PDO Seçenekleri
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hataları exception olarak fırlatır
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Varsayılan veri çekme formatı (ilişkisel dizi)
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Gerçek prepared statements kullan
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci" // Türkçe karakter sorunu çözümü
];

try {
    // Veritabanı bağlantısını oluştur
    $pdo = new PDO($dsn, $username, $password, $options);
    // echo "Bağlantı başarılı!"; // Geliştirme aşamasında test etmek için yorum satırını kaldırabilirsiniz
} catch (\PDOException $e) {
    // Bağlantı hatası durumunda çalışacak blok
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
