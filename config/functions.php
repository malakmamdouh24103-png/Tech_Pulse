<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// BASE_URL = web path of the project folder (works with any folder name).
$__doc  = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
$__root = realpath(__DIR__ . '/..');
$__base = '';
if ($__doc && $__root) {
    $__doc  = str_replace('\\', '/', $__doc);
    $__root = str_replace('\\', '/', $__root);
    if (stripos($__root, $__doc) === 0) {
        $__base = rtrim(substr($__root, strlen($__doc)), '/');
    }
}
define('BASE_URL', $__base);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

/** Escape output (prevents XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** CSRF protection. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/** One-time flash messages. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [$type, $message];
}

function show_flash(): void
{
    if (!empty($_SESSION['flash'])) {
        [$type, $message] = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show" role="alert">'
           . e($message)
           . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}

/** Admin protection. */
function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function fmt_date(string $date): string
{
    return date('M j, Y', strtotime($date));
}

function read_time(string $text): int
{
    return max(1, (int) ceil(str_word_count($text) / 200));
}

function excerpt(string $text, int $len = 110): string
{
    return mb_strimwidth(trim((string) preg_replace('/\s+/', ' ', $text)), 0, $len, '...');
}

/** Rebuild the current query string with some values changed (for pagination/filters). */
function url_with(array $params): string
{
    $q = array_merge($_GET, $params);
    $q = array_filter($q, static fn($v) => $v !== null && $v !== '' && $v !== 0 && $v !== '0');
    return '?' . http_build_query($q);
}

/** Icons the admin can choose for a category (Bootstrap Icons). */
function category_icons(): array
{
    return [
        'bi-cpu' => 'Chip', 'bi-robot' => 'Robot / AI', 'bi-code-slash' => 'Code',
        'bi-shield-lock' => 'Security', 'bi-phone' => 'Phone', 'bi-cloud' => 'Cloud',
        'bi-controller' => 'Gaming', 'bi-rocket-takeoff' => 'Startups',
        'bi-wifi' => 'Networks', 'bi-database' => 'Data',
    ];
}

/** Safe image upload. Returns [filename|null, error|null]. No file chosen => [null, null]. */
function handle_image_upload(string $field = 'image'): array
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Image upload failed.'];
    }
    if ($f['size'] > 3 * 1024 * 1024) {
        return [null, 'Image must be smaller than 3 MB.'];
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($allowed[$mime]) || getimagesize($f['tmp_name']) === false) {
        return [null, 'Only JPG, PNG or WEBP images are allowed.'];
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        return [null, 'Cannot create the uploads folder.'];
    }
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) {
        return [null, 'Image upload failed.'];
    }
    return [$name, null];
}

function delete_image(?string $name): void
{
    if ($name) {
        $path = UPLOAD_DIR . basename($name);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
