<?php
require_once __DIR__ . '/functions/const.php';
require_once __DIR__ . '/functions/message.php';
require_once __DIR__ . '/functions/helpers.php';

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ../views/dashboard.php');
    exit;
}

require_once dirname(__DIR__) . '/index.php';
require_once __DIR__ . '/functions/queries.php';

$username = '';
$password = '';
$usernameError = false;
$passwordError = false;

$requestMethod = 'GET';
if (isset($_SERVER['REQUEST_METHOD'])) {
    $requestMethod = $_SERVER['REQUEST_METHOD'];
}

if ($requestMethod === 'POST') {

    if (isset($_POST['action']) && $_POST['action'] === 'clear') {
        header('Location: ../views/login.php', true, 303);
        exit;
    }

    if (isset($_POST['username']) && is_string($_POST['username'])) {
        $username = trim($_POST['username']);
    }

    if (isset($_POST['password']) && is_string($_POST['password'])) {
        $password = $_POST['password'];
    }

    $usernameError = $username === '';
    $passwordError = $password === '';

    if (!$usernameError && !$passwordError) {
        $user = findUserForLogin($db, $username);

        if (!$user || (int) $user['status'] !== USER_STATUS_ACTIVE) {
            $usernameError = true;
        } elseif (!hash_equals($user['password_hash'], md5($password))) {
            $passwordError = true;
        } else {

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            header('Location: ../views/dashboard.php', true, 303);
            exit;
        }
    }
}

$showLoginError = $usernameError || $passwordError;
