<?php
// Public site header. Page sets $title before including.
$title   = $title ?? 'TechPulse';
$navCats = $pdo->query('SELECT id, name, icon FROM categories ORDER BY name')->fetchAll();
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> | TechPulse</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark site-nav sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php"><i class="bi bi-lightning-charge-fill text-warning"></i> TechPulse</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php"><i class="bi bi-house-door"></i> Home</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-grid"></i> Categories</a>
          <ul class="dropdown-menu">
            <?php foreach ($navCats as $c): ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/index.php?cat=<?= (int) $c['id'] ?>"><i class="bi <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
      </ul>
      <a class="btn btn-warning btn-sm fw-semibold" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-person-gear"></i> <?= empty($_SESSION['user_id']) ? 'Admin login' : 'Dashboard' ?></a>
    </div>
  </div>
</nav>
