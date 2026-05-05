<?php
require_once 'db.php';

// ---------------------------------------------------------
// 1. KPI Kartları İçin Veri Çekme İşlemleri
// ---------------------------------------------------------

// Toplam Ciro
$stmt_ciro = $pdo->query("SELECT SUM(toplam_tutar) as ciro FROM satislar");
$toplam_ciro = $stmt_ciro->fetch()['ciro'] ?? 0;

// Bu Ayki Toplam Satış Adeti
$stmt_aylik = $pdo->query("
    SELECT SUM(adet) as aylik_adet 
    FROM satislar 
    WHERE MONTH(satis_tarihi) = MONTH(CURRENT_DATE()) 
    AND YEAR(satis_tarihi) = YEAR(CURRENT_DATE())
");
$aylik_adet = $stmt_aylik->fetch()['aylik_adet'] ?? 0;

// En Çok Satış Yapılan Bölge (Adet bazında)
$stmt_bolge = $pdo->query("
    SELECT b.bolge_adi, SUM(s.adet) as toplam_adet 
    FROM satislar s
    JOIN bolgeler b ON s.bolge_id = b.id
    GROUP BY s.bolge_id
    ORDER BY toplam_adet DESC
    LIMIT 1
");
$en_iyi_bolge_row = $stmt_bolge->fetch();
$en_iyi_bolge = $en_iyi_bolge_row ? $en_iyi_bolge_row['bolge_adi'] : 'Veri Yok';

// ---------------------------------------------------------
// 2. Grafikler İçin Veri Çekme İşlemleri
// ---------------------------------------------------------

// Grafik 1: Bölge Bazlı Toplam Satış Hacmi (Tutar bazında)
$stmt_bolge_hacim = $pdo->query("
    SELECT b.bolge_adi, SUM(s.toplam_tutar) as hacim 
    FROM satislar s
    JOIN bolgeler b ON s.bolge_id = b.id
    GROUP BY s.bolge_id
");
$bolge_verileri = $stmt_bolge_hacim->fetchAll();
$bolge_isimleri = [];
$bolge_hacimleri = [];
foreach ($bolge_verileri as $row) {
    $bolge_isimleri[] = $row['bolge_adi'];
    $bolge_hacimleri[] = (float)$row['hacim'];
}

// Grafik 2: Kategori Bazlı Satış Performansı (Tutar bazında)
$stmt_kategori = $pdo->query("
    SELECT k.kategori_adi, SUM(s.toplam_tutar) as performans 
    FROM satislar s
    JOIN urunler u ON s.urun_id = u.id
    JOIN kategoriler k ON u.kategori_id = k.id
    GROUP BY k.id
");
$kategori_verileri = $stmt_kategori->fetchAll();
$kategori_isimleri = [];
$kategori_performanslari = [];
foreach ($kategori_verileri as $row) {
    $kategori_isimleri[] = $row['kategori_adi'];
    $kategori_performanslari[] = (float)$row['performans'];
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
    <title>Dashboard - Satış Raporlama ve Veri Analizi Aracı</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: var(--bs-secondary-bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .kpi-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-5px); }
        .icon-circle { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .chart-container { background: var(--bs-body-bg); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .canvas-wrapper { position: relative; height: 300px; width: 100%; }
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
    <!-- Premium Welcome Header -->
    <div class="card border-0 rounded-4 mb-5 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: linear-gradient(135deg, var(--bs-body-bg) 0%, var(--bs-secondary-bg) 100%);">
        <div class="card-body p-4 p-md-5 position-relative">
            <!-- Decorative background elements -->
            <div class="position-absolute top-0 end-0 opacity-10" style="transform: translate(20%, -20%);">
                <i class="fas fa-chart-pie" style="font-size: 15rem;"></i>
            </div>
            
            <div class="d-flex align-items-center position-relative z-1">
                <div class="flex-shrink-0 me-4 bg-white p-3 rounded-4 shadow-sm" style="border: 1px solid rgba(0,0,0,0.05);">
                    <img src="logo.png" alt="AR Logo" style="height: 70px; object-fit: contain;">
                </div>
                <div class="flex-grow-1">
                    <h2 class="fw-bold mb-1" style="color: var(--bs-heading-color); letter-spacing: -0.5px;">AR Analiz Platformu</h2>
                    <p class="text-muted mb-0 fs-5">Satış verilerinizi yönetin, premium raporlarla geleceği planlayın.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-secondary m-0"><i class="fas fa-chart-line me-2"></i>Genel Bakış</h4>
    </div>

    <!-- KPI Kartları -->
    <div class="row mb-4">
        <!-- Toplam Ciro -->
        <div class="col-md-4 mb-3">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-success me-3 shadow-sm">
                        <i class="fas fa-turkish-lira-sign"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.8rem;">Toplam Ciro</h6>
                        <h3 class="mb-0 fw-bold"><?= number_format($toplam_ciro, 2, ',', '.') ?> TL</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bu Ayki Toplam Satış Adeti -->
        <div class="col-md-4 mb-3">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-primary me-3 shadow-sm">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.8rem;">Bu Ayki Satış Adeti</h6>
                        <h3 class="mb-0 fw-bold"><?= number_format($aylik_adet, 0, '', '.') ?> Adet</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- En Çok Satış Yapılan Bölge -->
        <div class="col-md-4 mb-3">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-warning me-3 shadow-sm">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.8rem;">En İyi Bölge</h6>
                        <h3 class="mb-0 fw-bold"><?= htmlspecialchars($en_iyi_bolge) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafikler -->
    <div class="row">
        <!-- Doughnut Chart: Bölge Bazlı Satış Hacmi -->
        <div class="col-lg-5">
            <div class="chart-container">
                <h5 class="fw-bold text-secondary mb-4 text-center">Bölge Bazlı Satış Hacmi (TL)</h5>
                <div class="canvas-wrapper">
                    <canvas id="bolgeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Bar Chart: Kategori Bazlı Satış Performansı -->
        <div class="col-lg-7">
            <div class="chart-container">
                <h5 class="fw-bold text-secondary mb-4 text-center">Kategori Bazlı Satış Performansı (TL)</h5>
                <div class="canvas-wrapper">
                    <canvas id="kategoriChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// PHP'den JSON formatında verileri al
const bolgeIsimleri = <?= json_encode($bolge_isimleri) ?>;
const bolgeHacimleri = <?= json_encode($bolge_hacimleri) ?>;

const kategoriIsimleri = <?= json_encode($kategori_isimleri) ?>;
const kategoriPerformanslari = <?= json_encode($kategori_performanslari) ?>;

// Renk paletleri
const donutColors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0', '#6610f2'];
const barColors = '#0d6efd';

// 1. Bölge Bazlı Doughnut Chart
const ctxBolge = document.getElementById('bolgeChart').getContext('2d');
new Chart(ctxBolge, {
    type: 'doughnut',
    data: {
        labels: bolgeIsimleri.length > 0 ? bolgeIsimleri : ['Veri Yok'],
        datasets: [{
            data: bolgeHacimleri.length > 0 ? bolgeHacimleri : [1],
            backgroundColor: bolgeHacimleri.length > 0 ? donutColors : ['#e9ecef'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        },
        cutout: '65%'
    }
});

// 2. Kategori Bazlı Bar Chart
const ctxKategori = document.getElementById('kategoriChart').getContext('2d');
new Chart(ctxKategori, {
    type: 'bar',
    data: {
        labels: kategoriIsimleri.length > 0 ? kategoriIsimleri : ['Veri Yok'],
        datasets: [{
            label: 'Toplam Satış (TL)',
            data: kategoriPerformanslari.length > 0 ? kategoriPerformanslari : [0],
            backgroundColor: barColors,
            borderRadius: 6,
            barPercentage: 0.6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false } // Bar grafiğinde tek veri seti olduğu için legend gizlendi
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { borderDash: [2, 4], color: '#e9ecef' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
</script>

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
