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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['new_username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

    if (empty($newUsername) || empty($newPassword)) {
        $error = 'ユーザー名とパスワードを入力してください';
    } elseif (strlen($newPassword) < 8) {
        $error = 'パスワードは8文字以上にしてください';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$newUsername]);
        if ($stmt->fetch()) {
            $error = 'そのユーザー名はすでに使われています';
        } else {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)');
            $stmt->execute([$newUsername, $hash, $isAdmin]);
            $success = 'ユーザー「' . htmlspecialchars($newUsername) . '」を登録しました';
        }
    }
}

$page_title = 'ユーザー登録';
$current_page = 'admin_add_user.php';
require __DIR__ . '/assets/views/admin_add_user.html';