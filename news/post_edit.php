<?php
require_once __DIR__ . '/auth.php';
require_login();
$pdo = db();
$u = current_user();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); exit('Invalid id'); }

// Load post
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); exit('Post not found'); }

// Permission: admin or owner
if ($u['role'] !== 'admin' && (int)$post['user_id'] !== (int)$u['id']) {
  http_response_code(403); exit('Forbidden');
}

$err = $msg = '';
// Defaults from DB
$title       = $post['title'];
$start_date  = $post['start_date'];
$end_date    = $post['end_date'];
$body_html   = $post['body_html'];
$status      = $post['status'];
$post_order  = isset($post['post_order']) ? (int)$post['post_order'] : 0; // NEW (admin)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check()) { http_response_code(400); exit('Bad CSRF token'); }

  $title       = trim($_POST['title'] ?? '');
  $start_date  = $_POST['start_date'] ?? '';
  $end_date    = $_POST['end_date'] ?? '';
  $body_html   = $_POST['body_html'] ?? '';
  $status      = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

  // Only admins are allowed to change post_order
  if ($u['role'] === 'admin') {                                      // NEW (admin)
    // allow empty to mean "no change" or coerce to int if set
    if (isset($_POST['post_order']) && $_POST['post_order'] !== '') {
      // Accept any integer; clamp to a safe range if you want
      $post_order = (int)$_POST['post_order'];
    }
  }                                                                   // NEW (admin)

  if (!$title || !$start_date || !$end_date) {
    $err = 'Title, start date, and end date are required.';
  } elseif (strtotime($start_date) === false || strtotime($end_date) === false) {
    $err = 'Invalid date(s).';
  } elseif ($start_date > $end_date) {
    $err = 'Start date must be before or equal to end date.';
  } else {
    // Build UPDATE dynamically so non-admins cannot alter post_order
    if ($u['role'] === 'admin') {                                     // NEW (admin)
      $upd = $pdo->prepare(
        'UPDATE posts
           SET title=?, body_html=?, start_date=?, end_date=?, status=?, post_order=?
         WHERE id=?'
      );
      $upd->execute([$title, $body_html, $start_date, $end_date, $status, $post_order, $id]);
    } else {
      $upd = $pdo->prepare(
        'UPDATE posts
           SET title=?, body_html=?, start_date=?, end_date=?, status=?
         WHERE id=?'
      );
      $upd->execute([$title, $body_html, $start_date, $end_date, $status, $id]);
    }                                                                 // NEW (admin)
    $msg = 'Post updated.';
  }
}

include __DIR__ . '/header.php';
?>
<h2>Edit Post</h2>
<?php if ($err): ?><mark class="secondary"><?= h($err) ?></mark><?php endif; ?>
<?php if ($msg): ?><mark><?= h($msg) ?></mark><?php endif; ?>
<form method="post" id="editForm">
  <?= csrf_field() ?>
  <label>Title <input name="title" required maxlength="255" value="<?= h($title) ?>"></label>

  <div style="display:flex; gap:1rem; flex-wrap:wrap;">
    <label>First newsletter to appear
      <input type="date" name="start_date" required value="<?= h($start_date) ?>">
    </label>
    <label>Last newsletter to appear
      <input type="date" name="end_date" required value="<?= h($end_date) ?>">
    </label>
    <label>Status
      <select name="status">
        <option value="published" <?= $status==='published'?'selected':''; ?>>Published</option>
        <option value="draft" <?= $status==='draft'?'selected':''; ?>>Draft</option>
      </select>
    </label>

    <?php if ($u['role'] === 'admin'): ?>                             <!-- NEW (admin) -->
      <label>Post Order
        <input type="number" name="post_order" value="<?= h((string)$post_order) ?>" step="1">
      </label>
    <?php endif; ?>                                                   <!-- NEW (admin) -->
  </div>

  <label>Body</label>
  <textarea id="newseditor" name="body_html" rows="12" required><?= h($body_html) ?></textarea>

  <button type="submit">Save Changes</button>
  <a class="secondary" href="<?= h(base_url('posts_list.php')) ?>">Back</a>
</form>

<?php $editorFormId = 'editForm'; include __DIR__ . '/editor.php'; ?>
<?php include __DIR__ . '/footer.php'; ?>

