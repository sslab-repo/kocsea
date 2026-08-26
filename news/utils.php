<?php
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): void { header('Location: ' . $path); exit; }
function base_url(string $path = ''): string { return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/'); }

// Rewrite relative / root-relative <img src> to absolute URLs (based on BASE_URL)
// so the HTML survives copy-paste into email clients (Gmail etc.).
function absolutize_img_src(string $html): string {
  $base   = rtrim(BASE_URL, '/');                       // https://host/kocsea/news
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

