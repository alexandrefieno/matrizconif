<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> - Matriz CONIF</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="topbar">
    <div><strong>Matriz CONIF</strong><span><?= htmlspecialchars($pageTitle) ?></span></div>
    <nav>
      <a href="/admin">Painel</a>
      <a href="/">Portal publico</a>
      <a href="/admin/account">Minha conta</a>
      <form method="post" action="/admin/logout" class="inline-form"><input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>"><button class="link-button">Sair</button></form>
    </nav>
  </header>
  <main>
    <p class="eyebrow">AREA ADMINISTRATIVA</p>
    <h1><?= htmlspecialchars($heading) ?></h1>
    <p class="lead"><?= htmlspecialchars($description) ?></p>

    <section class="notice">
      <strong>Status da etapa</strong>
      <p><?= htmlspecialchars($statusText) ?></p>
    </section>

    <?php if (!empty($actions)): ?>
      <section class="panel-wide">
        <h2>Fluxo previsto</h2>
        <ol class="flow-list">
          <?php foreach ($actions as $action): ?>
            <li><?= htmlspecialchars($action) ?></li>
          <?php endforeach; ?>
        </ol>
      </section>
    <?php endif; ?>

    <?php if (!empty($fields)): ?>
      <section class="panel-wide">
        <h2>Campos que esta pagina devera controlar</h2>
        <div class="field-grid">
          <?php foreach ($fields as $field): ?>
            <span><?= htmlspecialchars($field) ?></span>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
