<?php
declare(strict_types=1);

use MatrizConif\Security\Auth;
use MatrizConif\Security\Csrf;

$database = require dirname(__DIR__) . '/config/bootstrap.php';
$auth = new Auth($database);
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');
if ($path === '//') {
    $path = '/';
}

$redirect = static function (string $location): never {
    header('Location: ' . $location, true, 303);
    exit;
};

$render = static function (string $template, array $data = []): never {
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/frontend/templates/' . $template . '.php';
    exit;
};

if ($path === '/' && $method === 'GET') {
    $render('home');
}

if ($path === '/admin/login') {
    if ($auth->user()) {
        $redirect('/admin');
    }

    $error = null;
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            http_response_code(419);
            $error = 'A sessao do formulario expirou. Tente novamente.';
        } elseif ($auth->attempt(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) {
            $redirect('/admin');
        } else {
            $error = 'Usuario ou senha invalidos.';
        }
    }
    $render('admin/login', ['error' => $error, 'csrfToken' => Csrf::token()]);
}

if ($path === '/admin/logout' && $method === 'POST') {
    if (!Csrf::validate($_POST['_token'] ?? null)) {
        http_response_code(419);
        exit('Sessao expirada.');
    }
    $auth->logout();
    $redirect('/admin/login');
}

$user = $auth->user();
if (str_starts_with($path, '/admin') && !$user) {
    $redirect('/admin/login');
}

if ($path === '/admin' && $method === 'GET') {
    $render('admin/dashboard', ['user' => $user, 'csrfToken' => Csrf::token()]);
}

$adminSections = [
    '/admin/periods' => [
        'pageTitle' => 'Anos-base',
        'heading' => 'Anos-base e periodos orcamentarios',
        'description' => 'Cadastro do ano-base analisado e do ano orcamentario simulado. Exemplo: dados de 2025 para simular o orcamento de 2027.',
        'statusText' => 'Pagina estrutural pronta para receber o formulario e a listagem de periodos.',
        'actions' => ['Criar periodo', 'Informar ano-base e ano orcamentario', 'Definir status: rascunho, validado, publicado ou arquivado', 'Registrar observacoes metodologicas'],
        'fields' => ['Ano-base', 'Ano orcamentario', 'Titulo do ciclo', 'Status', 'Observacoes', 'Responsavel pelo cadastro'],
    ],
    '/admin/imports' => [
        'pageTitle' => 'Importacoes',
        'heading' => 'Importacao controlada de planilhas',
        'description' => 'Entrada administrativa para carregar uma planilha por vez, gerar resumo automatico, conferir campos e somente depois incorporar os dados a base.',
        'statusText' => 'Proxima etapa de programacao: upload, leitura, resumo e registro do lote importado.',
        'actions' => ['Selecionar periodo', 'Enviar uma planilha', 'Classificar o tipo de importacao', 'Exibir resumo do conteudo lido', 'Confirmar incorporacao dos dados'],
        'fields' => ['Periodo', 'Tipo de importacao', 'Arquivo', 'Fonte', 'Data de referencia', 'Resumo de linhas', 'Resultado da validacao'],
    ],
    '/admin/parameters' => [
        'pageTitle' => 'Parametros',
        'heading' => 'Parametros da Matriz CONIF',
        'description' => 'Area para informar valores normativos, parametros administrativos e hipoteses documentadas que alimentam o calculo.',
        'statusText' => 'Pagina estrutural pronta; os campos serao vinculados ao dicionario metodologico da matriz.',
        'actions' => ['Selecionar periodo', 'Cadastrar parametro', 'Classificar natureza do parametro', 'Vincular fonte ou justificativa', 'Salvar historico auditavel'],
        'fields' => ['Chave do parametro', 'Valor numerico', 'Valor textual', 'Natureza', 'Fonte', 'Justificativa'],
    ],
    '/admin/simulations' => [
        'pageTitle' => 'Simulacoes',
        'heading' => 'Simulacoes do orcamento de Pouso Alegre',
        'description' => 'Ambiente para executar cenarios com foco no Campus Pouso Alegre, usando os demais dados institucionais apenas para composicao relativa.',
        'statusText' => 'Pagina estrutural pronta para receber o motor de calculo fase a fase.',
        'actions' => ['Selecionar periodo', 'Definir premissas do cenario', 'Executar calculo', 'Decompor resultado por fase e componente', 'Salvar simulacao administrativa'],
        'fields' => ['Periodo', 'Nome do cenario', 'Premissas', 'Versao do motor', 'Resultado Pouso Alegre', 'Comparativo institucional'],
    ],
    '/admin/publishing' => [
        'pageTitle' => 'Publicacao',
        'heading' => 'Publicacao para o portal publico',
        'description' => 'Controle para liberar apenas periodos e simulacoes conferidos, evitando que rascunhos administrativos aparecam para a comunidade.',
        'statusText' => 'Pagina estrutural pronta para receber botoes de validar, publicar e arquivar.',
        'actions' => ['Revisar simulacao calculada', 'Validar responsavel e data', 'Publicar no portal publico', 'Arquivar versoes antigas'],
        'fields' => ['Simulacao', 'Status', 'Validador', 'Data de validacao', 'Data de publicacao', 'Observacoes publicas'],
    ],
    '/admin/audit' => [
        'pageTitle' => 'Auditoria',
        'heading' => 'Auditoria e rastreabilidade',
        'description' => 'Historico das acoes administrativas relevantes: uploads, alteracoes de parametros, validacoes, publicacoes e alteracoes de credenciais.',
        'statusText' => 'Pagina estrutural pronta para receber consulta aos logs ja previstos no banco.',
        'actions' => ['Filtrar por periodo', 'Filtrar por usuario', 'Consultar entidade alterada', 'Comparar antes e depois', 'Exportar registro de rastreabilidade'],
        'fields' => ['Usuario', 'Acao', 'Entidade', 'Registro', 'Antes', 'Depois', 'Data e hora'],
    ],
];

if (isset($adminSections[$path]) && $method === 'GET') {
    $render('admin/section', $adminSections[$path] + ['user' => $user, 'csrfToken' => Csrf::token()]);
}

if ($path === '/admin/account') {
    $errors = [];
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            http_response_code(419);
            $errors[] = 'A sessao do formulario expirou. Tente novamente.';
        } else {
            $newPassword = trim((string) ($_POST['new_password'] ?? ''));
            $confirmation = (string) ($_POST['new_password_confirmation'] ?? '');
            if ($newPassword !== '' && $newPassword !== $confirmation) {
                $errors[] = 'A confirmacao da nova senha nao confere.';
            } else {
                $errors = $auth->changeCredentials(
                    (int) $user['id'],
                    (string) ($_POST['current_password'] ?? ''),
                    trim((string) ($_POST['username'] ?? '')),
                    $newPassword === '' ? null : $newPassword
                );
            }
            if (!$errors) {
                $redirect('/admin/account?updated=1');
            }
        }
    }
    $user = $auth->user();
    $render('admin/account', [
        'user' => $user,
        'errors' => $errors,
        'updated' => isset($_GET['updated']),
        'csrfToken' => Csrf::token(),
    ]);
}

http_response_code(404);
$render('errors/404');
