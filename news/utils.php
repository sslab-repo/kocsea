<?php
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): void { header('Location: ' . $path); exit; }
function base_url(string $path = ''): string { return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/'); }

// Rewrite relative / root-relative <img src> to absolute URLs so the HTML
// survives copy-paste into email clients (Gmail etc.).
// Images use ASSET_BASE_URL if defined in config.php; otherwise BASE_URL with
// the scheme forced to http:// (the host's HTTPS cert doesn't cover this domain,
// so Gmail's image proxy rejects https image URLs).
function absolutize_img_src(string $html): string {
  $base = defined('ASSET_BASE_URL') ? ASSET_BASE_URL : preg_replace('#^https://#i', 'http://', BASE_URL);
  $base   = rtrim($base, '/');                          // http://host/kocsea/news
  $origin = preg_replace('#^(https?://[^/]+).*$#i', '$1', $base); // https://host
  return preg_replace_callback(
    '#(<img\b[^>]*\bsrc=)(["\'])([^"\']+)\2#i',
    function ($m) use ($base, $origin) {
      $src = $m[3];
      if (preg_match('#^(https?:)?//|^data:#i', $src)) return $m[0]; // already absolute
      $abs = ($src[0] === '/') ? $origin . $src : $base . '/' . ltrim($src, './');
      return $m[1] . $m[2] . $abs . $m[2];
    },
    $html
  );
}

// Center every <img> with inline styles (survives copy-paste into email and is honored by
// dompdf). Drops align= / float so TinyMCE's left/right alignment can't override it.
function center_images(string $html): string {
  return preg_replace_callback('#<img\b[^>]*>#i', function ($m) {
    $tag = $m[0];
    $tag = preg_replace('#\salign=(["\'])[^"\']*\1#i', '', $tag);
    $center = 'display:block;margin-left:auto;margin-right:auto;';
    if (preg_match('#\sstyle=(["\'])(.*?)\1#is', $tag, $s)) {
      $style = preg_replace('#\s*(float|display|margin(-left|-right)?)\s*:[^;"\']*;?#i', '', $s[2]);
      $style = rtrim(trim($style), ';');
      $style = ($style !== '' ? $style . ';' : '') . $center;
      $tag = str_replace($s[0], ' style=' . $s[1] . $style . $s[1], $tag);
    } else {
      $tag = preg_replace('#<img\b#i', '<img style="' . $center . '"', $tag, 1);
    }
    return $tag;
  }, $html);
}

// CSRF helpers
if (session_status() === PHP_SESSION_NONE) { session_start(); }
function csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_check(): bool {
  return isset($_POST['csrf']) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf']);
}

