<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
require_login();

$icons        = category_icons();
$errors       = [];
$editCategory = null;
$form         = ['name' => '', 'description' => '', 'icon' => 'bi-cpu'];

// ADD / UPDATE / DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if (!csrf_check()) {
        $errors[] = 'Invalid form token. Please try again.';

    } elseif ($action === 'delete') {
        $id    = (int) ($_POST['id'] ?? 0);
        $check = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE category_id = ?');
        $check->execute([$id]);
        if ((int) $check->fetchColumn() > 0) {
            flash('warning', 'Cannot delete a category that still has posts.');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('success', 'Category deleted successfully!');
        }
        header('Location: categories.php');
        exit;

    } elseif ($action === 'add' || $action === 'update') {
        $id   = $action === 'update' ? (int) ($_POST['id'] ?? 0) : 0;
        $form = [
            'name'        => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'icon'        => (string) ($_POST['icon'] ?? ''),
        ];

        if (mb_strlen($form['name']) < 2 || mb_strlen($form['name']) > 80) {
            $errors[] = 'Category name must be between 2 and 80 characters.';
        }
        if (mb_strlen($form['description']) > 255) {
            $errors[] = 'Description must be 255 characters or less.';
        }
        if (!isset($icons[$form['icon']])) {
            $errors[] = 'Please choose a valid icon.';
        }
        if (!$errors) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = ? AND id <> ?');
            $check->execute([$form['name'], $id]);
            if ((int) $check->fetchColumn() > 0) {
                $errors[] = 'This category already exists.';
            }
        }

        if (!$errors) {
            if ($action === 'add') {
                $pdo->prepare('INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)')
                    ->execute([$form['name'], $form['description'], $form['icon']]);
                flash('success', 'Category added successfully!');
            } else {
                $pdo->prepare('UPDATE categories SET name = ?, description = ?, icon = ? WHERE id = ?')
                    ->execute([$form['name'], $form['description'], $form['icon'], $id]);
                flash('success', 'Category updated successfully!');
            }
            header('Location: categories.php');
            exit;
        }
        if ($action === 'update') {
            $editCategory = ['id' => $id] + $form;
        }
    }
}

// EDIT MODE: load the category into the form
if (isset($_GET['edit_id']) && !$editCategory) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_id']]);
    $editCategory = $stmt->fetch() ?: null;
    if (!$editCategory) {
        flash('danger', 'Category not found!');
        header('Location: categories.php');
        exit;
    }
}
$values = $editCategory ?? $form;

$categories = $pdo->query(
    'SELECT c.*, COUNT(p.id) AS total FROM categories c
       LEFT JOIN posts p ON p.category_id = c.id GROUP BY c.id ORDER BY c.created_at DESC, c.id DESC'
)->fetchAll();

$pageTitle = 'Categories';
$active    = 'categories';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="py-4">

        <div class="d-flex justify-content-between flex-wrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="bi bi-tags"></i> Categories Management</h1>
        </div>

        <?php show_flash(); ?>
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div>
        <?php endforeach; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card side-card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <?= $editCategory ? '<i class="bi bi-pencil-square"></i> Edit Category' : '<i class="bi bi-plus-circle"></i> Add New Category' ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="<?= $editCategory ? 'update' : 'add' ?>">
                            <?php if ($editCategory): ?>
                                <input type="hidden" name="id" value="<?= (int) $editCategory['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="categoryName" class="form-label">Category Name *</label>
                                <input type="text" class="form-control" id="categoryName" name="name" maxlength="80"
                                       value="<?= e($values['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="categoryDescription" class="form-label">Description</label>
                                <input type="text" class="form-control" id="categoryDescription" name="description" maxlength="255"
                                       value="<?= e($values['description'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label for="categoryIcon" class="form-label">Icon</label>
                                <select class="form-select" id="categoryIcon" name="icon">
                                    <?php foreach ($icons as $cls => $label): ?>
                                        <option value="<?= e($cls) ?>" <?= $values['icon'] === $cls ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if ($editCategory): ?>
                                <button type="submit" class="btn btn-success w-100 mb-2"><i class="bi bi-check-circle"></i> Update</button>
                                <a href="categories.php" class="btn btn-outline-secondary w-100">Cancel</a>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> Add</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card side-card">
                    <div class="card-header bg-white"><h5 class="card-title mb-0">All Categories</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead>
                                    <tr><th>ID</th><th>Name</th><th>Description</th><th>Posts</th><th>Created At</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?= (int) $category['id'] ?></td>
                                        <td><i class="bi <?= e($category['icon']) ?> text-primary"></i> <?= e($category['name']) ?></td>
                                        <td><?= e($category['description'] ?? '') ?></td>
                                        <td><span class="badge text-bg-primary"><?= (int) $category['total'] ?></span></td>
                                        <td><?= e(fmt_date($category['created_at'])) ?></td>
                                        <td class="text-nowrap">
                                            <a href="categories.php?edit_id=<?= (int) $category['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                                                <button class="btn btn-sm btn-danger" <?= (int) $category['total'] > 0 ? 'disabled title="This category has posts"' : '' ?>>
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$categories): ?>
                                    <tr><td colspan="6" class="text-center">No categories found.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
