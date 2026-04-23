<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /admin_login.php'); exit; }

try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
        getenv('DB_USER'), getenv('DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) { die('DB接続エラー'); }

$stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$currentUser || !$currentUser['is_admin']) { http_response_code(403); die('アクセス権限がありません'); }

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    if ($_POST['user_id'] != $_SESSION['user_id']) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND is_admin = 0');
        $stmt->execute([$_POST['user_id']]);
        $success = 'ユーザーを削除しました';
    }
}

$stmt = $pdo->query('SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'ユーザー一覧';
$current_page = 'admin.php';
require __DIR__ . '/assets/views/admin.html';