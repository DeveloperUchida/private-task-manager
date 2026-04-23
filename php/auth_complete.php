<?php
session_start();
$_SESSION['user_id'] = $_GET['user_id'];
$_SESSION['email'] = $_GET['email'];
$_SESSION['auth_type'] = 'google';
header('Location: /tasks.php');
exit;