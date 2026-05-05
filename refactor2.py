import os
import glob
import re

php_files = glob.glob("*.php")

navbar_html = """
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
"""

html2pdf_script = """
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
"""

pdf_print_header = """
    <!-- Sadece PDF ve Yazıcıda Görünecek Logo ve Başlık -->
    <div class="print-header" style="display: none; text-align: center; margin-bottom: 20px;">
        <img src="logo.png" alt="Logo" style="height: 80px; margin-bottom: 15px; border-radius: 8px;">
        <h2 style="color: #333;">SATIS ANALIZ PLATFORMU</h2>
        <hr style="border-top: 2px solid #0d6efd; width: 50%; margin: 10px auto;">
    </div>
"""

for file in php_files:
    if file == "db.php" or file == "fix_data.php":
        continue
        
    with open(file, "r", encoding="utf-8") as f:
        content = f.read()

    # 1. Replace existing navbar or inject it
    if "<!-- Navbar -->" in content:
        # Regex to replace everything from <!-- Navbar --> to </nav>
        content = re.sub(r'<!-- Navbar -->.*?<\/nav>', navbar_html, content, flags=re.DOTALL)
    else:
        # For satis_ekle.php, inject right after <body>
        content = content.replace("<body>", "<body>\n" + navbar_html)
        # Remove floating theme toggle if it exists
        content = re.sub(r'<button class="btn btn-dark position-fixed.*?<\/button>', '', content, flags=re.DOTALL)

    # 2. Add PDF functionality to reports
    if file in ["performans.php", "rapor.php"]:
        # Add html2pdf script before </body>
        if "html2pdf.js" not in content:
            content = content.replace("</body>", html2pdf_script + "\n</body>")
        
        # Add print header logo
        if '<div class="print-header"' in content:
            # Replace existing print header
            content = re.sub(r'<div class="print-header".*?<\/div>', pdf_print_header, content, flags=re.DOTALL)
        else:
            # Inject at the beginning of the container pb-5
            content = content.replace('<div class="container pb-5">', '<div class="container pb-5" id="pdf-content">\n' + pdf_print_header)
            # Also wrap the inner content if not wrapped
            if 'id="pdf-content"' not in content:
                 content = content.replace('<div class="container pb-5">', '<div class="container pb-5" id="pdf-content">')
                 
        # Ensure the container has id="pdf-content"
        if 'id="pdf-content"' not in content:
            content = content.replace('<div class="container pb-5">', '<div class="container pb-5" id="pdf-content">')

        # Inject "PDF İndir" button
        if file == "performans.php":
            if "downloadPDF" not in content:
                btn_html = """
                <button onclick="downloadPDF('pdf-content', 'Performans_Raporu.pdf')" class="btn btn-danger no-print float-end mt-2">
                    <i class="fas fa-file-pdf me-1"></i> PDF İndir
                </button>
                """
                content = content.replace('<h2 class="fw-bold text-secondary">', btn_html + '<h2 class="fw-bold text-secondary">')
        
        if file == "rapor.php":
            # Replace the generic window.print button with PDF download
            content = content.replace('onclick="window.print()"', 'onclick="downloadPDF(\'pdf-content\', \'Satis_Raporu.pdf\')"')
            content = content.replace('Yazdır / PDF Olarak Kaydet', 'PDF Olarak İndir')

    with open(file, "w", encoding="utf-8") as f:
        f.write(content)

print("Menu and PDF functionality injected.")
