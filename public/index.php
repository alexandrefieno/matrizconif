<?php
declare(strict_types=1);

use MatrizConif\Import\SpreadsheetImportService;
use MatrizConif\Security\Auth;
use MatrizConif\Security\Csrf;
use PDO;
use Throwable;

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

$fetchPeriods = static function (PDO $database): array {
    return $database->query('SELECT id, base_year, budget_year, title, status FROM base_periods ORDER BY budget_year DESC, base_year DESC')->fetchAll();
};

$fetchImports = static function (PDO $database): array {
    return $database->query(
        "SELECT ib.id, ib.import_type, ib.original_filename, ib.source_name, ib.row_count, ib.status, ib.uploaded_at,
                bp.base_year, bp.budget_year, u.name AS uploaded_by_name
           FROM import_batches ib
           JOIN base_periods bp ON bp.id = ib.base_period_id
           JOIN users u ON u.id = ib.uploaded_by
          ORDER BY ib.uploaded_at DESC
          LIMIT 20"
    )->fetchAll();
};

$registerImport = static function (PDO $database, array $user, array $input, array $import): int {
    $database->beginTransaction();
    try {
        $summary = $import['summary'];
        $stmt = $database->prepare(
            "INSERT INTO import_batches
                (base_period_id, import_type, original_filename, stored_filename, sha256, source_name, source_url, reference_date, row_count, status, validation_report, uploaded_by)
             VALUES
                (:base_period_id, :import_type, :original_filename, :stored_filename, :sha256, :source_name, :source_url, :reference_date, :row_count, 'validated', :validation_report, :uploaded_by)"
        );
        $stmt->execute([
            ':base_period_id' => (int) $input['base_period_id'],
            ':import_type' => $input['import_type'],
            ':original_filename' => $import['original_filename'],
            ':stored_filename' => $import['stored_filename'],
            ':sha256' => $import['sha256'],
            ':source_name' => $input['source_name'],
            ':source_url' => $input['source_url'] !== '' ? $input['source_url'] : null,
            ':reference_date' => $input['reference_date'] !== '' ? $input['reference_date'] : null,
            ':row_count' => (int) $summary['row_count'],
            ':validation_report' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':uploaded_by' => (int) $user['id'],
        ]);
        $batchId = (int) $database->lastInsertId();

        $rowStmt = $database->prepare(
            "INSERT INTO import_rows (import_batch_id, source_row, payload, validation_status)
             VALUES (:import_batch_id, :source_row, :payload, 'valid')"
        );
        foreach ($import['rows'] as $sourceRow => $payload) {
            $rowStmt->execute([
                ':import_batch_id' => $batchId,
                ':source_row' => (int) $sourceRow,
                ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }

        $auditStmt = $database->prepare(
            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, after_data)
             VALUES (:user_id, 'import_uploaded', 'import_batch', :entity_id, :after_data)"
        );
        $auditStmt->execute([
            ':user_id' => (int) $user['id'],
            ':entity_id' => $batchId,
            ':after_data' => json_encode([
                'base_period_id' => (int) $input['base_period_id'],
                'import_type' => $input['import_type'],
                'original_filename' => $import['original_filename'],
                'row_count' => (int) $summary['row_count'],
                'headers' => $summary['headers'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $database->commit();
        return $batchId;
    } catch (Throwable $exception) {
        $database->rollBack();
        throw $exception;
    }
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

if ($path === '/admin/imports') {
    $errors = [];
    $lastImport = null;
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            http_response_code(419);
            $errors[] = 'A sessao do formulario expirou. Tente novamente.';
        } else {
            $input = [
                'base_period_id' => (int) ($_POST['base_period_id'] ?? 0),
                'import_type' => (string) ($_POST['import_type'] ?? ''),
                'source_name' => trim((string) ($_POST['source_name'] ?? '')),
                'source_url' => trim((string) ($_POST['source_url'] ?? '')),
                'reference_date' => trim((string) ($_POST['reference_date'] ?? '')),
            ];
            $allowedTypes = ['pnp_cycles','pnp_income','institution_indicators','campus_parameters','budget_envelopes'];
            if ($input['base_period_id'] <= 0) {
                $errors[] = 'Selecione um periodo/ano-base.';
            }
            if (!in_array($input['import_type'], $allowedTypes, true)) {
                $errors[] = 'Selecione o tipo de importacao.';
            }
            if ($input['source_name'] === '') {
                $errors[] = 'Informe a fonte da planilha.';
            }
            if (!isset($_FILES['spreadsheet'])) {
                $errors[] = 'Selecione uma planilha para importar.';
            }

            if (!$errors) {
                try {
                    $service = new SpreadsheetImportService(dirname(__DIR__) . '/storage');
                    $import = $service->storeAndSummarize($_FILES['spreadsheet']);
                    $batchId = $registerImport($database, $user, $input, $import);
                    $lastImport = $import['summary'] + [
                        'batch_id' => $batchId,
                        'original_filename' => $import['original_filename'],
                        'import_type' => $input['import_type'],
                    ];
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }
    }

    $render('admin/imports', [
        'user' => $user,
        'csrfToken' => Csrf::token(),
        'periods' => $fetchPeriods($database),
        'imports' => $fetchImports($database),
        'errors' => $errors,
        'lastImport' => $lastImport,
    ]);
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
