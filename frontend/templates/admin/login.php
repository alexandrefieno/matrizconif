<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Acesso administrativo — Matriz CONIF</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-page">
  <main class="auth-card">
    <p class="eyebrow">ÁREA RESTRITA</p>
    <h1>Acesso administrativo</h1>
    <p>Entre para importar, conferir e publicar os dados da Matriz CONIF.</p>
    <?php if ($error): ?><div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" action="/admin/login" class="form-stack">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <label>Usuário<input name="username" autocomplete="username" required autofocus></label>
      <label>Senha<input type="password" name="password" autocomplete="current-password" required></label>
      <button type="submit">Entrar</button>
    </form>
    <a href="/">Voltar ao portal público</a>
  </main>
</body>
</html>
