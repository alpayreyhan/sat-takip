<?php
require_once 'db.php';

// 1. En Çok Satan 5 Ürün (Azalan - DESC)
// Sadece satış yapılmış ürünleri değerlendirmek için JOIN (INNER JOIN) kullanıyoruz.
$stmt_top = $pdo->query("
    SELECT 
        u.urun_adi, 
        COALESCE(SUM(s.adet), 0) as toplam_adet, 
        COALESCE(SUM(s.toplam_tutar), 0) as toplam_gelir
    FROM urunler u
    JOIN satislar s ON u.id = s.urun_id
    GROUP BY u.id
    ORDER BY toplam_adet DESC
    LIMIT 5
");
$top_urunler = $stmt_top->fetchAll();

// 2. Düşük Performans Gösteren 5 Ürün (Artan - ASC)
// Hiç satılmayan ürünleri de listeye dahil etmek (toplam adet 0 olarak) için LEFT JOIN kullanıyoruz.
$stmt_bottom = $pdo->query("
    SELECT 
        u.urun_adi, 
        COALESCE(SUM(s.adet), 0) as toplam_adet, 
        COALESCE(SUM(s.toplam_tutar), 0) as toplam_gelir
    FROM urunler u
    LEFT JOIN satislar s ON u.id = s.urun_id
    GROUP BY u.id
    ORDER BY toplam_adet ASC, u.urun_adi ASC
    LIMIT 5
");
$bottom_urunler = $stmt_bottom->fetchAll();
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
    <title>Performans Raporu - Veri Analizi Aracı</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: var(--bs-secondary-bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .table-container { 
            background: var(--bs-body-bg); 
            border-radius: 12px; 
            padding: 25px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            height: 100%;
        }
        .table-title {
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .text-success-custom { color: #198754; }
        .text-danger-custom { color: #dc3545; }
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


<div class="container pb-5" id="pdf-content">

    <!-- Sadece PDF ve Yazıcıda Görünecek Logo ve Başlık -->
    <div class="print-header" style="display: none; text-align: center; margin-bottom: 20px;">
        <img src="logo.png" alt="Logo" style="height: 80px; margin-bottom: 15px; border-radius: 8px;">
        <h2 style="color: #333;">SATIS ANALIZ PLATFORMU</h2>
        <hr style="border-top: 2px solid #0d6efd; width: 50%; margin: 10px auto;">
    </div>

    <div class="mb-4">
        <h2 class="fw-bold text-secondary"><i class="fas fa-trophy me-2 text-warning"></i>Ürün Performans Raporu</h2>
        <p class="text-muted">Bu ekranda sisteminizdeki en çok ve en az satış yapılan ürünlerin hacim bazlı analizini inceleyebilirsiniz.</p>
    </div>

    <div class="row align-items-stretch">
        
        <!-- Sol Kolon: En Çok Satanlar -->
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="table-container border-top border-success border-4">
                <h5 class="table-title text-success-custom">
                    <i class="fas fa-arrow-trend-up me-2"></i>En Çok Satan 5 Ürün
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover table-borderless align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>Ürün Adı</th>
                                <th class="text-center">Adet</th>
                                <th class="text-end">Ciro (Gelir)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($top_urunler) > 0): ?>
                                <?php foreach ($top_urunler as $urun): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($urun['urun_adi']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                                                <?= number_format($urun['toplam_adet'], 0, '', '.') ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-secondary">
                                            <?= number_format($urun['toplam_gelir'], 2, ',', '.') ?> ₺
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">Henüz satış verisi bulunmuyor.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon: Düşük Performans Gösterenler -->
        <div class="col-lg-6">
            <div class="table-container border-top border-danger border-4">
                <h5 class="table-title text-danger-custom">
                    <i class="fas fa-arrow-trend-down me-2"></i>Düşük Performans Gösteren 5 Ürün
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover table-borderless align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>Ürün Adı</th>
                                <th class="text-center">Adet</th>
                                <th class="text-end">Ciro (Gelir)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($bottom_urunler) > 0): ?>
                                <?php foreach ($bottom_urunler as $urun): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($urun['urun_adi']) ?></td>
                                        <td class="text-center">
                                            <?php if ($urun['toplam_adet'] == 0): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">Hiç Satılmadı</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill text-dark">
                                                    <?= number_format($urun['toplam_adet'], 0, '', '.') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold text-secondary">
                                            <?= number_format($urun['toplam_gelir'], 2, ',', '.') ?> ₺
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">Kayıtlı ürün bulunmuyor.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-muted" style="font-size: 0.85rem;">
                    <i class="fas fa-info-circle me-1"></i> Bu listeye hiç satılmayan (satış adeti 0 olan) ürünler de dahildir.
                </div>
            </div>
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

<!-- html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF(elementId, filename) {
    const element = document.getElementById(elementId);
    
    // Geçici olarak yazdırılabilir başlığı (Logoyu) görünür yap
    const printHeader = element.querySelector('.print-header');
    if(printHeader) printHeader.style.display = 'block';

    var opt = {
      margin:       0.5,
      filename:     filename,
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, useCORS: true },
      jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        // İndirme sonrası başlığı tekrar gizle
        if(printHeader) printHeader.style.display = 'none';
    });
}
</script>

</body>
</html>
