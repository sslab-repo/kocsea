<?php
// upload_image.php — TinyMCE image upload endpoint.
// Accepts multipart POST {file, csrf}; saves to news/uploads/; returns JSON {location}.
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

function fail(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

if (!current_user()) fail(401, 'Login required');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'POST only');
if (!csrf_check()) fail(400, 'Bad CSRF token');
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) fail(400, 'No file uploaded');

$f = $_FILES['file'];
$maxBytes = 5 * 1024 * 1024; // 5 MB
if ($f['size'] > $maxBytes) fail(413, 'Image too large (max 5 MB)');

// Verify it is really an image and pick the extension from the detected type
$info = @getimagesize($f['tmp_name']);
if ($info === false) fail(415, 'Not an image');
$extByMime = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$mime = $info['mime'] ?? '';
if (!isset($extByMime[$mime])) fail(415, 'Unsupported image type: ' . $mime);

$dir = __DIR__ . '/uploads';
if (!is_dir($dir) && !mkdir($dir, 0775, true)) fail(500, 'Cannot create uploads dir');

$name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extByMime[$mime];
$dest = $dir . '/' . $name;
if (!move_uploaded_file($f['tmp_name'], $dest)) fail(500, 'Cannot save file');
@chmod($dest, 0644);

// Relative to the app dir: resolves in the editor/preview, and build_newsletter.php
// inlines it into the PDF (absolutize_img_src() makes it absolute for email copy).
echo json_encode(['location' => 'uploads/' . $name]);
