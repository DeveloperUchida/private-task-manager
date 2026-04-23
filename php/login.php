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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - TaskManager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f7f7f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 420px;
            padding: 0 24px;
        }

        .logo {
            font-family: 'DM Mono', monospace;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.15em;
            color: #999;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 48px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            border: 1px solid #ebebeb;
        }

        h1 {
            font-size: 22px;
            font-weight: 500;
            color: #111;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .subtitle {
            font-size: 14px;
            color: #999;
            margin-bottom: 32px;
        }

        .error {
            background: #fff5f5;
            border: 1px solid #fecaca;
            color: #dc2626;
            font-size: 13px;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .field {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #444;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            height: 44px;
            padding: 0 14px;
            border: 1.5px solid #e5e5e5;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            color: #111;
            background: #fafafa;
            outline: none;
            transition: border-color 0.15s, background 0.15s;
        }

        input:focus {
            border-color: #111;
            background: #fff;
        }

        .btn-login {
            width: 100%;
            height: 44px;
            background: #111;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 24px;
            transition: background 0.15s, transform 0.1s;
            letter-spacing: -0.01em;
        }

        .btn-login:hover { background: #333; }
        .btn-login:active { transform: scale(0.99); }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: #ccc;
            font-size: 12px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #ebebeb;
        }

        .btn-google {
            width: 100%;
            height: 44px;
            background: #fff;
            color: #333;
            border: 1.5px solid #e5e5e5;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: border-color 0.15s, background 0.15s;
            text-decoration: none;
        }

        .btn-google:hover {
            border-color: #bbb;
            background: #fafafa;
        }

        .google-icon {
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">TaskManager</div>
        <div class="card">
            <h1>おかえりなさい</h1>
            <p class="subtitle">アカウントにログインしてください</p>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/login.php">
                <div class="field">
                    <label for="username">ユーザー名</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="ユーザーネームを入力してください"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                    >
                </div>
                <div class="field">
                    <label for="password">パスワード</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="パスワードを入力してください"
                        autocomplete="current-password"
                    >
                </div>
                <button type="submit" class="btn-login">ログイン</button>
            </form>

            <div class="divider">または</div>

            <a href="/auth/google/login" class="btn-google">
                <svg class="google-icon" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Googleでログイン
            </a>
        </div>
    </div>
</body>
</html>