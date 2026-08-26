<?php
require_once __DIR__ . '/auth.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (attempt_login($username, $password)) {
        redirect(base_url('dashboard.php'));
    } else {
        $err = 'Invalid credentials or inactive account.';
    }
}
include __DIR__ . '/header.php';
?>
<article>
  <h2>Login</h2>
  <?php if ($err): ?><mark class="secondary"><?= h($err) ?></mark><?php endif; ?>
  <form method="post" action="">
    <label>Username <input type="text" name="username" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button type="submit">Sign in</button>
  </form>
</article>
<?php include __DIR__ . '/footer.php'; ?>
