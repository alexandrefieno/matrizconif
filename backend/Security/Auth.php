<?php
declare(strict_types=1);

namespace MatrizConif\Security;

use PDO;

final class Auth
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function attempt(string $username, string $password): bool
    {
        $statement = $this->database->prepare(
            'SELECT id, username, name, email, password_hash, role, active, must_change_password
             FROM users WHERE username = :username LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        if (!$user || !(bool) $user['active'] || !password_verify($password, $user['password_hash'])) {
            $this->audit(null, 'auth.login_failed', ['username' => $username]);
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['auth_user_id'] = (int) $user['id'];
        Csrf::rotate();
        $this->audit((int) $user['id'], 'auth.login', null);

        return true;
    }

    public function user(): ?array
    {
        $id = $_SESSION['auth_user_id'] ?? null;
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return null;
        }

        $statement = $this->database->prepare(
            'SELECT id, username, name, email, role, active, must_change_password
             FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => (int) $id]);
        $user = $statement->fetch();

        if (!$user || !(bool) $user['active']) {
            unset($_SESSION['auth_user_id']);
            return null;
        }

        return $user;
    }

    public function logout(): void
    {
        $user = $this->user();
        if ($user) {
            $this->audit((int) $user['id'], 'auth.logout', null);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parameters['path'], $parameters['domain'], $parameters['secure'], $parameters['httponly']);
        }
        session_destroy();
    }

    public function changeCredentials(int $userId, string $currentPassword, string $username, ?string $newPassword): array
    {
        $statement = $this->database->prepare(
            'SELECT id, username, password_hash FROM users WHERE id = :id AND active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return ['A senha atual não confere.'];
        }

        $errors = [];
        if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
            $errors[] = 'O usuário deve ter de 3 a 60 caracteres: letras, números, ponto, hífen ou sublinhado.';
        }
        if ($newPassword !== null && mb_strlen($newPassword) < 12) {
            $errors[] = 'A nova senha deve ter pelo menos 12 caracteres.';
        }
        if ($errors) {
            return $errors;
        }

        $duplicate = $this->database->prepare('SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1');
        $duplicate->execute(['username' => $username, 'id' => $userId]);
        if ($duplicate->fetch()) {
            return ['Esse nome de usuário já está em uso.'];
        }

        $sql = 'UPDATE users SET username = :username, must_change_password = 0';
        $parameters = ['username' => $username, 'id' => $userId];
        if ($newPassword !== null) {
            $sql .= ', password_hash = :password_hash';
            $parameters['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';

        $update = $this->database->prepare($sql);
        $update->execute($parameters);
        $this->audit($userId, 'auth.credentials_changed', [
            'previous_username' => $user['username'],
            'new_username' => $username,
            'password_changed' => $newPassword !== null,
        ]);
        Csrf::rotate();

        return [];
    }

    private function audit(?int $userId, string $action, ?array $data): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, after_data, ip_address)
             VALUES (:user_id, :action, :entity_type, :entity_id, :after_data, INET6_ATON(:ip_address))'
        );
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => 'authentication',
            'entity_id' => $userId,
            'after_data' => $data === null ? null : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    }
}
