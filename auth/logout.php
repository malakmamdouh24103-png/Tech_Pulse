<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/functions.php';
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
