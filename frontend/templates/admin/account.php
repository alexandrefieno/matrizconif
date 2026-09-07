<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Minha conta — Matriz CONIF</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="topbar"><div><strong>Matriz CONIF</strong><span>Minha conta</span></div><nav><a href="/admin">Painel</a><a href="/">Portal público</a></nav></header>
  <main class="narrow">
    <p class="eyebrow">SEGURANÇA</p><h1>Alterar credenciais</h1>
    <?php if ($updated): ?><div class="alert alert-success">Credenciais atualizadas.</div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="/admin/account" class="form-stack panel">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <label>Novo nome de usuário<input name="username" value="<?= htmlspecialchars($user['username']) ?>" required></label>
      <label>Senha atual<input type="password" name="current_password" autocomplete="current-password" required></label>
      <label>Nova senha <small>(deixe em branco para manter)</small><input type="password" name="new_password" autocomplete="new-password"></label>
      <label>Confirmar nova senha<input type="password" name="new_password_confirmation" autocomplete="new-password"></label>
      <button type="submit">Salvar alterações</button>
    </form>
  </main>
</body>
</html>
