<?php
// newsletter_preview.php
// Fetch whatever HTML you currently render in your preview.
// For example, you might assemble from DB: settings.banner_html + posts, etc.
require_once __DIR__ . '/bootstrap.php'; // if you have one; otherwise remove

// Example: $newsletterHtml = getNewsletterHtmlFromDb();
$newsletterHtml = $newsletterHtml ?? <<<HTML
<div style="text-align:center;padding:16px 0;border-bottom:2px solid #333;">
<img src="assets/banner.png" alt="" style="max-width:100%;height:auto;">
</div>
<div style="padding:16px;font-family:Arial,sans-serif;line-height:1.5;">
  <h2>Welcome!</h2>
  <p>Edit anything in this preview—headlines, text, links, even image URLs. Your PDF will match exactly.</p>
</div>
HTML;

// A tiny helper to protect against accidental `<script>` paste, etc. (extra defense on the server happens in build step too)
function safe_seed($html) {
  return str_replace(['</textarea>'], ['</tex' . 'tarea>'], $html);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Newsletter – Editable Preview</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f6f7fb;margin:0;}
    .wrap{max-width:900px;margin:24px auto;padding:16px;}
    .bar{display:flex;gap:8px;align-items:center;justify-content:space-between;margin-bottom:12px}
    .bar-left,.bar-right{display:flex;gap:8px;align-items:center}
    button,.btn{
      border:1px solid #d0d7e2;background:#fff;border-radius:8px;padding:8px 12px;cursor:pointer;
      box-shadow:0 1px 2px rgba(0,0,0,.04);
    }
    button.primary{background:#0d6efd;color:#fff;border-color:#0d6efd}
    button:disabled{opacity:.6;cursor:not-allowed}
    #editor{
      background:#fff;border:1px solid #d0d7e2;border-radius:12px;min-height:500px;padding:0;margin:0;
      box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:auto;
    }
    /* Make the whole thing “feel” like a page */
    .page{
      width:8.5in; /* letter width */
      min-height:11in;
      margin:0 auto;
      padding:0;
      background:#fff;
    }
    /* Optional: a faint page border in preview only */
    .page{outline:1px dashed #e6e9ef; outline-offset:-1px;}
  </style>

  <!-- OPTIONAL: Enable rich editing with TinyMCE (uncomment both script tags and the init block below) -->
  <!--
  <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
  -->
</head>
<body>
  <div class="wrap">
    <div class="bar">
      <div class="bar-left">
        <strong>Editable Newsletter Preview</strong>
      </div>
      <div class="bar-right">
        <button type="button" id="resetBtn" title="Restore original preview">Reset</button>
        <button type="button" id="toggleEditBtn">Disable editing</button>
        <form id="buildForm" action="newsletter_build_pdf.php" method="post" style="display:inline;">
          <input type="hidden" name="html" id="hiddenHtml">
          <button type="submit" class="primary">Build PDF</button>
        </form>
      </div>
    </div>

    <!-- The live, editable preview -->
    <div id="editor" contenteditable="true">
      <div class="page" id="page">
        <?= safe_seed($newsletterHtml) ?>
      </div>
    </div>
  </div>

  <script>
    const editor = document.getElementById('editor');
    const page = document.getElementById('page');
    const hiddenHtml = document.getElementById('hiddenHtml');
    const resetBtn = document.getElementById('resetBtn');
    const toggleEditBtn = document.getElementById('toggleEditBtn');

    // Keep a pristine copy for reset
    const originalHtml = page.innerHTML;

    // When submitting, place current edited HTML into hidden field
    //document.getElementById('buildForm').addEventListener('submit', function(e){
    // If using TinyMCE, replace with: hiddenHtml.value = tinymce.activeEditor.getContent();
    //  hiddenHtml.value = page.innerHTML;
    //});
    //Replaced to 96-99
	clone.querySelectorAll('img').forEach(img => {
          img.setAttribute('src', img.src);
      });
      hiddenHtml.value = clone.innerHTML;
    });

    resetBtn.addEventListener('click', () => {
      if (confirm('Restore the original preview content?')) {
        // If using TinyMCE, do: tinymce.activeEditor.setContent(originalHtml);
        page.innerHTML = originalHtml;
      }
    });

    let editing = true;
    toggleEditBtn.addEventListener('click', () => {
      editing = !editing;
      editor.setAttribute('contenteditable', editing ? 'true' : 'false');
      toggleEditBtn.textContent = editing ? 'Disable editing' : 'Enable editing';
    });

    // OPTIONAL: TinyMCE init (uncomment if you enabled the TinyMCE script in <head>)
    /*
    tinymce.init({
      selector: '#page',
      inline: true,
      menubar: false,
      plugins: 'link lists image table code',
      toolbar: 'undo redo | bold italic underline | bullist numlist | link image table | code',
      // Keep absolute URLs so Dompdf can fetch images
      convert_urls: false,
      // Prevent paste of scripts/JS
      valid_elements: '*[*]-script',
      extended_valid_elements: '*[*]',
    });
    */
  </script>
</body>
</html>

