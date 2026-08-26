<?php
require_once __DIR__ . '/auth.php';
require_login();
$pdo = db();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_check()) { http_response_code(400); exit('Bad CSRF token'); }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { http_response_code(400); exit('Invalid id'); }

// Check ownership/permission
$stmt = $pdo->prepare('SELECT user_id FROM posts WHERE id = ?');
$stmt->execute([$id]);
$owner = $stmt->fetchColumn();
if ($owner === false) { http_response_code(404); exit('Not found'); }
if ($u['role'] !== 'admin' && (int)$owner !== (int)$u['id']) {
  http_response_code(403); exit('Forbidden');
}

$del = $pdo->prepare('DELETE FROM posts WHERE id = ?');
$del->execute([$id]);

redirect(base_url('posts_list.php'));

