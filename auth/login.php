<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_check()) {
        $error = 'Invalid form token. Please try again.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :u');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ' . BASE_URL . '/admin/index.php');
            exit;
        }
        $error = 'Wrong username or password.';
    }
}

$title = 'Admin Login';
require __DIR__ . '/../layout/site_header.php';
?>
<div class="container py-5">
  <div class="card login-card mx-auto"><div class="card-body p-4 p-md-5">
    <div class="text-center mb-4"><i class="bi bi-shield-lock-fill display-4 text-primary"></i><h1 class="h4 mt-2">Admin Login</h1></div>
    <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label" for="username">Username</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span>
          <input class="form-control" id="username" name="username" required></div></div>
      <div class="mb-4"><label class="form-label" for="password">Password</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-key"></i></span>
          <input type="password" class="form-control" id="password" name="password" required></div></div>
      <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Sign in</button>
    </form>
  </div></div>
</div>
<?php require __DIR__ . '/../layout/site_footer.php'; ?>
