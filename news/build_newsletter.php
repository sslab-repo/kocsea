<?php
/*
 * ini_set('display_errors', 1); // Enable error display
ini_set('display_startup_errors', 1); // Show startup errors
error_reporting(E_ALL); // Report all types of errors
 */
require_once __DIR__ . '/auth.php';
require_admin();
$pdo = db();

$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? '';
$title = trim($_POST['title'] ?? 'Weekly Newsletter');
if (!$start || !$end) { redirect(base_url('admin_newsletter.php')); }

// Load banner and posts again to produce final HTML
$banner = $pdo->query("SELECT `value` FROM settings WHERE `key`='banner_html'")->fetchColumn();
$stmt = $pdo->prepare('SELECT p.*, u.name AS author FROM posts p JOIN users u ON u.id = p.user_id
  WHERE p.status = "published" AND p.start_date <= ? AND p.end_date >= ?
  ORDER BY post_order, p.updated_at ASC');
$stmt->execute([$end, $start]);
$posts = $stmt->fetchAll();

ob_start();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    /* NanumGothic (registered below) covers Hangul; dompdf's bundled fonts don't.
       !important so inline font-family from pasted content can't fall back to a
       font without Korean glyphs. */
    body, body * { font-family: 'NanumGothic', 'DejaVu Sans', sans-serif !important; }
    body { font-size: 12pt; }
    .container { max-width: 800px; margin: 0 auto; }
    h1,h2,h3 { margin: 0.5rem 0; }
    .divider { border-bottom: 1px solid #ccc; margin: 12px 0; }
  </style>
</head>
<body>
  <div class="container">
    <?= $banner ?>
    <h2 style="text-align:center;"><?= h($title) ?></h2>
    <?php foreach ($posts as $p): ?>
      <section style="page-break-inside: avoid; margin-bottom: 18px;">
        <h3><?= h($p['title']) ?></h3>
        <div><?= $p['body_html'] ?></div>
   <!--     <p style="font-size:10pt;color:#777;">Posted by <?= h($p['author']) ?> (Active: <?= h($p['start_date']) ?> → <?= h($p['end_date']) ?>)</p> -->
      </section>
    <?php endforeach; ?>
    <div class="divider"></div>
    <p style="text-align:right; color:#666; font-size:9pt; margin:4px 0 0;">Coverage: <?= h($start) ?> to <?= h($end) ?></p>
  </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Ensure output dir exists
if (!is_dir(PDF_OUTPUT_DIR)) {
    mkdir(PDF_OUTPUT_DIR, 0775, true);
}

// Use Dompdf (preferred) — support Composer or a local include fallback
$dompdfAutoload = __DIR__ . '/vendor/autoload.php';
$dompdfFallback = __DIR__ . '/dompdf/autoload.inc.php';
if (file_exists($dompdfAutoload)) {
    require_once $dompdfAutoload;
} elseif (file_exists($dompdfFallback)) {
    require_once $dompdfFallback;
} else {
    http_response_code(500);
    echo 'Dompdf not found. Install via Composer: composer require dompdf/dompdf';
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;
$html = preg_replace_callback(
      '#(<img\b[^>]*\bsrc=")(?!https?://|data:)([^"]+)"#i',
      function($m) {
          $src = $m[2];
          $localPath = ($src[0] === '/') ? $_SERVER['DOCUMENT_ROOT'] . $src : __DIR__ . '/' . $src;
          if (!file_exists($localPath)) return $m[0];
          $mime = mime_content_type($localPath) ?: 'image/png';
          $b64  = base64_encode(file_get_contents($localPath));
          return $m[1] . 'data:' . $mime . ';base64,' . $b64 . '"';
      },
      $html
  );
$html = preg_replace('#(<img\b[^>]+\bsrc=")(/[^"]+)#i', '$1' . $origin . '$2', $html);
// Fonts: dompdf writes its font metrics cache next to the fonts, so news/fonts/ must be writable.
$fontDir = __DIR__ . '/fonts';
$options = new Options();
$options->set('isRemoteEnabled', true);           // allow images
$options->set('isFontSubsettingEnabled', true);   // embed only the glyphs used (Korean fonts are large)
$options->set('fontDir', $fontDir);
$options->set('fontCache', $fontDir);
$options->set('chroot', __DIR__);
$options->set('defaultFont', 'NanumGothic');
$dompdf = new Dompdf($options);

// Register NanumGothic (OFL) for Hangul support. First run parses the .ttf files and
// writes metrics (nanumgothic_*.ttf/.ufm + cache json) into fonts/; later runs reuse them.
$fm = $dompdf->getFontMetrics();
if (!$fm->getFamily('NanumGothic')) {
    $fm->registerFont(['family' => 'NanumGothic', 'style' => 'normal', 'weight' => 'normal'], $fontDir . '/NanumGothic-Regular.ttf');
    $fm->registerFont(['family' => 'NanumGothic', 'style' => 'normal', 'weight' => 'bold'],   $fontDir . '/NanumGothic-Bold.ttf');
    $fm->registerFont(['family' => 'NanumGothic', 'style' => 'italic', 'weight' => 'normal'], $fontDir . '/NanumGothic-Regular.ttf');
    $fm->registerFont(['family' => 'NanumGothic', 'style' => 'italic', 'weight' => 'bold'],   $fontDir . '/NanumGothic-Bold.ttf');
}

$dompdf->loadHtml($html, 'UTF-8');

$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$dompdf->stream('newsletter_' . date('Ymd_His') . '.pdf', ['Attachment' => true]);

$fn = 'newsletter-' . date('Ymd-His') . '.pdf';
$path = rtrim(PDF_OUTPUT_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fn;
file_put_contents($path, $dompdf->output());

// Save newsletter record
$stmt = $pdo->prepare('INSERT INTO newsletters (title, start_date, end_date, issue_date, html, pdf_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$title, $start, $end, date('Y-m-d'), $html, $path, current_user()['id']]);

// Offer download
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $fn . '"');
readfile($path);
exit;
