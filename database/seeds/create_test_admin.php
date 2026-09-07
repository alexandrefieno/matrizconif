<?php
declare(strict_types=1);

$database = require dirname(__DIR__, 2) . '/config/bootstrap.php';

if (($_ENV['APP_ENV'] ?? 'production') !== 'local') {
    fwrite(STDERR, "Credenciais de teste só podem ser criadas com APP_ENV=local.\n");
    exit(1);
}

$existing = $database->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
if ($existing) {
    fwrite(STDOUT, "Já existe um administrador. Nenhuma credencial foi alterada.\n");
    exit(0);
}

$statement = $database->prepare(
    'INSERT INTO users (username, name, email, password_hash, role, active, must_change_password)
     VALUES (:username, :name, :email, :password_hash, :role, 1, 1)'
);
$statement->execute([
    'username' => 'admin',
    'name' => 'Administrador de teste',
    'email' => 'administrador@localhost',
    'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
    'role' => 'admin',
]);

fwrite(STDOUT, "Administrador de teste criado. Altere as credenciais antes da publicação.\n");
