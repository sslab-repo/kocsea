<?php
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): void { header('Location: ' . $path); exit; }
function base_url(string $path = ''): string { return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/'); }

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

