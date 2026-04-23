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

$stmt = $pdo->query('SELECT id, username FROM users WHERE is_admin = 0 ORDER BY username');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = $_POST['user_id'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($targetId)) {
        $error = 'ユーザーを選択してください';
    } elseif (strlen($newPassword) < 8) {
        $error = 'パスワードは8文字以上にしてください';
    } else {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND is_admin = 0');
        $stmt->execute([$hash, $targetId]);
        $success = 'パスワードをリセットしました';
    }
}

$page_title = 'パスワードリセット';
$current_page = 'admin_reset_password.php';
require __DIR__ . '/assets/views/admin_reset_password.html';