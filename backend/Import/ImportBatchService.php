<?php
declare(strict_types=1);

namespace MatrizConif\Import;

use DateTimeImmutable;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

final class ImportBatchService
{
    private ?array $activeUnits = null;

    public function __construct(private readonly \PDO $database)
    {
    }

    /** @return array<string,array{label:string,required:bool,type:string,aliases:array<int,string>}> */
    public function fieldsFor(string $importType): array
    {
        $unit = [
            'unit_code' => $this->field('Codigo da unidade', false, 'string', ['codigo unidade', 'cod unidade', 'codigo campus']),
            'unit_name' => $this->field('Nome da unidade', false, 'string', ['unidade', 'campus', 'nome unidade', 'unidade de ensino']),
        ];

        return match ($importType) {
            'pnp_cycles' => $unit + [
                'cycle_code' => $this->field('Codigo do ciclo', true, 'string', ['ciclo', 'codigo ciclo', 'id ciclo']),
                'course_name' => $this->field('Curso', true, 'string', ['nome curso', 'curso']),
                'course_type' => $this->field('Tipo de curso', true, 'string', ['tipo curso', 'tipo de curso']),
                'modality' => $this->field('Modalidade', true, 'string', ['modalidade de ensino', 'modalidade']),
                'funding_type' => $this->field('Tipo de financiamento', false, 'string', ['financiamento', 'tipo financiamento']),
                'knowledge_axis' => $this->field('Eixo tecnologico', false, 'string', ['eixo', 'eixo tecnologico']),
                'offer_type' => $this->field('Tipo de oferta', false, 'string', ['oferta', 'tipo oferta']),
                'agricultural' => $this->field('Curso agropecuario', false, 'boolean', ['agropecuaria', 'agropecuario']),
                'start_date' => $this->field('Inicio do ciclo', true, 'date', ['data inicio', 'inicio ciclo']),
                'end_date' => $this->field('Fim do ciclo', true, 'date', ['data fim', 'fim ciclo']),
                'cycle_hours' => $this->field('Carga horaria do ciclo', false, 'number', ['carga horaria ciclo', 'ch ciclo']),
                'catalog_hours' => $this->field('Carga horaria de catalogo', false, 'number', ['carga horaria catalogo', 'ch catalogo']),
                'enrollment_count' => $this->field('Quantidade de matriculas', true, 'number', ['matriculas', 'quantidade', 'qtd matriculas']),
            ],
            'pnp_income' => $unit + [
                'band_code' => $this->field('Faixa de renda', true, 'string', ['faixa renda', 'renda', 'faixa']),
                'student_count' => $this->field('Quantidade de estudantes', true, 'number', ['estudantes', 'alunos', 'quantidade']),
            ],
            'institution_indicators' => $unit + [
                'indicator_key' => $this->field('Indicador', true, 'string', ['indicador', 'chave indicador']),
                'numeric_value' => $this->field('Valor numerico', false, 'number', ['valor', 'valor numerico']),
                'text_value' => $this->field('Valor textual', false, 'string', ['texto', 'valor textual']),
            ],
            'campus_parameters' => $unit + [
                'parameter_key' => $this->field('Parametro', true, 'string', ['parametro', 'chave parametro']),
                'numeric_value' => $this->field('Valor numerico', false, 'number', ['valor', 'valor numerico']),
                'text_value' => $this->field('Valor textual', false, 'string', ['texto', 'valor textual']),
            ],
            'budget_envelopes' => $unit + [
                'component' => $this->field('Componente orcamentario', true, 'string', ['componente', 'envelope', 'tipo']),
                'action_code' => $this->field('Acao orcamentaria', false, 'string', ['acao', 'acao orcamentaria', 'codigo acao']),
                'amount' => $this->field('Valor', true, 'number', ['valor', 'montante', 'orcamento']),
            ],
            default => throw new RuntimeException('Tipo de importacao desconhecido.'),
        };
    }

    public function batch(int $batchId): ?array
    {
        $stmt = $this->database->prepare(
            "SELECT ib.*, bp.base_year, bp.budget_year, bp.title AS period_title,
                    uploader.name AS uploaded_by_name, validator.name AS validated_by_name,
                    promoter.name AS promoted_by_name
               FROM import_batches ib
               JOIN base_periods bp ON bp.id = ib.base_period_id
               JOIN users uploader ON uploader.id = ib.uploaded_by
          LEFT JOIN users validator ON validator.id = ib.validated_by
          LEFT JOIN users promoter ON promoter.id = ib.promoted_by
              WHERE ib.id = :id"
        );
        $stmt->execute([':id' => $batchId]);
        $batch = $stmt->fetch();

        return $batch ?: null;
    }

    public function rows(int $batchId, int $page = 1, int $perPage = 25): array
    {
        $offset = (max(1, $page) - 1) * $perPage;
        $stmt = $this->database->prepare(
            'SELECT id, source_row, payload, validation_status, validation_errors
               FROM import_rows WHERE import_batch_id = :batch_id
              ORDER BY source_row LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':batch_id', $batchId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn (array $row): array => $this->decodeRow($row), $stmt->fetchAll());
    }

    /** @param array<int,string> $headers @return array<string,string> */
    public function suggestMapping(string $importType, array $headers): array
    {
        $normalizedHeaders = [];
        foreach ($headers as $header) {
            $normalizedHeaders[$this->normalize((string) $header)] = (string) $header;
        }

        $mapping = [];
        foreach ($this->fieldsFor($importType) as $key => $field) {
            foreach (array_merge([$key, $field['label']], $field['aliases']) as $alias) {
                $normalized = $this->normalize($alias);
                if (isset($normalizedHeaders[$normalized])) {
                    $mapping[$key] = $normalizedHeaders[$normalized];
                    break;
                }
            }
        }

        return $mapping;
    }

    /** @param array<string,string> $mapping */
    public function validate(int $batchId, array $mapping, int $userId): array
    {
        $batch = $this->batch($batchId);
        if (!$batch || in_array($batch['status'], ['rejected', 'promoted'], true)) {
            throw new RuntimeException('Este lote nao pode ser validado no estado atual.');
        }

        $summary = json_decode((string) $batch['validation_report'], true) ?: [];
        $headers = array_map('strval', $summary['headers'] ?? []);
        $fields = $this->fieldsFor((string) $batch['import_type']);
        $mappingErrors = $this->mappingErrors($fields, $mapping, $headers);
        $valid = 0;
        $invalid = 0;
        $pousoAlegre = 0;
        $units = [];
        $seen = [];
        $errorSamples = [];

        $this->database->beginTransaction();
        try {
            $allRows = $this->allRows($batchId);
            $update = $this->database->prepare(
                'UPDATE import_rows SET validation_status = :status, validation_errors = :errors WHERE id = :id'
            );
            foreach ($allRows as $row) {
                $payload = $row['payload'];
                [$data, $errors] = $this->transform($fields, $mapping, $payload);
                $errors = array_merge($mappingErrors, $errors);
                $unit = $this->resolveUnit($data);
                if (!$unit) {
                    $errors[] = 'Unidade nao cadastrada ou nao identificada.';
                } else {
                    $data['_unit_id'] = (int) $unit['id'];
                    $units[$unit['name']] = ($units[$unit['name']] ?? 0) + 1;
                    if ($unit['code'] === 'POUSO_ALEGRE') {
                        $pousoAlegre++;
                    }
                }
                $errors = array_merge($errors, $this->businessErrors((string) $batch['import_type'], $data));
                $key = $this->naturalKey((string) $batch['import_type'], $data);
                if ($key !== null && isset($seen[$key])) {
                    $errors[] = 'Registro duplicado no lote; primeira ocorrencia na linha ' . $seen[$key] . '.';
                } elseif ($key !== null) {
                    $seen[$key] = (int) $row['source_row'];
                }

                $errors = array_values(array_unique($errors));
                $status = $errors ? 'invalid' : 'valid';
                $errors ? $invalid++ : $valid++;
                if ($errors && count($errorSamples) < 20) {
                    $errorSamples[] = ['source_row' => (int) $row['source_row'], 'errors' => $errors];
                }
                $update->execute([
                    ':status' => $status,
                    ':errors' => $errors ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null,
                    ':id' => (int) $row['id'],
                ]);
            }

            ksort($units);
            $report = array_merge($summary, [
                'validated_at' => date(DATE_ATOM),
                'mapping_errors' => $mappingErrors,
                'valid_rows' => $valid,
                'invalid_rows' => $invalid,
                'pouso_alegre_rows' => $pousoAlegre,
                'units' => $units,
                'error_samples' => $errorSamples,
            ]);
            $status = $invalid === 0 && !$mappingErrors && $valid > 0 ? 'validated' : 'checking';
            $stmt = $this->database->prepare(
                "UPDATE import_batches
                    SET status = :status, mapping = :mapping, validation_report = :report,
                        rejection_reason = NULL, validated_by = :validated_by, validated_at = :validated_at
                  WHERE id = :id"
            );
            $stmt->execute([
                ':status' => $status,
                ':mapping' => json_encode($mapping, JSON_UNESCAPED_UNICODE),
                ':report' => json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':validated_by' => $status === 'validated' ? $userId : null,
                ':validated_at' => $status === 'validated' ? date('Y-m-d H:i:s') : null,
                ':id' => $batchId,
            ]);
            $this->audit($userId, 'import_validated', $batchId, ['status' => $status, 'report' => $report]);
            $this->database->commit();

            return $report + ['status' => $status];
        } catch (\Throwable $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    public function reject(int $batchId, string $reason, int $userId): void
    {
        if (trim($reason) === '') {
            throw new RuntimeException('Informe o motivo da rejeicao.');
        }
        $batch = $this->batch($batchId);
        if (!$batch || $batch['status'] === 'promoted') {
            throw new RuntimeException('Este lote nao pode ser rejeitado.');
        }
        $stmt = $this->database->prepare(
            "UPDATE import_batches SET status = 'rejected', rejection_reason = :reason,
                    validated_by = NULL, validated_at = NULL WHERE id = :id"
        );
        $stmt->execute([':reason' => trim($reason), ':id' => $batchId]);
        $this->audit($userId, 'import_rejected', $batchId, ['reason' => trim($reason)]);
    }

    public function promote(int $batchId, int $userId): int
    {
        $batch = $this->batch($batchId);
        if (!$batch || $batch['status'] !== 'validated') {
            throw new RuntimeException('Somente um lote validado pode ser incorporado.');
        }
        $mapping = json_decode((string) $batch['mapping'], true) ?: [];
        $fields = $this->fieldsFor((string) $batch['import_type']);
        $rows = $this->allRows($batchId);
        $inserted = 0;

        $this->database->beginTransaction();
        try {
            foreach ($rows as $row) {
                if ($row['validation_status'] !== 'valid') {
                    throw new RuntimeException('O lote possui linha nao validada. Execute a validacao novamente.');
                }
                [$data, $errors] = $this->transform($fields, $mapping, $row['payload']);
                $unit = $this->resolveUnit($data);
                if ($errors || !$unit) {
                    throw new RuntimeException('A linha ' . $row['source_row'] . ' deixou de atender ao mapeamento validado.');
                }
                $data['_unit_id'] = (int) $unit['id'];
                $this->insertFinal($batch, (int) $row['source_row'], $data);
                $inserted++;
            }
            $stmt = $this->database->prepare(
                "UPDATE import_batches SET status = 'promoted', promoted_by = :user_id, promoted_at = NOW() WHERE id = :id"
            );
            $stmt->execute([':user_id' => $userId, ':id' => $batchId]);
            $this->audit($userId, 'import_promoted', $batchId, ['inserted_rows' => $inserted]);
            $this->database->commit();

            return $inserted;
        } catch (\Throwable $exception) {
            $this->database->rollBack();
            if (str_contains($exception->getMessage(), 'Duplicate entry')) {
                throw new RuntimeException('A incorporacao encontrou dados ja existentes para o periodo. O lote nao foi incorporado.');
            }
            throw $exception;
        }
    }

    private function field(string $label, bool $required, string $type, array $aliases): array
    {
        return compact('label', 'required', 'type', 'aliases');
    }

    private function allRows(int $batchId): array
    {
        $stmt = $this->database->prepare(
            'SELECT id, source_row, payload, validation_status, validation_errors FROM import_rows WHERE import_batch_id = :id ORDER BY source_row'
        );
        $stmt->execute([':id' => $batchId]);
        return array_map(fn (array $row): array => $this->decodeRow($row), $stmt->fetchAll());
    }

    private function decodeRow(array $row): array
    {
        $row['payload'] = json_decode((string) $row['payload'], true) ?: [];
        $row['validation_errors'] = $row['validation_errors'] ? json_decode((string) $row['validation_errors'], true) : [];
        return $row;
    }

    private function mappingErrors(array $fields, array $mapping, array $headers): array
    {
        $errors = [];
        if (trim((string) ($mapping['unit_code'] ?? '')) === '' && trim((string) ($mapping['unit_name'] ?? '')) === '') {
            $errors[] = 'Mapeie ao menos o codigo ou o nome da unidade.';
        }
        foreach ($fields as $key => $field) {
            $source = trim((string) ($mapping[$key] ?? ''));
            if ($field['required'] && $source === '') {
                $errors[] = 'Mapeie o campo obrigatorio: ' . $field['label'] . '.';
            } elseif ($source !== '' && !in_array($source, $headers, true)) {
                $errors[] = 'A coluna selecionada para ' . $field['label'] . ' nao existe no arquivo.';
            }
        }
        return $errors;
    }

    private function transform(array $fields, array $mapping, array $payload): array
    {
        $data = [];
        $errors = [];
        foreach ($fields as $key => $field) {
            $source = trim((string) ($mapping[$key] ?? ''));
            $raw = $source === '' ? null : ($payload[$source] ?? null);
            if ($this->blank($raw)) {
                $data[$key] = null;
                if ($field['required']) {
                    $errors[] = $field['label'] . ' nao informado.';
                }
                continue;
            }
            try {
                $data[$key] = $this->convert($raw, $field['type']);
            } catch (RuntimeException) {
                $errors[] = $field['label'] . ' possui formato invalido.';
                $data[$key] = null;
            }
        }
        return [$data, $errors];
    }

    private function businessErrors(string $type, array $data): array
    {
        $errors = [];
        if ($this->blank($data['unit_code'] ?? null) && $this->blank($data['unit_name'] ?? null)) {
            $errors[] = 'Informe o codigo ou o nome da unidade.';
        }
        if (in_array($type, ['institution_indicators', 'campus_parameters'], true)
            && $data['numeric_value'] === null && $this->blank($data['text_value'])) {
            $errors[] = 'Informe ao menos um valor numerico ou textual.';
        }
        if ($type === 'pnp_cycles' && $data['start_date'] && $data['end_date'] && $data['end_date'] < $data['start_date']) {
            $errors[] = 'A data final do ciclo e anterior a data inicial.';
        }
        foreach (['enrollment_count', 'student_count', 'amount'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] < 0) {
                $errors[] = 'O valor de ' . $key . ' nao pode ser negativo.';
            }
        }
        return $errors;
    }

    private function resolveUnit(array $data): ?array
    {
        $code = $this->normalize((string) ($data['unit_code'] ?? ''));
        $name = $this->normalizeUnit((string) ($data['unit_name'] ?? ''));
        $this->activeUnits ??= $this->database->query('SELECT id, code, name FROM units WHERE active = 1')->fetchAll();
        foreach ($this->activeUnits as $unit) {
            if (($code !== '' && $this->normalize((string) $unit['code']) === $code)
                || ($name !== '' && $this->normalizeUnit((string) $unit['name']) === $name)) {
                return $unit;
            }
        }
        return null;
    }

    private function naturalKey(string $type, array $data): ?string
    {
        if (!isset($data['_unit_id'])) {
            return null;
        }
        $parts = match ($type) {
            'pnp_cycles' => [$data['_unit_id'], $data['cycle_code'] ?? null],
            'pnp_income' => [$data['_unit_id'], $data['band_code'] ?? null],
            'institution_indicators' => [$data['_unit_id'], $data['indicator_key'] ?? null],
            'campus_parameters' => [$data['_unit_id'], $data['parameter_key'] ?? null],
            'budget_envelopes' => [$data['_unit_id'], $data['component'] ?? null, $data['action_code'] ?? ''],
            default => [],
        };
        return in_array(null, $parts, true) ? null : implode('|', array_map('strval', $parts));
    }

    private function insertFinal(array $batch, int $sourceRow, array $data): void
    {
        $common = [(int) $batch['base_period_id'], (int) $batch['id'], $data['_unit_id']];
        $sql = match ($batch['import_type']) {
            'pnp_cycles' => 'INSERT INTO pnp_cycles (base_period_id,import_batch_id,unit_id,cycle_code,course_name,course_type,modality,funding_type,knowledge_axis,offer_type,agricultural,start_date,end_date,cycle_hours,catalog_hours,enrollment_count,source_row) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            'pnp_income' => 'INSERT INTO pnp_income_bands (base_period_id,import_batch_id,unit_id,band_code,student_count,source_row) VALUES (?,?,?,?,?,?)',
            'institution_indicators' => 'INSERT INTO institution_indicators (base_period_id,import_batch_id,unit_id,indicator_key,numeric_value,text_value,source_row) VALUES (?,?,?,?,?,?,?)',
            'campus_parameters' => 'INSERT INTO campus_parameter_values (base_period_id,import_batch_id,unit_id,parameter_key,numeric_value,text_value,source_row) VALUES (?,?,?,?,?,?,?)',
            'budget_envelopes' => 'INSERT INTO budget_envelopes (base_period_id,import_batch_id,unit_id,component,action_code,amount,source_row) VALUES (?,?,?,?,?,?,?)',
            default => throw new RuntimeException('Tipo de incorporacao desconhecido.'),
        };
        $values = match ($batch['import_type']) {
            'pnp_cycles' => [...$common, $data['cycle_code'], $data['course_name'], $data['course_type'], $data['modality'], $data['funding_type'], $data['knowledge_axis'], $data['offer_type'], (int) ($data['agricultural'] ?? false), $data['start_date'], $data['end_date'], $data['cycle_hours'], $data['catalog_hours'], $data['enrollment_count'], $sourceRow],
            'pnp_income' => [...$common, $data['band_code'], $data['student_count'], $sourceRow],
            'institution_indicators' => [...$common, $data['indicator_key'], $data['numeric_value'], $data['text_value'], $sourceRow],
            'campus_parameters' => [...$common, $data['parameter_key'], $data['numeric_value'], $data['text_value'], $sourceRow],
            'budget_envelopes' => [...$common, $data['component'], $data['action_code'], $data['amount'], $sourceRow],
        };
        $this->database->prepare($sql)->execute($values);
    }

    private function convert(mixed $value, string $type): mixed
    {
        return match ($type) {
            'number' => $this->number($value),
            'date' => $this->date($value),
            'boolean' => in_array($this->normalize((string) $value), ['1', 'sim', 's', 'true', 'verdadeiro'], true),
            default => trim((string) $value),
        };
    }

    private function number(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $text = preg_replace('/[^0-9,.-]/', '', trim((string) $value)) ?? '';
        if ($text === '') {
            throw new RuntimeException('Numero vazio.');
        }
        if (str_contains($text, ',') && str_contains($text, '.')) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } elseif (str_contains($text, ',')) {
            $text = str_replace(',', '.', $text);
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $text)) {
            $text = str_replace('.', '', $text);
        }
        if (!is_numeric($text)) {
            throw new RuntimeException('Numero invalido.');
        }
        return (float) $text;
    }

    private function date(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, trim((string) $value));
            if ($date && $date->format($format) === trim((string) $value)) {
                return $date->format('Y-m-d');
            }
        }
        throw new RuntimeException('Data invalida.');
    }

    private function blank(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private function normalize(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($value));
        $normalized = mb_strtolower($ascii === false ? $value : $ascii);
        return trim(preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '');
    }

    private function normalizeUnit(string $value): string
    {
        $normalized = $this->normalize($value);
        $normalized = preg_replace('/\b(instituto federal do sul de minas gerais|instituto federal|ifsuldeminas|campus avancado|campus)\b/', ' ', $normalized) ?? $normalized;
        return trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
    }

    private function audit(int $userId, string $action, int $batchId, array $after): void
    {
        $stmt = $this->database->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, after_data) VALUES (:user_id,:action,\'import_batch\',:entity_id,:after_data)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':entity_id' => $batchId,
            ':after_data' => json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
