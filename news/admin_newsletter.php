<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/auth.php';
require_admin();
$pdo = db();

// Default to the current week (Mon-Sun)
$today = new DateTime('today');
$weekStart = (clone $today)->modify('friday this week');
$weekEnd = (clone $weekStart)->modify('+6 days');

$start = $_GET['start'] ?? $weekStart->format('Y-m-d');
$end = (new DateTime($start))->modify('+6 days')->format('Y-m-d');
$title = $_GET['title'] ?? ''; //('Weekly Newsletter for ' . $start);

// Load banner HTML
$banner = $pdo->query("SELECT `value` FROM settings WHERE `key`='banner_html'")->fetchColumn();

// Fetch posts overlapping the selected window and published
$stmt = $pdo->prepare('SELECT p.*, u.name AS author FROM posts p JOIN users u ON u.id = p.user_id
  WHERE p.status = "published" AND p.start_date <= ? AND p.end_date >= ?
  ORDER BY post_order, p.updated_at ASC');
$stmt->execute([$end, $start]);
$posts = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>
<h2>Build Newsletter</h2>
<form method="get" class="grid">
  <label>Newsletter date<input type="date" name="start" value="<?= h($start) ?>" required></label>
<!--  <label>End (choose a Thursday) <input type="date" name="end" value="<?= h($end) ?>" required></label>-->
<!--  <label>Title <input type="text" name="title" value="<?= h($title) ?>" required></label> -->
  <button type="submit">Preview</button>
</form>

<hr>
<h3>Preview</h3>
<div class="post">
  <?php /* banner intentionally omitted from the preview (copy-paste to email); it is still rendered in the PDF */ ?>
  <?php if (!$posts): ?>
    <p><em>No posts found in this window.</em></p>
  <?php else: ?>
    <?php foreach ($posts as $p): ?>
      <section style="margin: 24px 0;">
        <h3 style="font-family:Arial,sans-serif; border-bottom:1px solid #ddd; padding-bottom:8px;"><?= h($p['title']) ?></h3>
        <div><?= absolutize_img_src($p['body_html']) ?></div>
       <!-- <p style="font-size:12px;color:#777;">Posted by <?= h($p['author']) ?></p> -->
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
  <div style="border-bottom:1px solid #ccc; margin:12px 0;"></div>
  <p style="text-align:right; color:#666; font-size:9pt; margin:4px 0 0;">Coverage: <?= h($start) ?> to <?= h($end) ?></p>
</div>

<form method="post" action="build_newsletter.php">
  <input type="hidden" name="start" value="<?= h($start) ?>">
  <input type="hidden" name="end" value="<?= h($end) ?>">
  <input type="hidden" name="title" value="<?= h($title) ?>">
  <button type="submit" <?= empty($posts) ? 'disabled' : '' ?>>Build PDF</button>
</form>

<?php include __DIR__ . '/footer.php'; ?>
