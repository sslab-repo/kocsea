<?php
// newsletter_build_pdf.php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php'; // dompdf must be installed via composer
use Dompdf\Dompdf;
use Dompdf\Options;

// Basic input guard
if (!isset($_POST['html'])) {
    http_response_code(400);
    echo "Missing 'html' content.";
    exit;
}

$rawHtml = (string) $_POST['html'];

// Extra safety: strip scripts and on* handlers (you can swap for HTML Purifier if you prefer)
function sanitize_newsletter_html(string $html): string {
    // Remove <script>…</script>
    $html = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $html);
    // Remove inline event handlers like onclick, onload, etc.
    $html = preg_replace('#\son\w+="[^"]*"#i', '', $html);
    $html = preg_replace("#\son\w+='[^']*'#i", '', $html);
    // Remove javascript: URLs
    $html = preg_replace('#\shref\s*=\s*["\']\s*javascript:[^"\']*["\']#i', ' href="#"', $html);
    $html = preg_replace('#\ssrc\s*=\s*["\']\s*javascript:[^"\']*["\']#i', '', $html);
    return $html;
}
$html = sanitize_newsletter_html($rawHtml);

// Wrap in a full HTML document with print CSS tuned for US Letter
$doc = <<<HTML
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    /* Ensure the page is sized for Letter and margins work in Dompdf */
    @page { size: Letter; margin: 0.5in; }
    body { font-family: Arial, Helvetica, sans-serif; color:#000; }
    img { max-width: 100%; height: auto; }
    h1,h2,h3 { page-break-after: avoid; }
    .no-print { display:none !important; }
  </style>
</head>
<body>
{$html}
</body>
</html>
HTML;

// Configure Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);      // allow external images/CSS
$options->set('isHtml5ParserEnabled', true); // better tag support
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
// If you want to force Letter regardless of @page:
$dompdf->setPaper('letter', 'portrait');

$dompdf->loadHtml($doc);
$dompdf->render();

// Stream to browser (inline). Change Attachment=>1 to force download.
$filename = 'newsletter_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ['Attachment' => false]);

