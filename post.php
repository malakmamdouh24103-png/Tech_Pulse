<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

$id   = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.icon
       FROM posts p JOIN categories c ON c.id = p.category_id
      WHERE p.id = :id AND p.status = 'published'"
);
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $title = 'Not found';
    require __DIR__ . '/layout/site_header.php';
    echo '<div class="container py-5 text-center"><i class="bi bi-emoji-frown display-1 text-secondary"></i>'
       . '<h2 class="mt-3">Article not found</h2><a class="btn btn-primary mt-3" href="index.php">Back to home</a></div>';
    require __DIR__ . '/layout/site_footer.php';
    exit;
}

// Add a comment (visitors)
$commentErrors = [];
$old = ['author_name' => '', 'comment' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['author_name'] = trim((string) ($_POST['author_name'] ?? ''));
    $old['comment']     = trim((string) ($_POST['comment'] ?? ''));
    if (!csrf_check()) {
        $commentErrors[] = 'Invalid form token. Please try again.';
    } else {
        if (mb_strlen($old['author_name']) > 60) {
            $commentErrors[] = 'Name must be 60 characters or less.';
        }
        if (mb_strlen($old['comment']) < 3 || mb_strlen($old['comment']) > 500) {
            $commentErrors[] = 'Comment must be between 3 and 500 characters.';
        }
        if (!$commentErrors) {
            $pdo->prepare('INSERT INTO comments (post_id, author_name, comment) VALUES (:p, :a, :c)')
                ->execute([':p' => $id, ':a' => $old['author_name'] !== '' ? $old['author_name'] : 'Guest', ':c' => $old['comment']]);
            flash('success', 'Thanks! Your comment was added.');
            header('Location: post.php?id=' . $id . '#comments');
            exit;
        }
    }
}

// Count one view per visitor session
if (empty($_SESSION['viewed'][$id])) {
    $pdo->prepare('UPDATE posts SET views = views + 1 WHERE id = :id')->execute([':id' => $id]);
    $_SESSION['viewed'][$id] = true;
    $post['views']++;
}

$stmt = $pdo->prepare('SELECT author_name, comment, created_at FROM comments WHERE post_id = :id ORDER BY id DESC');
$stmt->execute([':id' => $id]);
$comments = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT id, title, created_at FROM posts
      WHERE category_id = :c AND id <> :id AND status = 'published' ORDER BY created_at DESC LIMIT 3"
);
$stmt->execute([':c' => $post['category_id'], ':id' => $id]);
$related = $stmt->fetchAll();

$paragraphs = preg_split('/\R{2,}/', trim($post['content']));
$title = $post['title'];
require __DIR__ . '/layout/site_header.php';
?>
<div class="container py-4">
  <nav aria-label="breadcrumb"><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="index.php?cat=<?= (int) $post['category_id'] ?>"><?= e($post['category_name']) ?></a></li>
    <li class="breadcrumb-item active" aria-current="page">Article</li>
  </ol></nav>

  <div class="row g-4">
    <article class="col-lg-8">
      <div class="card side-card overflow-hidden">
        <?php if ($post['image']): ?>
          <img src="<?= e(UPLOAD_URL . $post['image']) ?>" class="w-100" style="max-height:340px;object-fit:cover" alt="<?= e($post['title']) ?>">
        <?php else: ?>
          <div class="card-banner grad-<?= (int) $post['category_id'] % 4 ?>" style="height:180px;font-size:4.5rem"><i class="bi <?= e($post['icon']) ?>"></i></div>
        <?php endif; ?>
        <div class="card-body p-4 p-md-5">
          <span class="badge text-bg-primary mb-3"><i class="bi <?= e($post['icon']) ?>"></i> <?= e($post['category_name']) ?></span>
          <h1 class="h2 fw-bold"><?= e($post['title']) ?></h1>
          <p class="text-muted small border-bottom pb-3">
            <i class="bi bi-calendar3"></i> <?= e(fmt_date($post['created_at'])) ?> &middot;
            <i class="bi bi-clock"></i> <?= read_time($post['content']) ?> min read &middot;
            <i class="bi bi-eye"></i> <?= (int) $post['views'] ?> views
          </p>
          <div class="post-body">
            <?php foreach ($paragraphs as $para): ?><p><?= nl2br(e($para)) ?></p><?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="card side-card mt-4" id="comments"><div class="card-body p-4">
        <h5 class="mb-3"><i class="bi bi-chat-dots text-primary"></i> Comments (<?= count($comments) ?>)</h5>
        <?php show_flash(); ?>
        <?php foreach ($commentErrors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" class="mb-4">
          <?= csrf_field() ?>
          <div class="row g-2">
            <div class="col-md-4"><input class="form-control" name="author_name" maxlength="60" placeholder="Your name (optional)" value="<?= e($old['author_name']) ?>"></div>
            <div class="col-md-8"><input class="form-control" name="comment" maxlength="500" placeholder="Write a comment..." value="<?= e($old['comment']) ?>" required></div>
          </div>
          <button class="btn btn-primary mt-2"><i class="bi bi-send"></i> Post comment</button>
        </form>
        <?php foreach ($comments as $cm): ?>
          <div class="d-flex gap-3 mb-3">
            <i class="bi bi-person-circle fs-3 text-secondary"></i>
            <div><strong><?= e($cm['author_name']) ?></strong> <small class="text-muted">&middot; <?= e(fmt_date($cm['created_at'])) ?></small>
              <div><?= e($cm['comment']) ?></div></div>
          </div>
        <?php endforeach; ?>
        <?php if (!$comments): ?><p class="text-muted mb-0">No comments yet. Be the first!</p><?php endif; ?>
      </div></div>
    </article>

    <aside class="col-lg-4">
      <div class="card side-card"><div class="card-body">
        <h5 class="mb-3"><i class="bi bi-link-45deg text-primary"></i> Related articles</h5>
        <?php if (!$related): ?><p class="text-muted small mb-0">No related articles yet.</p><?php endif; ?>
        <?php foreach ($related as $r): ?>
          <a href="post.php?id=<?= (int) $r['id'] ?>" class="d-block text-decoration-none text-dark mb-3">
            <span class="fw-semibold d-block"><?= e($r['title']) ?></span>
            <small class="text-muted"><?= e(fmt_date($r['created_at'])) ?></small>
          </a>
        <?php endforeach; ?>
      </div></div>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/layout/site_footer.php'; ?>
