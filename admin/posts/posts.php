<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
require_login();

$q       = trim((string) ($_GET['q'] ?? ''));
$cat     = (int) ($_GET['cat'] ?? 0);
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;

$where  = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = 'p.title LIKE :q';
    $params[':q'] = '%' . $q . '%';
}
if ($cat > 0) {
    $where[] = 'p.category_id = :cat';
    $params[':cat'] = $cat;
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE $whereSql");
$stmt->execute($params);
$total  = (int) $stmt->fetchColumn();
$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

// One query for everything (category name + comments count) instead of a query per row
$stmt = $pdo->prepare(
    "SELECT p.id, p.title, p.image, p.status, p.views, p.created_at, p.category_id,
            c.name AS category_name, c.icon,
            (SELECT COUNT(*) FROM comments m WHERE m.post_id = p.id) AS comments_count
       FROM posts p JOIN categories c ON c.id = p.category_id
      WHERE $whereSql
      ORDER BY p.id DESC
      LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$extraScript = <<<'JS'
<script>
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (ev) {
  var btn = ev.relatedTarget;
  document.getElementById('deleteId').value = btn.dataset.id;
  document.getElementById('deleteTitle').textContent = btn.dataset.title;
});
</script>
JS;

$pageTitle = 'Posts';
$active    = 'posts';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="py-4">

        <div class="d-flex justify-content-between flex-wrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="bi bi-file-text"></i> Posts Management</h1>
            <a href="addpost.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Post</a>
        </div>

        <?php show_flash(); ?>

        <form method="get" class="row g-2 mb-3">
            <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search by title..."></div>
            <div class="col-md-4">
                <select class="form-select" name="cat">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                <a href="posts.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="card side-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr><th>ID</th><th>Image</th><th>Title</th><th>Category</th><th>Status</th><th>Comments</th><th>Views</th><th>Created At</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($posts as $row): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <td>
                                    <?php if ($row['image']): ?>
                                        <img class="thumb" src="<?= e(UPLOAD_URL . $row['image']) ?>" alt="">
                                    <?php else: ?>
                                        <span class="thumb-empty grad-<?= (int) $row['category_id'] % 4 ?>"><i class="bi <?= e($row['icon']) ?>"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($row['title']) ?></td>
                                <td><?= e($row['category_name']) ?></td>
                                <td><span class="badge <?= $row['status'] === 'published' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($row['status']) ?></span></td>
                                <td><span class="badge text-bg-info"><?= (int) $row['comments_count'] ?></span></td>
                                <td><?= (int) $row['views'] ?></td>
                                <td><?= e(fmt_date($row['created_at'])) ?></td>
                                <td class="text-nowrap">
                                    <?php if ($row['status'] === 'published'): ?>
                                        <a href="<?= BASE_URL ?>/post.php?id=<?= (int) $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                    <?php endif; ?>
                                    <a href="editpost.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-id="<?= (int) $row['id'] ?>" data-title="<?= e($row['title']) ?>">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$posts): ?>
                            <tr><td colspan="9" class="text-center">No Posts Found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pages > 1): ?>
                <nav aria-label="Pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $page - 1])) ?>">&laquo;</a></li>
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $i])) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $page + 1])) ?>">&raquo;</a></li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="deletepost.php" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="deleteId">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> Confirm delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete <strong id="deleteTitle"></strong>? Its comments and image will be deleted too.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
