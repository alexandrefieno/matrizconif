<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Importacoes - Matriz CONIF</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="topbar">
    <div><strong>Matriz CONIF</strong><span>Importacoes</span></div>
    <nav>
      <a href="/admin">Painel</a>
      <a href="/">Portal publico</a>
      <a href="/admin/account">Minha conta</a>
      <form method="post" action="/admin/logout" class="inline-form"><input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>"><button class="link-button">Sair</button></form>
    </nav>
  </header>
  <main>
    <p class="eyebrow">AREA ADMINISTRATIVA</p>
    <h1>Importacao controlada de planilhas</h1>
    <p class="lead">Envie uma planilha por vez. O sistema salva o lote, le a primeira aba, identifica cabecalhos, registra as linhas brutas e apresenta um resumo para conferencia.</p>

    <?php if ($errors): ?>
      <div class="alert alert-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <?php if (!$periods): ?>
      <div class="alert alert-warning"><strong>Nenhum periodo cadastrado.</strong> Cadastre o ano-base antes de importar planilhas. A tela de cadastro sera implementada na proxima etapa.</div>
    <?php endif; ?>

    <section class="panel-wide">
      <h2>Novo upload</h2>
      <form method="post" action="/admin/imports" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <label>Periodo / ano-base
          <select name="base_period_id" required <?= !$periods ? 'disabled' : '' ?>>
            <option value="">Selecione</option>
            <?php foreach ($periods as $period): ?>
              <option value="<?= (int) $period['id'] ?>"><?= htmlspecialchars($period['title']) ?> - base <?= (int) $period['base_year'] ?> / orcamento <?= (int) $period['budget_year'] ?> (<?= htmlspecialchars($period['status']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Tipo de importacao
          <select name="import_type" required <?= !$periods ? 'disabled' : '' ?>>
            <option value="">Selecione</option>
            <option value="pnp_cycles">PNP - ciclos/matriculas</option>
            <option value="pnp_income">PNP - renda</option>
            <option value="institution_indicators">Indicadores institucionais</option>
            <option value="campus_parameters">Parametrizacao por campus</option>
            <option value="budget_envelopes">Envelopes orcamentarios</option>
          </select>
        </label>
        <label>Fonte da planilha
          <input name="source_name" placeholder="Ex.: PNP, CONIF, Reitoria, levantamento proprio" required <?= !$periods ? 'disabled' : '' ?>>
        </label>
        <label>URL da fonte <small>(opcional)</small>
          <input name="source_url" placeholder="https://..." <?= !$periods ? 'disabled' : '' ?>>
        </label>
        <label>Data de referencia <small>(opcional)</small>
          <input type="date" name="reference_date" <?= !$periods ? 'disabled' : '' ?>>
        </label>
        <label>Planilha
          <input type="file" name="spreadsheet" accept=".xlsx,.xls,.csv,.ods" required <?= !$periods ? 'disabled' : '' ?>>
        </label>
        <div class="form-actions"><button type="submit" <?= !$periods ? 'disabled' : '' ?>>Enviar e resumir</button></div>
      </form>
    </section>

    <?php if ($lastImport): ?>
      <section class="panel-wide result-panel">
        <p class="eyebrow">UPLOAD REGISTRADO</p>
        <h2><?= htmlspecialchars($lastImport['original_filename']) ?></h2>
        <div class="summary-grid compact">
          <article class="metric-card"><span>Lote</span><strong>#<?= (int) $lastImport['batch_id'] ?></strong><small><?= htmlspecialchars($lastImport['import_type']) ?></small></article>
          <article class="metric-card"><span>Aba lida</span><strong><?= htmlspecialchars($lastImport['selected_sheet']) ?></strong><small><?= count($lastImport['sheet_names']) ?> aba(s) no arquivo</small></article>
          <article class="metric-card"><span>Linhas</span><strong><?= number_format((int) $lastImport['row_count'], 0, ',', '.') ?></strong><small><?= (int) $lastImport['column_count'] ?> coluna(s)</small></article>
        </div>
        <h3>Cabecalhos identificados</h3>
        <div class="field-grid"><?php foreach ($lastImport['headers'] as $header): ?><span><?= htmlspecialchars((string) $header) ?></span><?php endforeach; ?></div>
        <h3>Amostra das primeiras linhas</h3>
        <div class="table-scroll"><table><thead><tr><?php foreach ($lastImport['headers'] as $header): ?><th><?= htmlspecialchars((string) $header) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($lastImport['preview_rows'] as $row): ?><tr><?php foreach ($lastImport['headers'] as $header): ?><td><?= htmlspecialchars((string) ($row[$header] ?? '')) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
        <p class="result-action"><a class="button-link" href="/admin/imports/<?= (int) $lastImport['batch_id'] ?>">Mapear e conferir este lote</a></p>
      </section>
    <?php endif; ?>

    <section class="panel-wide">
      <h2>Ultimos lotes importados</h2>
      <?php if (!$imports): ?>
        <p class="muted">Nenhum lote importado ainda.</p>
      <?php else: ?>
        <div class="table-scroll"><table><thead><tr><th>Lote</th><th>Periodo</th><th>Tipo</th><th>Arquivo</th><th>Linhas</th><th>Status</th><th>Fonte</th><th>Data</th><th></th></tr></thead><tbody><?php foreach ($imports as $import): ?><tr><td>#<?= (int) $import['id'] ?></td><td><?= (int) $import['base_year'] ?> -> <?= (int) $import['budget_year'] ?></td><td><?= htmlspecialchars($import['import_type']) ?></td><td><?= htmlspecialchars($import['original_filename']) ?></td><td><?= number_format((int) $import['row_count'], 0, ',', '.') ?></td><td><span class="status status-<?= htmlspecialchars($import['status']) ?>"><?= htmlspecialchars($import['status']) ?></span></td><td><?= htmlspecialchars($import['source_name']) ?></td><td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $import['uploaded_at']))) ?></td><td><a class="text-link" href="/admin/imports/<?= (int) $import['id'] ?>">Conferir lote</a></td></tr><?php endforeach; ?></tbody></table></div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
