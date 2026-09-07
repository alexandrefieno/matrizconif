<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Lote #<?= (int) $batch['id'] ?> - Matriz CONIF</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="topbar">
    <div><strong>Matriz CONIF</strong><span>Conferencia do lote #<?= (int) $batch['id'] ?></span></div>
    <nav>
      <a href="/admin">Painel</a>
      <a href="/admin/imports">Importacoes</a>
      <a href="/admin/account">Minha conta</a>
      <form method="post" action="/admin/logout" class="inline-form"><input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>"><button class="link-button">Sair</button></form>
    </nav>
  </header>
  <main>
    <p class="eyebrow">ETAPA ADMINISTRATIVA 3</p>
    <div class="title-row">
      <div>
        <h1 class="page-title">Conferencia do lote #<?= (int) $batch['id'] ?></h1>
        <p class="lead"><?= htmlspecialchars($batch['original_filename']) ?></p>
      </div>
      <span class="status status-<?= htmlspecialchars($batch['status']) ?> status-large"><?= htmlspecialchars($batch['status']) ?></span>
    </div>

    <?php if ($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if ($validated): ?>
      <div class="alert <?= $validationResult === 'validated' ? 'alert-success' : 'alert-warning' ?>">
        <?= $validationResult === 'validated' ? 'Validacao concluida. O lote esta apto para incorporacao.' : 'Conferencia concluida, mas ha pendencias a corrigir no mapeamento ou nos dados.' ?>
      </div>
    <?php endif; ?>
    <?php if ($rejected): ?><div class="alert alert-success">Lote rejeitado e preservado apenas para rastreabilidade.</div><?php endif; ?>
    <?php if ($promoted !== null): ?><div class="alert alert-success"><?= number_format($promoted, 0, ',', '.') ?> registro(s) incorporado(s) a base definitiva.</div><?php endif; ?>

    <section class="summary-grid compact summary-grid-4">
      <article class="metric-card"><span>Periodo</span><strong><?= (int) $batch['base_year'] ?> / <?= (int) $batch['budget_year'] ?></strong><small>ano-base / orcamento</small></article>
      <article class="metric-card"><span>Tipo</span><strong><?= htmlspecialchars($batch['import_type']) ?></strong><small><?= htmlspecialchars($batch['source_name']) ?></small></article>
      <article class="metric-card"><span>Conteudo</span><strong><?= number_format((int) $batch['row_count'], 0, ',', '.') ?></strong><small><?= (int) ($summary['column_count'] ?? 0) ?> coluna(s)</small></article>
      <article class="metric-card"><span>Envio</span><strong><?= htmlspecialchars(date('d/m/Y', strtotime((string) $batch['uploaded_at']))) ?></strong><small><?= htmlspecialchars($batch['uploaded_by_name']) ?></small></article>
    </section>

    <?php if ($batch['status'] === 'rejected'): ?>
      <div class="alert alert-error"><strong>Motivo da rejeicao:</strong> <?= htmlspecialchars((string) $batch['rejection_reason']) ?></div>
    <?php endif; ?>

    <?php if (isset($summary['valid_rows'])): ?>
      <section class="panel-wide">
        <h2>Resultado da ultima validacao</h2>
        <div class="summary-grid compact summary-grid-4">
          <article class="metric-card"><span>Validas</span><strong><?= number_format((int) $summary['valid_rows'], 0, ',', '.') ?></strong></article>
          <article class="metric-card"><span>Invalidas</span><strong><?= number_format((int) $summary['invalid_rows'], 0, ',', '.') ?></strong></article>
          <article class="metric-card"><span>Pouso Alegre</span><strong><?= number_format((int) $summary['pouso_alegre_rows'], 0, ',', '.') ?></strong><small>registros identificados</small></article>
          <article class="metric-card"><span>Unidades</span><strong><?= count($summary['units'] ?? []) ?></strong><small>unidades reconhecidas</small></article>
        </div>
        <?php if (!empty($summary['error_samples'])): ?>
          <h3>Amostra de pendencias</h3>
          <ul class="validation-list"><?php foreach ($summary['error_samples'] as $sample): ?><li><strong>Linha <?= (int) $sample['source_row'] ?>:</strong> <?= htmlspecialchars(implode(' ', $sample['errors'])) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if (!in_array($batch['status'], ['rejected', 'promoted'], true)): ?>
      <section class="panel-wide">
        <h2>1. Mapear e validar colunas</h2>
        <p class="muted">Associe cada campo do sistema a uma coluna do arquivo. Campos marcados com * sao obrigatorios; para a unidade, informe ao menos o codigo ou o nome. O arquivo bruto permanece inalterado.</p>
        <form method="post" action="/admin/imports/<?= (int) $batch['id'] ?>" class="form-grid mapping-grid">
          <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <input type="hidden" name="action" value="validate">
          <?php foreach ($fields as $key => $field): ?>
            <label><?= htmlspecialchars($field['label']) ?><?= $field['required'] ? ' *' : '' ?>
              <select name="mapping[<?= htmlspecialchars($key) ?>]" <?= $field['required'] ? 'required' : '' ?>>
                <option value="">Nao utilizar</option>
                <?php foreach ($headers as $header): ?><option value="<?= htmlspecialchars($header) ?>" <?= ($mapping[$key] ?? '') === $header ? 'selected' : '' ?>><?= htmlspecialchars($header) ?></option><?php endforeach; ?>
              </select>
              <small>Formato esperado: <?= htmlspecialchars($field['type']) ?></small>
            </label>
          <?php endforeach; ?>
          <div class="form-actions"><button type="submit">Executar validacao</button></div>
        </form>
      </section>
    <?php endif; ?>

    <section class="panel-wide">
      <h2>2. Conferir linhas</h2>
      <div class="table-scroll"><table><thead><tr><th>Linha</th><th>Status</th><th>Conteudo original</th><th>Pendencias</th></tr></thead><tbody>
      <?php foreach ($rows as $row): ?><tr><td><?= (int) $row['source_row'] ?></td><td><span class="status status-<?= htmlspecialchars($row['validation_status']) ?>"><?= htmlspecialchars($row['validation_status']) ?></span></td><td><dl class="payload-list"><?php foreach ($row['payload'] as $key => $value): ?><div><dt><?= htmlspecialchars((string) $key) ?></dt><dd><?= htmlspecialchars((string) $value) ?></dd></div><?php endforeach; ?></dl></td><td><?php if ($row['validation_errors']): ?><ul class="cell-errors"><?php foreach ($row['validation_errors'] as $error): ?><li><?= htmlspecialchars((string) $error) ?></li><?php endforeach; ?></ul><?php else: ?><span class="muted">Nenhuma registrada</span><?php endif; ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <?php if ($pageCount > 1): ?><nav class="pagination" aria-label="Paginacao"><?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">Anterior</a><?php endif; ?><span>Pagina <?= $page ?> de <?= $pageCount ?></span><?php if ($page < $pageCount): ?><a href="?page=<?= $page + 1 ?>">Proxima</a><?php endif; ?></nav><?php endif; ?>
    </section>

    <?php if ($batch['status'] === 'validated'): ?>
      <section class="panel-wide action-panel">
        <div><h2>3. Incorporar a base</h2><p>Esta acao grava os registros validados nas tabelas definitivas do periodo. Em caso de conflito, toda a operacao sera desfeita.</p></div>
        <form method="post" action="/admin/imports/<?= (int) $batch['id'] ?>" onsubmit="return confirm('Confirma a incorporacao deste lote a base definitiva?')"><input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="action" value="promote"><button type="submit">Incorporar lote</button></form>
      </section>
    <?php endif; ?>

    <?php if (!in_array($batch['status'], ['rejected', 'promoted'], true)): ?>
      <section class="panel-wide danger-panel">
        <h2>Rejeitar lote</h2>
        <form method="post" action="/admin/imports/<?= (int) $batch['id'] ?>" class="form-grid">
          <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="action" value="reject">
          <label class="full-span">Motivo da rejeicao<textarea name="rejection_reason" rows="3" required></textarea></label>
          <div class="form-actions"><button type="submit" class="button-danger">Rejeitar lote</button></div>
        </form>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
