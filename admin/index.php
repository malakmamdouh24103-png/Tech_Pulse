<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$stats = [
    ['Posts',      (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn(),                       'bi-file-text',  'grad-0'],
    ['Categories', (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),                  'bi-tags',       'grad-1'],
    ['Comments',   (int) $pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn(),                    'bi-chat-dots',  'grad-2'],
    ['Total views',(int) $pdo->query('SELECT COALESCE(SUM(views),0) FROM posts')->fetchColumn(),         'bi-eye',        'grad-3'],
];
$latestPosts = $pdo->query(
    'SELECT p.id, p.title, p.status, p.created_at, c.name AS category_name
       FROM posts p JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC LIMIT 5'
)->fetchAll();
$latestComments = $pdo->query(
    'SELECT m.comment, m.author_name, m.created_at, p.title
       FROM comments m JOIN posts p ON p.id = m.post_id ORDER BY m.id DESC LIMIT 5'
)->fetchAll();
$chart = $pdo->query(
    'SELECT c.name, COUNT(p.id) AS total FROM categories c
       LEFT JOIN posts p ON p.category_id = c.id GROUP BY c.id ORDER BY c.name'
)->fetchAll();

$flags  = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$labels = json_encode(array_column($chart, 'name'), $flags);
$values = json_encode(array_map('intval', array_column($chart, 'total')));
$extraScript = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>'
    . '<script>new Chart(document.getElementById("catChart"),{type:"doughnut",data:{labels:' . $labels
    . ',datasets:[{data:' . $values . ',backgroundColor:["#6d28d9","#2563eb","#db2777","#059669","#f59e0b","#0ea5e9"]}]},'
    . 'options:{plugins:{legend:{position:"bottom"}}}});</script>';

$pageTitle = 'Dashboard';
$active    = 'dashboard';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="py-4">

        <div class="d-flex justify-content-between flex-wrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="bi bi-speedometer2"></i> Dashboard Overview</h1>
            <a href="<?= BASE_URL ?>/admin/posts/addpost.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New post</a>
        </div>
        <?php show_flash(); ?>

        <div class="row g-3 mb-4">
            <?php foreach ($stats as [$label, $value, $icon, $grad]): ?>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card <?= $grad ?>"><div class="card-body d-flex justify-content-between align-items-center">
                        <div><div class="small text-white-50"><?= e($label) ?></div><div class="display-6 fw-bold"><?= $value ?></div></div>
                        <i class="bi <?= $icon ?> fs-1 opacity-75"></i>
                    </div></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card side-card h-100"><div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-pie-chart"></i> Posts per category</h5>
                    <canvas id="catChart" height="220"></canvas>
                </div></div>
            </div>
            <div class="col-xl-8">
                <div class="card side-card mb-4"><div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-clock-history"></i> Latest posts</h5>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($latestPosts as $p): ?>
                                <tr>
                                    <td><?= e($p['title']) ?></td>
                                    <td><?= e($p['category_name']) ?></td>
                                    <td><span class="badge <?= $p['status'] === 'published' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($p['status']) ?></span></td>
                                    <td><?= e(fmt_date($p['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div></div>

                <div class="card side-card"><div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-chat-dots"></i> Latest comments</h5>
                    <?php foreach ($latestComments as $m): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <strong><?= e($m['author_name']) ?></strong> on <em><?= e($m['title']) ?></em>
                            <div class="text-secondary small"><?= e($m['comment']) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$latestComments): ?><p class="text-muted mb-0">No comments yet.</p><?php endif; ?>
                </div></div>
            </div>
        </div>

    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
