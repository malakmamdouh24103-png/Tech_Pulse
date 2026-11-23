<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
require_login();

// STEP 1: get the post id (GET when opening the page, POST when saving)
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) {
    flash('danger', 'Post not found.');
    header('Location: posts.php');
    exit;
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$errors     = [];

// STEP 2: update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = 'Invalid form token. Please try again.';
    } else {
        $title       = trim((string) ($_POST['title'] ?? ''));
        $content     = trim((string) ($_POST['content'] ?? ''));
        $category_id = (int) ($_POST['category_id'] ?? 0);
        $status      = (string) ($_POST['status'] ?? '');

        if (mb_strlen($title) < 5 || mb_strlen($title) > 200) {
            $errors[] = 'Title must be between 5 and 200 characters.';
        }
        if (mb_strlen($content) < 30) {
            $errors[] = 'Content must be at least 30 characters.';
        }
        if (!in_array($category_id, array_map('intval', array_column($categories, 'id')), true)) {
            $errors[] = 'Selected category does not exist!';
        }
        if (!in_array($status, ['published', 'draft'], true)) {
            $errors[] = 'Invalid status.';
        }

        $newImage = null;
        if (!$errors) {
            [$newImage, $uploadError] = handle_image_upload('image');
            if ($uploadError) {
                $errors[] = $uploadError;
            }
        }

        if (!$errors) {
            try {
                $image = $newImage ?? $post['image'];
                $pdo->prepare('UPDATE posts SET title = ?, content = ?, image = ?, category_id = ?, status = ? WHERE id = ?')
                    ->execute([$title, $content, $image, $category_id, $status, $id]);
                if ($newImage !== null) {
                    delete_image($post['image']);   // remove the old image after a successful update
                }
                flash('success', 'Post updated successfully!');
                header('Location: posts.php');
                exit;
            } catch (PDOException $e) {
                delete_image($newImage);
                $errors[] = 'Could not update the post. Please try again.';
            }
        }

        // keep what the admin typed
        $post['title'] = $title;
        $post['content'] = $content;
        $post['category_id'] = $category_id;
        $post['status'] = $status;
    }
}

$pageTitle = 'Edit Post';
$active    = 'posts';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="py-4">

        <div class="d-flex justify-content-between flex-wrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="bi bi-pencil-square"></i> Edit Post</h1>
        </div>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div>
        <?php endforeach; ?>

        <div class="card side-card"><div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" class="form-control" id="title" name="title" maxlength="200" value="<?= e($post['title']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="content">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="8" required><?= e($post['content']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="category_id">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Choose Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= (int) $post['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="image">New Image <small class="text-muted">(leave empty to keep the current one)</small></label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/png,image/jpeg,image/webp">
                    </div>
                    <?php if (!empty($post['image'])): ?>
                    <div class="col-12">
                        <label class="form-label d-block">Current Image</label>
                        <img src="<?= e(UPLOAD_URL . $post['image']) ?>" width="120" height="120" style="object-fit:cover" class="img-thumbnail" alt="Current image">
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Update Post</button>
                    <a href="posts.php" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div></div>

    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
