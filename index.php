<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

$cat     = (int) ($_GET['cat'] ?? 0);
$q       = trim((string) ($_GET['q'] ?? ''));
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

$where  = ["p.status = 'published'"];
$params = [];
if ($cat > 0) {
    $where[] = 'p.category_id = :cat';
    $params[':cat'] = $cat;
}
if ($q !== '') {
    $where[] = '(p.title LIKE :q1 OR p.content LIKE :q2)';
    $params[':q1'] = $params[':q2'] = '%' . $q . '%';
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE $whereSql");
$stmt->execute($params);
$total  = (int) $stmt->fetchColumn();
$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.icon,
            (SELECT COUNT(*) FROM comments m WHERE m.post_id = p.id) AS comments_count
       FROM posts p JOIN categories c ON c.id = p.category_id
      WHERE $whereSql
      ORDER BY p.created_at DESC, p.id DESC
      LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$categories = $pdo->query(
    "SELECT c.id, c.name, c.icon, COUNT(p.id) AS total
       FROM categories c LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published'
      GROUP BY c.id ORDER BY c.name"
)->fetchAll();
$popular    = $pdo->query("SELECT id, title, views FROM posts WHERE status = 'published' ORDER BY views DESC LIMIT 4")->fetchAll();
$totalPosts = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();

$title = 'Home';
require __DIR__ . '/layout/site_header.php';
?>
<section class="hero text-white">
  <div class="container py-5">
    <span class="badge rounded-pill text-bg-warning mb-3"><i class="bi bi-broadcast"></i> Live tech updates</span>
    <h1 class="display-5 fw-bold">Your daily dose of <span class="text-gradient">tech news</span></h1>
    <p class="lead text-white-50 col-lg-7">Programming, AI, cybersecurity and gadgets, explained simply.</p>
    <form method="get" class="search-box input-group mt-4" style="max-width:560px" role="search">
      <?php if ($cat): ?><input type="hidden" name="cat" value="<?= $cat ?>"><?php endif; ?>
      <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search news..." aria-label="Search news">
      <button class="btn btn-warning fw-semibold"><i class="bi bi-search"></i> Search</button>
    </form>
    <div class="d-flex gap-3 mt-4 flex-wrap">
      <div class="hero-stat"><i class="bi bi-newspaper"></i> <strong><?= $totalPosts ?></strong> articles</div>
      <div class="hero-stat"><i class="bi bi-tags"></i> <strong><?= count($categories) ?></strong> categories</div>
    </div>
  </div>
</section>

<div class="container py-4">
  <div class="d-flex flex-wrap gap-2 mb-4">
    <a href="index.php" class="btn btn-sm cat-pill <?= $cat === 0 ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="bi bi-collection"></i> All</a>
    <?php foreach ($categories as $c): ?>
      <a href="index.php?cat=<?= (int) $c['id'] ?>" class="btn btn-sm cat-pill <?= $cat === (int) $c['id'] ? 'btn-primary' : 'btn-outline-primary' ?>">
        <i class="bi <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <?php if ($q !== ''): ?><p class="text-secondary"><?= $total ?> result(s) for "<strong><?= e($q) ?></strong>"</p><?php endif; ?>

      <?php if (!$posts): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
          <i class="bi bi-inbox display-3 text-secondary"></i>
          <h4 class="mt-3">No articles found</h4>
          <a href="index.php" class="btn btn-primary mt-2">Back to home</a>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($posts as $p): ?>
            <div class="col-md-6">
              <article class="card news-card h-100 position-relative">
                <?php if ($p['image']): ?>
                  <img class="card-img-cover" src="<?= e(UPLOAD_URL . $p['image']) ?>" alt="<?= e($p['title']) ?>">
                <?php else: ?>
                  <div class="card-banner grad-<?= (int) $p['category_id'] % 4 ?>"><i class="bi <?= e($p['icon']) ?>"></i></div>
                <?php endif; ?>
                <div class="card-body d-flex flex-column">
                  <span class="badge text-bg-light align-self-start mb-2"><i class="bi <?= e($p['icon']) ?>"></i> <?= e($p['category_name']) ?></span>
                  <h5 class="card-title"><a class="stretched-link" href="post.php?id=<?= (int) $p['id'] ?>"><?= e($p['title']) ?></a></h5>
                  <p class="card-text text-secondary small flex-grow-1"><?= e(excerpt($p['content'])) ?></p>
                  <div class="small text-muted d-flex justify-content-between">
                    <span><i class="bi bi-calendar3"></i> <?= e(fmt_date($p['created_at'])) ?></span>
                    <span><i class="bi bi-chat-dots"></i> <?= (int) $p['comments_count'] ?> &middot; <i class="bi bi-eye"></i> <?= (int) $p['views'] ?></span>
                  </div>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
        <nav class="mt-4" aria-label="Pagination">
          <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $page - 1])) ?>">&laquo;</a></li>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $i])) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $page + 1])) ?>">&raquo;</a></li>
          </ul>
        </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <aside class="col-lg-4">
      <div class="card side-card mb-4"><div class="card-body">
        <h5 class="mb-3"><i class="bi bi-fire text-danger"></i> Most read</h5>
        <?php foreach ($popular as $i => $p): ?>
          <a href="post.php?id=<?= (int) $p['id'] ?>" class="d-flex gap-3 text-decoration-none text-dark mb-3">
            <span class="fs-3 fw-bold text-primary"><?= $i + 1 ?></span>
            <span><span class="d-block fw-semibold small"><?= e($p['title']) ?></span>
            <small class="text-muted"><i class="bi bi-eye"></i> <?= (int) $p['views'] ?> views</small></span>
          </a>
        <?php endforeach; ?>
      </div></div>
      <div class="card side-card"><div class="card-body">
        <h5 class="mb-3"><i class="bi bi-tags text-primary"></i> Categories</h5>
        <?php foreach ($categories as $c): ?>
          <a href="index.php?cat=<?= (int) $c['id'] ?>" class="d-flex justify-content-between align-items-center text-decoration-none py-2 border-bottom">
            <span><i class="bi <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></span>
            <span class="badge text-bg-primary rounded-pill"><?= (int) $c['total'] ?></span>
          </a>
        <?php endforeach; ?>
      </div></div>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/layout/site_footer.php'; ?>
