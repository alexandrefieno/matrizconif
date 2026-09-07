<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Administracao - Matriz CONIF</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="topbar">
    <div><strong>Matriz CONIF</strong><span>Administracao</span></div>
    <nav>
      <a href="/admin/periods">Anos-base</a>
      <a href="/admin/imports">Importacoes</a>
      <a href="/">Portal publico</a>
      <a href="/admin/account">Minha conta</a>
      <form method="post" action="/admin/logout" class="inline-form"><input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>"><button class="link-button">Sair</button></form>
    </nav>
  </header>
  <main>
    <p class="eyebrow">PAINEL ADMINISTRATIVO</p>
    <h1>Ola, <?= htmlspecialchars($user['name']) ?>.</h1>
    <p class="lead">Ambiente de preparacao, conferencia e publicacao das simulacoes orcamentarias da Matriz CONIF, com foco no Campus Pouso Alegre.</p>

    <?php if ((bool) $user['must_change_password']): ?>
      <div class="alert alert-warning"><strong>Credenciais provisorias.</strong> O acesso de teste ainda esta ativo. <a href="/admin/account">Altere o usuario e a senha</a> antes de publicar o sistema.</div>
    <?php endif; ?>

    <section class="summary-grid" aria-label="Resumo da administracao">
      <article class="metric-card"><span>Escopo principal</span><strong>Pouso Alegre</strong><small>Simulacao centrada no campus.</small></article>
      <article class="metric-card"><span>Base institucional</span><strong>IFSULDEMINAS</strong><small>Dados das demais unidades entram para participacao relativa.</small></article>
      <article class="metric-card"><span>Status do modulo</span><strong>Em construcao</strong><small>Validacao local pelo XAMPP.</small></article>
    </section>

    <section class="admin-grid" aria-label="Etapas administrativas">
      <a class="admin-card" href="/admin/periods"><span>01</span><strong>Anos-base</strong><small>Cadastrar ano-base, ano orcamentario, status e observacoes.</small><em>Acessar Anos-base</em></a>
      <a class="admin-card" href="/admin/imports"><span>02</span><strong>Importacoes</strong><small>Receber uma planilha por vez, resumir conteudo e registrar fonte.</small><em>Acessar Importacoes</em></a>
      <a class="admin-card" href="/admin/parameters"><span>03</span><strong>Parametros</strong><small>Definir valores normativos, hipoteses e parametros administrativos.</small><em>Acessar Parametros</em></a>
      <a class="admin-card" href="/admin/simulations"><span>04</span><strong>Simulacoes</strong><small>Executar cenarios para o campus Pouso Alegre e comparar resultados.</small><em>Acessar Simulacoes</em></a>
      <a class="admin-card" href="/admin/publishing"><span>05</span><strong>Publicacao</strong><small>Liberar para o portal publico apenas simulacoes conferidas.</small><em>Acessar Publicacao</em></a>
      <a class="admin-card" href="/admin/audit"><span>06</span><strong>Auditoria</strong><small>Consultar historico de uploads, parametros e mudancas relevantes.</small><em>Acessar Auditoria</em></a>
    </section>
  </main>
</body>
</html>
