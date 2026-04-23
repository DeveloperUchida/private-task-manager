<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /admin_login.php');
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('DB接続エラー');
}

$stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$currentUser || !$currentUser['is_admin']) {
    http_response_code(403);
    die('アクセス権限がありません');
}

$target_id = $_GET['id'] ?? '';
if (empty($target_id)) {
    header('Location: /admin.php');
    exit;
}

// google_idも含めて取得
$stmt = $pdo->prepare('SELECT id, username, email, google_id, is_admin FROM users WHERE id = ?');
$stmt->execute([$target_id]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$targetUser) {
    header('Location: /admin.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';

    // 基本情報更新
    if ($action === 'update') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

        if (empty($newUsername)) {
            $error = 'ユーザー名を入力してください';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $stmt->execute([$newUsername, $target_id]);
            if ($stmt->fetch()) {
                $error = 'そのユーザー名はすでに使われています';
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, is_admin = ? WHERE id = ?');
                $stmt->execute([$newUsername, $newEmail ?: null, $isAdmin, $target_id]);
                $success = 'ユーザー情報を更新しました';
            }
        }
    }

    // Google連携解除
    if ($action === 'unlink_google') {
        if ($targetUser['username']) {
            $stmt = $pdo->prepare('UPDATE users SET google_id = NULL WHERE id = ?');
            $stmt->execute([$target_id]);
            $success = 'Google連携を解除しました';
        } else {
            $error = 'パスワード認証がない場合はGoogle連携を解除できません';
        }
    }

    // パスワード設定（Google専用ユーザーにPWを追加）
    if ($action === 'set_password') {
        $newPassword = $_POST['new_password'] ?? '';
        $newUsername = trim($_POST['new_username'] ?? '');

        if (empty($newUsername)) {
            $error = 'ユーザー名を入力してください';
        } elseif (strlen($newPassword) < 8) {
            $error = 'パスワードは8文字以上にしてください';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $stmt->execute([$newUsername, $target_id]);
            if ($stmt->fetch()) {
                $error = 'そのユーザー名はすでに使われています';
            } else {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('UPDATE users SET username = ?, password_hash = ? WHERE id = ?');
                $stmt->execute([$newUsername, $hash, $target_id]);
                $success = 'パスワード認証を設定しました';
            }
        }
    }

    // パスワード認証解除
    if ($action === 'unlink_password') {
        if ($targetUser['google_id']) {
            $stmt = $pdo->prepare('UPDATE users SET username = NULL, password_hash = NULL WHERE id = ?');
            $stmt->execute([$target_id]);
            $success = 'パスワード認証を解除しました';
        } else {
            $error = 'Google連携がない場合はパスワード認証を解除できません';
        }
    }

    // データ再取得
    $stmt = $pdo->prepare('SELECT id, username, email, google_id, is_admin FROM users WHERE id = ?');
    $stmt->execute([$target_id]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

$page_title = 'ユーザー編集';
$current_page = 'admin.php';
require __DIR__ . '/assets/views/admin_edit_user.html';
