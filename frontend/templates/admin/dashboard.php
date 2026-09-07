<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Administração — Matriz CONIF</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="topbar"><div><strong>Matriz CONIF</strong><span>Administração</span></div><nav><a href="/">Portal público</a><a href="/admin/account">Minha conta</a><form method="post" action="/admin/logout" class="inline-form"><input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>"><button class="link-button">Sair</button></form></nav></header>
  <main>
    <p class="eyebrow">PAINEL ADMINISTRATIVO</p>
    <h1>Olá, <?= htmlspecialchars($user['name']) ?>.</h1>
    <?php if ((bool) $user['must_change_password']): ?><div class="alert alert-warning"><strong>Credenciais provisórias.</strong> O acesso de teste ainda está ativo. <a href="/admin/account">Altere o usuário e a senha</a> antes de publicar o sistema.</div><?php endif; ?>
    <section class="notice"><strong>Próxima etapa</strong><p>O módulo de importação controlada será acrescentado aqui: uma planilha por vez, resumo, conferência e incorporação.</p></section>
  </main>
</body>
</html>
