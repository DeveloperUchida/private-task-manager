<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? '管理画面' ?> - TaskManager</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/admin_layout.css">
</head>

<body>
    <div class="layout">
        <!-- サイドメニュー -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                TaskManager
                <span class="admin-badge">Admin</span>
            </div>
            <nav class="sidebar-nav">
                <a href="/admin.php" class="nav-item <?= $current_page === 'admin.php' ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span>
                    ユーザー一覧
                </a>
                <a href="/admin_add_user.php" class="nav-item <?= $current_page === 'admin_add_user.php' ? 'active' : '' ?>">
                    <span class="nav-icon">➕</span>
                    ユーザー登録
                </a>
                <a href="/admin_reset_password.php" class="nav-item <?= $current_page === 'admin_reset_password.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🔑</span>
                    パスワードリセット
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="/tasks.php" class="nav-item">
                    <span class="nav-icon">←</span>
                    タスク画面へ
                </a>
                <a href="/admin_logout.php" class="nav-item logout">
                    <span class="nav-icon">🚪</span>
                    ログアウト
                </a>
            </div>
        </aside>

        <!-- メインコンテンツ -->
        <main class="content">
            <div class="content-header">
                <h1 class="page-title"><?= $page_title ?? '管理画面' ?></h1>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</body>

</html>