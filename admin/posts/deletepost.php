<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
require_login();

// Delete only through POST + CSRF token (a link could be triggered by anyone)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash('danger', 'Invalid request.');
    header('Location: posts.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

try {
    $stmt = $pdo->prepare('SELECT image FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) {
        flash('warning', 'Post not found.');
    } else {
        // comments are removed automatically (ON DELETE CASCADE)
        $pdo->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        delete_image($post['image']);
        flash('success', 'Post deleted successfully!');
    }
} catch (PDOException $e) {
    flash('danger', 'Delete error. Please try again.');
}

header('Location: posts.php');
exit;
