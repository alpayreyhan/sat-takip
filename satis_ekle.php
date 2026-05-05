<?php
require_once 'db.php';

$mesaj = '';
$mesaj_tur = '';

// Form gönderildiyse işlemleri yap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urun_id = $_POST['urun_id'] ?? null;
    $bolge_id = $_POST['bolge_id'] ?? null;
    $adet = $_POST['adet'] ?? null;

    // Gelen verilerin doğruluğunu kontrol et
    if ($urun_id && $bolge_id && $adet && $adet > 0) {
        try {
            // Güvenlik: Kullanıcıdan fiyatı almak yerine, seçilen ürünün fiyatını DB'den çekiyoruz
            $stmt = $pdo->prepare("SELECT fiyat FROM urunler WHERE id = ?");
            $stmt->execute([$urun_id]);
            $urun = $stmt->fetch();

            if ($urun) {
                $fiyat = $urun['fiyat'];
                $toplam_tutar = $fiyat * $adet; // Fiyat ile adeti çarpıp toplam tutarı hesapla

                // Satışı veritabanına kaydet
                $insert_stmt = $pdo->prepare("INSERT INTO satislar (urun_id, bolge_id, adet, toplam_tutar) VALUES (?, ?, ?, ?)");
                $insert_stmt->execute([$urun_id, $bolge_id, $adet, $toplam_tutar]);

                $mesaj = "Satış başarıyla eklendi! Toplam Tutar: " . number_format($toplam_tutar, 2, ',', '.') . " TL";
                $mesaj_tur = "success";
            } else {
                $mesaj = "Seçilen ürün sistemde bulunamadı.";
                $mesaj_tur = "danger";
            }
        } catch (\PDOException $e) {
            $mesaj = "Kayıt sırasında bir hata oluştu: " . $e->getMessage();
            $mesaj_tur = "danger";
        }
    } else {
        $mesaj = "Lütfen tüm alanları eksiksiz ve geçerli bir şekilde doldurun.";
        $mesaj_tur = "warning";
    }
}

// Ürünleri veritabanından çek (Dropdown için)
try {
    $urunler = $pdo->query("SELECT id, urun_adi, fiyat FROM urunler ORDER BY urun_adi ASC")->fetchAll();
} catch (\PDOException $e) {
    $urunler = []; // Tablo boş veya yoksa hata vermesin diye
}

// Bölgeleri veritabanından çek (Dropdown için)
try {
    $bolgeler = $pdo->query("SELECT id, bolge_adi FROM bolgeler ORDER BY bolge_adi ASC")->fetchAll();
} catch (\PDOException $e) {
    $bolgeler = [];
}

?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="light">
<head>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Satış Ekle - Veri Analizi Aracı</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--bs-secondary-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-form {
            max-width: 550px;
            margin: 50px auto;
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .card-header {
            border-radius: 12px 12px 0 0 !important;
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 no-print" id="main-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <img src="logo.png" alt="Logo" height="30" class="d-inline-block align-text-top me-2 rounded bg-white p-1">
            Satış Analiz
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="raporlarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-pdf me-1"></i> Raporlar
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark shadow" aria-labelledby="raporlarDropdown">
                        <li><a class="dropdown-item" href="performans.php"><i class="fas fa-trophy me-2 text-warning"></i>Performans Raporu</a></li>
                        <li><a class="dropdown-item" href="rapor.php"><i class="fas fa-list me-2 text-info"></i>Kapsamlı Rapor</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-primary text-white ms-3 px-3 rounded-pill" href="satis_ekle.php">
                        <i class="fas fa-plus me-1"></i> Yeni Satış
                    </a>
                </li>
                <li class="nav-item ms-2 d-flex align-items-center">
                    <button class="btn btn-sm btn-outline-light rounded-circle" id="theme-toggle" title="Koyu/Açık Tema">
                        <i class="fas fa-moon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>


        
        

<div class="container">
    <div class="card card-form">
        <div class="card-header text-white text-center py-3">
            <h4 class="mb-0 fw-bold">Yeni Satış Ekle</h4>
        </div>
        <div class="card-body p-4">
            
            <!-- Bildirim Mesajı Alanı -->
            <?php if ($mesaj): ?>
                <div class="alert alert-<?= $mesaj_tur ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?= htmlspecialchars($mesaj) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                </div>
            <?php endif; ?>

            <form action="satis_ekle.php" method="POST">
                
                <!-- Ürün Seçimi -->
                <div class="mb-3">
                    <label for="urun_id" class="form-label fw-semibold text-secondary">Ürün</label>
                    <select class="form-select" id="urun_id" name="urun_id" required>
                        <option value="" selected disabled>Lütfen bir ürün seçin...</option>
                        <?php foreach ($urunler as $urun): ?>
                            <option value="<?= $urun['id'] ?>">
                                <?= htmlspecialchars($urun['urun_adi']) ?> (<?= number_format($urun['fiyat'], 2, ',', '.') ?> TL)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Bölge Seçimi -->
                <div class="mb-3">
                    <label for="bolge_id" class="form-label fw-semibold text-secondary">Satılan Bölge</label>
                    <select class="form-select" id="bolge_id" name="bolge_id" required>
                        <option value="" selected disabled>Lütfen bir bölge seçin...</option>
                        <?php foreach ($bolgeler as $bolge): ?>
                            <option value="<?= $bolge['id'] ?>">
                                <?= htmlspecialchars($bolge['bolge_adi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Satış Adeti -->
                <div class="mb-4">
                    <label for="adet" class="form-label fw-semibold text-secondary">Satış Adeti</label>
                    <input type="number" class="form-control" id="adet" name="adet" min="1" required placeholder="Örn: 5">
                </div>

                <!-- Butonlar -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold">
                        Satışı Kaydet
                    </button>
                    <!-- Ana sayfaya ya da başka bir ekrana dönmek isterseniz: -->
                    <!-- <a href="index.php" class="btn btn-outline-secondary">Geri Dön</a> -->
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('theme-toggle');
    if (!themeToggleBtn) return;
    
    const themeIcon = themeToggleBtn.querySelector('i');
    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
    updateIcon(currentTheme);
    
    themeToggleBtn.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-bs-theme');
        let newTheme = theme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateIcon(newTheme);
    });
    
    function updateIcon(theme) {
        if (theme === 'dark') {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
            themeToggleBtn.classList.replace('btn-outline-dark', 'btn-outline-light');
        } else {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
            // If navbar is dark, outline-light is still better. But we can keep it outline-light or use text-warning
            themeToggleBtn.classList.replace('btn-outline-dark', 'btn-outline-light');
        }
    }
});
</script>
</body>
</html>
