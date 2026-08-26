<?php
require_once __DIR__ . '/auth.php';
require_login();
include __DIR__ . '/header.php';
?>
<h3>Welcome, <?= h(current_user()['name']) ?>!</h3>
<p>Create a new post or review your existing ones. Admins can build the weekly newsletter.</p>
<ul>
  <li><a href="<?= h(base_url('post_new.php')) ?>">Create a Post</a></li>
  <li><a href="<?= h(base_url('posts_list.php')) ?>">My Posts</a></li>
  <?php if (current_user()['role'] === 'admin'): ?>
    <li><a href="<?= h(base_url('admin_newsletter.php')) ?>">Build Newsletter</a></li>
  <?php endif; ?>
</ul>
<?php include __DIR__ . '/footer.php'; ?>
