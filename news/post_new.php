<?php
require_once __DIR__ . '/auth.php';
require_login();
$pdo = db();
$msg = $err = '';

// TEMP DEBUG (remove in production)
if (!headers_sent()) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/php-error.log');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If TinyMCE isn't initialized correctly, body_html may be empty.
    $title = trim($_POST['title'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date   = $_POST['end_date'] ?? '';
    $body_html  = $_POST['body_html'] ?? '';
    $status     = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

    if (!$title || !$start_date || !$end_date) {
        $err = 'Title, start date, and end date are required.';
    } elseif (strtotime($start_date) === false || strtotime($end_date) === false) {
        $err = 'Invalid date(s).';
    } elseif ($start_date > $end_date) {
        $err = 'Start date must be before or equal to end date.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO posts (user_id, title, body_html, start_date, end_date, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                current_user()['id'],
                $title,
                $body_html,
                $start_date,
                $end_date,
                $status
            ]);
            $msg = 'Post saved.';
            redirect(base_url('posts_list.php'));
        } catch (Throwable $e) {
            // Show a concise message, log the details
            error_log('Insert failed: ' . $e->getMessage());
            $err = 'Database error while saving the post.';
        }
    }
}

include __DIR__ . '/header.php';
?>
<h2>New Post</h2>
<?php if ($err): ?><mark class="secondary"><?= h($err) ?></mark><?php endif; ?>
<?php if ($msg): ?><mark><?= h($msg) ?></mark><?php endif; ?>

<form method="post" id="postForm">
  <label>Title <input name="title" required maxlength="255" value="<?= isset($title) ? h($title) : '' ?>"></label>
  <div style="display:flex; gap:1rem;">
    <label>First newsletter to appear <input type="date" name="start_date" required value="<?= isset($start_date) ? h($start_date) : '' ?>"></label>
    <label>Last newsletter to appear <input type="date" name="end_date" required value="<?= isset($end_date) ? h($end_date) : '' ?>"></label>
    <label>Status
      <select name="status">
        <option value="published" <?= (isset($status) && $status==='published')?'selected':''; ?>>Published</option>
        <option value="draft" <?= (isset($status) && $status==='draft')?'selected':''; ?>>Draft</option>
      </select>
    </label>
  </div>
  <label>Body</label>
  <textarea id="newseditor" name="body_html" rows="12" required><?= isset($body_html) ? h($body_html) : '' ?></textarea>
  <button type="submit">Save</button>
</form>

<?php $editorFormId = 'postForm'; include __DIR__ . '/editor.php'; ?>

<?php include __DIR__ . '/footer.php'; ?>

