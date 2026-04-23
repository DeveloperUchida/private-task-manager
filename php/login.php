<?php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'ユーザー名とパスワードを入力してください';
    } else {
        try {
            $pdo = new PDO(
                'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
                getenv('DB_USER'),
                getenv('DB_PASS'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['auth_type'] = 'password';
                header('Location: /tasks.php');
                exit;
            } else {
                $error = 'ユーザー名またはパスワードが違います';
            }
        } catch (PDOException $e) {
            $error = 'DB接続エラー';
        }
    }
}

require __DIR__ . '/assets/views/login.html';