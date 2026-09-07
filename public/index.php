<?php
declare(strict_types=1);

use MatrizConif\Security\Auth;
use MatrizConif\Security\Csrf;

$database = require dirname(__DIR__) . '/config/bootstrap.php';
$auth = new Auth($database);
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');
if ($path === '//') {
    $path = '/';
}

$redirect = static function (string $location): never {
    header('Location: ' . $location, true, 303);
    exit;
};

$render = static function (string $template, array $data = []): never {
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/frontend/templates/' . $template . '.php';
    exit;
};

if ($path === '/' && $method === 'GET') {
    $render('home');
}

if ($path === '/admin/login') {
    if ($auth->user()) {
        $redirect('/admin');
    }

    $error = null;
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            http_response_code(419);
            $error = 'A sessão do formulário expirou. Tente novamente.';
        } elseif ($auth->attempt(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) {
            $redirect('/admin');
        } else {
            $error = 'Usuário ou senha inválidos.';
        }
    }
    $render('admin/login', ['error' => $error, 'csrfToken' => Csrf::token()]);
}

if ($path === '/admin/logout' && $method === 'POST') {
    if (!Csrf::validate($_POST['_token'] ?? null)) {
        http_response_code(419);
        exit('Sessão expirada.');
    }
    $auth->logout();
    $redirect('/admin/login');
}

$user = $auth->user();
if (str_starts_with($path, '/admin') && !$user) {
    $redirect('/admin/login');
}

if ($path === '/admin' && $method === 'GET') {
    $render('admin/dashboard', ['user' => $user, 'csrfToken' => Csrf::token()]);
}

if ($path === '/admin/account') {
    $errors = [];
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            http_response_code(419);
            $errors[] = 'A sessão do formulário expirou. Tente novamente.';
        } else {
            $newPassword = trim((string) ($_POST['new_password'] ?? ''));
            $confirmation = (string) ($_POST['new_password_confirmation'] ?? '');
            if ($newPassword !== '' && $newPassword !== $confirmation) {
                $errors[] = 'A confirmação da nova senha não confere.';
            } else {
                $errors = $auth->changeCredentials(
                    (int) $user['id'],
                    (string) ($_POST['current_password'] ?? ''),
                    trim((string) ($_POST['username'] ?? '')),
                    $newPassword === '' ? null : $newPassword
                );
            }
            if (!$errors) {
                $redirect('/admin/account?updated=1');
            }
        }
    }
    $user = $auth->user();
    $render('admin/account', [
        'user' => $user,
        'errors' => $errors,
        'updated' => isset($_GET['updated']),
        'csrfToken' => Csrf::token(),
    ]);
}

http_response_code(404);
$render('errors/404');
