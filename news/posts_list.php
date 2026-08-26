<?php
require_once __DIR__ . '/auth.php';
require_login();
$pdo = db();
$u = current_user();

if ($u['role'] === 'admin') {
    $stmt = $pdo->query('SELECT p.*, u.name AS author FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.end_date desc, p.created_at DESC');
} else {
    $stmt = $pdo->prepare('SELECT p.*, u.name AS author FROM posts p JOIN users u ON u.id = p.user_id WHERE p.user_id = ? ORDER BY p.end_date desc, p.created_at DESC');
    $stmt->execute([$u['id']]);
}
$posts = $stmt->fetchAll();
include __DIR__ . '/header.php';
?>
<h2><?= $u['role']==='admin' ? 'All Posts' : 'My Posts' ?></h2>
<?php foreach ($posts as $p): ?>
  <article class="post">
    <header style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
      <strong><?= h($p['title']) ?></strong>
      <span>
        <?php if ($u['role']==='admin' || (int)$u['id']===(int)$p['user_id']): ?>
          <a class="contrast" href="<?= h(base_url('post_edit.php?id='.(int)$p['id'])) ?>">Edit</a>
          <a href="#" class="danger delete-link" data-id="<?= (int)$p['id'] ?>">Delete</a>
        <?php endif; ?>
      </span>
    </header>
    <small>By <?= h($p['author']) ?> | Active: <?= h($p['start_date']) ?> → <?= h($p['end_date']) ?> | Status: <?= h($p['status']) ?></small>
    <div><?= $p['body_html'] ?></div>
  </article>
<?php endforeach; ?>

<!-- Reusable hidden delete form to keep DELETE as a POST with CSRF -->
<form id="deleteForm" method="post" action="<?= h(base_url('post_delete.php')) ?>" style="display:none;">
  <input type="hidden" name="id" id="delete_id">
  <?= csrf_field() ?>
</form>
<script>
  document.addEventListener('click', function (e) {
    const a = e.target.closest('a.delete-link');
    if (!a) return;
    e.preventDefault();
    if (!confirm('Delete this post? This cannot be undone.')) return;
    document.getElementById('delete_id').value = a.dataset.id;
    document.getElementById('deleteForm').submit();
  });
</script>
<?php include __DIR__ . '/footer.php'; ?>
