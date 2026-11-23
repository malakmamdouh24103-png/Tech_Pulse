<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
require_login();

$posts       = $pdo->query('SELECT id, title FROM posts ORDER BY title')->fetchAll();
$postIds     = array_map('intval', array_column($posts, 'id'));
$errors      = [];
$editComment = null;
$form        = ['author_name' => (string) ($_SESSION['username'] ?? 'Admin'), 'comment' => '', 'post_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if (!csrf_check()) {
        $errors[] = 'Invalid form token. Please try again.';

    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash('success', 'Comment deleted successfully!');
        header('Location: comments.php');
        exit;

    } elseif ($action === 'add' || $action === 'update') {
        $id   = $action === 'update' ? (int) ($_POST['id'] ?? 0) : 0;
        $form = [
            'author_name' => trim((string) ($_POST['author_name'] ?? '')),
            'comment'     => trim((string) ($_POST['comment'] ?? '')),
            'post_id'     => (string) ($_POST['post_id'] ?? ''),
        ];

        if ($form['author_name'] === '' || mb_strlen($form['author_name']) > 60) {
            $errors[] = 'Name is required (max 60 characters).';
        }
        if (mb_strlen($form['comment']) < 3 || mb_strlen($form['comment']) > 500) {
            $errors[] = 'Comment must be between 3 and 500 characters.';
        }
        if (!in_array((int) $form['post_id'], $postIds, true)) {
            $errors[] = 'Selected post does not exist!';
        }

        if (!$errors) {
            if ($action === 'add') {
                $pdo->prepare('INSERT INTO comments (post_id, author_name, comment) VALUES (?, ?, ?)')
                    ->execute([(int) $form['post_id'], $form['author_name'], $form['comment']]);
                flash('success', 'Comment added successfully!');
            } else {
                $pdo->prepare('UPDATE comments SET post_id = ?, author_name = ?, comment = ? WHERE id = ?')
                    ->execute([(int) $form['post_id'], $form['author_name'], $form['comment'], $id]);
                flash('success', 'Comment updated successfully!');
            }
            header('Location: comments.php');
            exit;
        }
        if ($action === 'update') {
            $editComment = ['id' => $id] + $form;
        }
    }
}

if (isset($_GET['edit_id']) && !$editComment) {
    $stmt = $pdo->prepare('SELECT * FROM comments WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_id']]);
    $editComment = $stmt->fetch() ?: null;
    if (!$editComment) {
        flash('danger', 'Comment not found!');
        header('Location: comments.php');
        exit;
    }
}
$values = $editComment ?? $form;

$comments = $pdo->query(
    'SELECT m.*, p.title FROM comments m INNER JOIN posts p ON p.id = m.post_id ORDER BY m.id DESC'
)->fetchAll();

$pageTitle = 'Comments';
$active    = 'comments';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="py-4">

        <div class="d-flex justify-content-between flex-wrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="bi bi-chat-dots"></i> Comments Management</h1>
        </div>

        <?php show_flash(); ?>
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div>
        <?php endforeach; ?>

        <div class="card side-card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><?= $editComment ? 'Edit Comment' : 'Add New Comment' ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editComment ? 'update' : 'add' ?>">
                    <?php if ($editComment): ?><input type="hidden" name="id" value="<?= (int) $editComment['id'] ?>"><?php endif; ?>

                    <div class="col-md-2">
                        <input type="text" name="author_name" class="form-control" placeholder="Name" maxlength="60"
                               value="<?= e($values['author_name']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="comment" class="form-control" placeholder="Comment" maxlength="500"
                               value="<?= e($values['comment']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <select name="post_id" class="form-select" required>
                            <option value="">Choose Post</option>
                            <?php foreach ($posts as $post): ?>
                                <option value="<?= (int) $post['id'] ?>" <?= (string) $post['id'] === (string) $values['post_id'] ? 'selected' : '' ?>>
                                    <?= e($post['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-<?= $editComment ? 'success' : 'primary' ?> flex-grow-1">
                            <i class="bi bi-<?= $editComment ? 'check-circle' : 'plus-circle' ?>"></i> <?= $editComment ? 'Update' : 'Add' ?>
                        </button>
                        <?php if ($editComment): ?><a href="comments.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card side-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead><tr><th>ID</th><th>Author</th><th>Comment</th><th>Post</th><th>Created At</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($comments as $row): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <td><?= e($row['author_name']) ?></td>
                                <td><?= e($row['comment']) ?></td>
                                <td><?= e($row['title']) ?></td>
                                <td><?= e(fmt_date($row['created_at'])) ?></td>
                                <td class="text-nowrap">
                                    <a href="comments.php?edit_id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this comment?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$comments): ?>
                            <tr><td colspan="6" class="text-center">No Comments Found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
