<?php require_once __DIR__ . '/auth.php'; $u = current_user(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h(SITE_NAME) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  <style>
    body { max-width: 980px; margin: 0 auto; }
    .post { border: 1px solid #ddd; padding: 16px; border-radius: 8px; margin-bottom: 16px; }
  </style>
</head>
<body>
<header style="display:flex; gap:1rem; align-items:center; padding:1rem 0;">
  <strong><?= h(SITE_NAME) ?></strong>
  <nav style="margin-left:auto; display:flex; gap:1rem;">
    <?php if ($u): ?>
      <a href="<?= h(base_url('dashboard.php')) ?>">Dashboard</a>
      <a href="<?= h(base_url('post_new.php')) ?>">New Post</a>
      <a href="<?= h(base_url('posts_list.php')) ?>">My Posts</a>
      <?php if ($u['role'] === 'admin'): ?>
        <a href="<?= h(base_url('admin_newsletter.php')) ?>">Build Newsletter</a>
      <?php endif; ?>
      <a href="<?= h(base_url('logout.php')) ?>">Logout</a>
    <?php else: ?>
      <a href="<?= h(base_url('login.php')) ?>">Login</a>
    <?php endif; ?>
  </nav>
</header>
<main>
