<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
require_login();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$errors     = [];
$old        = ['title' => '', 'content' => '', 'category_id' => '', 'status' => 'published'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = 'Invalid form token. Please try again.';
    } else {
        foreach ($old as $k => $v) {
            $old[$k] = trim((string) ($_POST[$k] ?? ''));
        }
        if (mb_strlen($old['title']) < 5 || mb_strlen($old['title']) > 200) {
            $errors[] = 'Title must be between 5 and 200 characters.';
        }
        if (mb_strlen($old['content']) < 30) {
            $errors[] = 'Content must be at least 30 characters.';
        }
        if (!in_array((int) $old['category_id'], array_map('intval', array_column($categories, 'id')), true)) {
            $errors[] = 'Selected category does not exist!';
        }
        if (!in_array($old['status'], ['published', 'draft'], true)) {
            $errors[] = 'Invalid status.';
        }

        $image = null;
        if (!$errors) {
            [$image, $uploadError] = handle_image_upload('image');
            if ($uploadError) {
                $errors[] = $uploadError;
            }
        }

        if (!$errors) {
            try {
                $pdo->prepare('INSERT INTO posts (category_id, title, content, image, status) VALUES (?, ?, ?, ?, ?)')
                    ->execute([(int) $old['category_id'], $old['title'], $old['content'], $image, $old['status']]);
                flash('success', 'Post added successfully!');
                header('Location: posts.php');
                exit;
            } catch (PDOException $e) {
                delete_image($image);
                $errors[] = 'Could not save the post. Please try again.';
            }
        }
    }
}

$pageTitle = 'Add Post';
$active    = 'addpost';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="py-4">

        <div class="d-flex justify-content-between flex-wrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="bi bi-plus-circle"></i> Add New Post</h1>
        </div>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div>
        <?php endforeach; ?>

        <?php if (!$categories): ?>
            <div class="alert alert-warning">Please <a href="../category/categories.php">add a category</a> first.</div>
        <?php else: ?>
        <div class="card side-card"><div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" class="form-control" id="title" name="title" maxlength="200" value="<?= e($old['title']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="content">Content <small class="text-muted">(leave an empty line between paragraphs)</small></label>
                        <textarea class="form-control" id="content" name="content" rows="8" required><?= e($old['content']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="category_id">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Choose Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $old['category_id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="published" <?= $old['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $old['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="image">Image <small class="text-muted">(optional, JPG/PNG/WEBP, max 3 MB)</small></label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Post</button>
                    <a href="posts.php" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div></div>
        <?php endif; ?>

    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
