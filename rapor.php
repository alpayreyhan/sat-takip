<?php
require_once 'db.php';

// Form gönderildi mi kontrol et, tarihler varsa al (GET metodu ile filtreleme)
$baslangic = $_GET['baslangic'] ?? '';
$bitis = $_GET['bitis'] ?? '';

// Temel SQL Sorgusu (4 Tabloyu birleştiriyoruz)
$sql = "
    SELECT 
        u.urun_adi, 
        b.bolge_adi, 
        k.kategori_adi, 
        s.adet, 
        s.toplam_tutar, 
        s.satis_tarihi
    FROM satislar s
    JOIN urunler u ON s.urun_id = u.id
    JOIN bolgeler b ON s.bolge_id = b.id
    JOIN kategoriler k ON u.kategori_id = k.id
    WHERE 1=1
";

$params = [];

// Eğer tarihler seçilmişse WHERE şartına ekle
if (!empty($baslangic) && !empty($bitis)) {
    $sql .= " AND DATE(s.satis_tarihi) >= :baslangic AND DATE(s.satis_tarihi) <= :bitis";
    $params[':baslangic'] = $baslangic;
    $params[':bitis'] = $bitis;
}

// Tarihe göre en yeniden en eskiye sırala
$sql .= " ORDER BY s.satis_tarihi DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $raporlar = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Veri çekme hatası: " . $e->getMessage());
}

// Alt kısımdaki genel toplamlar için değişkenler
$genel_toplam_adet = 0;
$genel_toplam_tutar = 0;
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
    <title>Kapsamlı Satış Raporu - Veri Analizi Aracı</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { 
            background-color: var(--bs-secondary-bg); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        .filter-card, .table-container { 
            background: var(--bs-body-bg); 
            border-radius: 12px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            border: none;
        }
        
        /* Yazdırma (Print) Ayarları */
        @media print {
            body { 
                background-color: white !important; 
                margin: 0; 
                padding: 0; 
            }
            .no-print { 
                display: none !important; 
            }
            .table-container { 
                box-shadow: none !important; 
                border: none !important; 
                padding: 0 !important; 
            }
            table { 
                width: 100% !important; 
            }
            th, td { 
                border: 1px solid #dee2e6 !important; 
                padding: 8px !important; 
            }
            .print-header { 
                display: block !important; 
                text-align: center; 
                margin-bottom: 20px; 
            }
        }
        
        /* Normal ekranda print başlığını gizle */
        .print-header { display: none; }
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
    
    <!-- Filtreleme Kartı (Yazdırılırken gizlenecek) -->
    <div class="card filter-card mb-4 no-print p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-secondary m-0"><i class="fas fa-filter me-2"></i>Tarih Aralığı Filtresi</h5>
            <button onclick="downloadPDF('pdf-content', 'Satis_Raporu.pdf')" class="btn btn-dark">
                <i class="fas fa-print me-1"></i> PDF Olarak İndir
            </button>
        </div>
        
        <form method="GET" action="rapor.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="baslangic" class="form-label text-muted fw-bold">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="baslangic" name="baslangic" value="<?= htmlspecialchars($baslangic) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="bitis" class="form-label text-muted fw-bold">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="bitis" name="bitis" value="<?= htmlspecialchars($bitis) ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-search me-1"></i> Raporla
                </button>
            </div>
        </form>
    </div>

    <!-- Sadece yazdırılırken görünecek başlık -->
    
    <!-- Sadece PDF ve Yazıcıda Görünecek Logo ve Başlık -->
    <div class="print-header" style="display: none; text-align: center; margin-bottom: 20px;">
        <img src="logo.png" alt="Logo" style="height: 80px; margin-bottom: 15px; border-radius: 8px;">
        <h2 style="color: #333;">SATIS ANALIZ PLATFORMU</h2>
        <hr style="border-top: 2px solid #0d6efd; width: 50%; margin: 10px auto;">
    </div>


    <!-- Rapor Tablosu -->
    <div class="table-container p-4">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tarih ve Saat</th>
                        <th>Ürün Adı</th>
                        <th>Kategori</th>
                        <th>Bölge</th>
                        <th class="text-center">Adet</th>
                        <th class="text-end">Tutar (TL)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($raporlar) > 0): ?>
                        <?php foreach ($raporlar as $row): ?>
                            <?php 
                                // Toplamları hesaplarken güncelliyoruz
                                $genel_toplam_adet += $row['adet'];
                                $genel_toplam_tutar += $row['toplam_tutar'];
                            ?>
                            <tr>
                                <td><?= date('d.m.Y H:i', strtotime($row['satis_tarihi'])) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['urun_adi']) ?></td>
                                <td><span class="badge bg-secondary text-light"><?= htmlspecialchars($row['kategori_adi']) ?></span></td>
                                <td><?= htmlspecialchars($row['bolge_adi']) ?></td>
                                <td class="text-center fw-semibold"><?= $row['adet'] ?></td>
                                <td class="text-end fw-bold text-success">
                                    <?= number_format($row['toplam_tutar'], 2, ',', '.') ?> ₺
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                Bu tarih aralığında herhangi bir satış kaydı bulunamadı.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (count($raporlar) > 0): ?>
                    <tfoot class="table-group-divider">
                        <tr class="table-secondary">
                            <td colspan="4" class="text-end fw-bold">Genel Toplam:</td>
                            <td class="text-center fw-bold fs-5 text-primary"><?= number_format($genel_toplam_adet, 0, '', '.') ?></td>
                            <td class="text-end fw-bold fs-5 text-success">
                                <?= number_format($genel_toplam_tutar, 2, ',', '.') ?> ₺
                            </td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
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
