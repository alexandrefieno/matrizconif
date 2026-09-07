<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Anos-base - Matriz CONIF</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="topbar">
    <div><strong>Matriz CONIF</strong><span>Anos-base</span></div>
    <nav>
      <a href="/admin">Painel</a>
      <a href="/admin/imports">Importacoes</a>
      <a href="/">Portal publico</a>
      <a href="/admin/account">Minha conta</a>
      <form method="post" action="/admin/logout" class="inline-form"><input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>"><button class="link-button">Sair</button></form>
    </nav>
  </header>
  <main>
    <p class="eyebrow">AREA ADMINISTRATIVA</p>
    <h1>Anos-base e periodos orcamentarios</h1>
    <p class="lead">Cadastre o ciclo de trabalho antes de importar planilhas. Exemplo: ano-base 2024 para validar o orcamento de 2026; ano-base 2025 para simular o orcamento de 2027.</p>

    <?php if ($created): ?><div class="alert alert-success">Periodo cadastrado com sucesso.</div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <section class="panel-wide">
      <h2>Novo periodo</h2>
      <form method="post" action="/admin/periods" class="form-grid">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <label>Ano-base dos dados
          <input type="number" name="base_year" min="2000" max="2100" placeholder="2024" required>
        </label>
        <label>Ano orcamentario
          <input type="number" name="budget_year" min="2000" max="2100" placeholder="2026" required>
        </label>
        <label>Titulo <small>(opcional)</small>
          <input name="title" placeholder="Base 2024 / Orcamento 2026">
        </label>
        <label class="full-span">Observacoes metodologicas <small>(opcional)</small>
          <textarea name="notes" rows="4" placeholder="Registre a origem dos dados, limites conhecidos ou decisao administrativa relevante."></textarea>
        </label>
        <div class="form-actions"><button type="submit">Cadastrar periodo</button></div>
      </form>
    </section>

    <section class="panel-wide">
      <h2>Periodos cadastrados</h2>
      <?php if (!$periods): ?>
        <p class="muted">Nenhum periodo cadastrado ainda.</p>
      <?php else: ?>
        <div class="table-scroll"><table><thead><tr><th>ID</th><th>Titulo</th><th>Ano-base</th><th>Orcamento</th><th>Status</th><th>Criado em</th></tr></thead><tbody><?php foreach ($periods as $period): ?><tr><td>#<?= (int) $period['id'] ?></td><td><?= htmlspecialchars($period['title']) ?></td><td><?= (int) $period['base_year'] ?></td><td><?= (int) $period['budget_year'] ?></td><td><?= htmlspecialchars($period['status']) ?></td><td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $period['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
