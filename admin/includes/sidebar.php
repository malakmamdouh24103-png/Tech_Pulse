<?php $active = $active ?? ''; ?>
<nav class="col-md-3 col-lg-2 d-md-block bg-white sidebar border-end min-vh-100">
    <div class="position-sticky pt-3">

        <h6 class="px-3 mt-3 mb-1 text-muted text-uppercase small">Main Menu</h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/index.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
        </ul>

        <h6 class="px-3 mt-4 mb-1 text-muted text-uppercase small">Content</h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?= $active === 'posts' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/posts/posts.php">
                    <i class="bi bi-file-text"></i> Posts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active === 'addpost' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/posts/addpost.php">
                    <i class="bi bi-plus-circle"></i> Add Post
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active === 'categories' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/category/categories.php">
                    <i class="bi bi-tags"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active === 'comments' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/comments/comments.php">
                    <i class="bi bi-chat-dots"></i> Comments
                </a>
            </li>
        </ul>

        <h6 class="px-3 mt-4 mb-1 text-muted text-uppercase small">Other</h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/index.php" target="_blank">
                    <i class="bi bi-globe"></i> View Site
                </a>
            </li>
        </ul>

    </div>
</nav>
