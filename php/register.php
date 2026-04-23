<?php
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'ユーザー名とパスワードを入力してください';
    } elseif (strlen($password) < 8) {
        $error = 'パスワードは8文字以上にしてください';
    } elseif ($password !== $password_confirm) {
        $error = 'パスワードが一致しません';
    } else {
        try {
            $pdo = new PDO(
                'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
                getenv('DB_USER'),
                getenv('DB_PASS'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'そのユーザー名はすでに使われています';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
                $stmt->execute([$username, $hash]);
                $success = '登録完了！ログインしてください';
            }
        } catch (PDOException $e) {
            $error = 'DB接続エラー';
        }
    }
}

require __DIR__ . '/assets/views/register.html';