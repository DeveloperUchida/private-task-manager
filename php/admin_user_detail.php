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

// ユーザー情報取得
$stmt = $pdo->prepare('SELECT id, username, email, google_id, is_admin, created_at FROM users WHERE id = ?');
$stmt->execute([$target_id]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$targetUser) {
    header('Location: /admin.php');
    exit;
}

// タスク統計取得
$stmt = $pdo->prepare('SELECT status, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY status');
$stmt->execute([$target_id]);
$taskStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = ['todo' => 0, 'in_progress' => 0, 'done' => 0];
foreach ($taskStats as $s) {
    $stats[$s['status']] = $s['count'];
}
$totalTasks = array_sum($stats);

// 最近のタスク取得
$stmt = $pdo->prepare('SELECT title, status, created_at FROM tasks WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$target_id]);
$recentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'ユーザー詳細';
$current_page = 'admin.php';
require __DIR__ . '/assets/views/admin_user_detail.html';
