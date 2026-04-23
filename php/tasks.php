<?php
session_start();

// if (!isset($_SESSION['user_id'])) {
//     header('Location: /login.php');
//     exit;
// }

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

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare('INSERT INTO tasks (user_id, title, description) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, $_POST['title'], $_POST['description'] ?? '']);
    }
    if ($_POST['action'] === 'update_status') {
        $stmt = $pdo->prepare('UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$_POST['status'], $_POST['task_id'], $user_id]);
    }
    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$_POST['task_id'], $user_id]);
    }
    header('Location: /tasks.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$todo     = array_filter($tasks, fn($t) => $t['status'] === 'todo');
$progress = array_filter($tasks, fn($t) => $t['status'] === 'in_progress');
$done     = array_filter($tasks, fn($t) => $t['status'] === 'done');

require __DIR__ . '/assets/views/tasks.html';