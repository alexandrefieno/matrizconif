<?php
declare(strict_types=1);

namespace MatrizConif\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use RuntimeException;

final class SpreadsheetImportService
{
    private const MAX_PREVIEW_ROWS = 10;
    private const MAX_STORED_ROWS = 5000;

    public function __construct(private readonly string $storagePath)
    {
    }

    /**
     * @param array{name:string,tmp_name:string,error:int,size:int} $file
     * @return array{original_filename:string,stored_filename:string,absolute_path:string,sha256:string,summary:array<string,mixed>,rows:array<int,array<string,mixed>>}
     */
    public function storeAndSummarize(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Nao foi possivel receber o arquivo enviado.');
        }

        $originalName = basename((string) $file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls', 'csv', 'ods'], true)) {
            throw new RuntimeException('Formato nao aceito. Envie xlsx, xls, csv ou ods.');
        }

        $targetDir = rtrim($this->storagePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'imports';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Nao foi possivel criar a pasta de importacoes.');
        }

        $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
            throw new RuntimeException('Nao foi possivel salvar o arquivo enviado.');
        }

        $sha256 = hash_file('sha256', $absolutePath);
        if ($sha256 === false) {
            throw new RuntimeException('Nao foi possivel calcular o hash do arquivo.');
        }

        $parsed = $this->summarize($absolutePath);

        return [
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'absolute_path' => $absolutePath,
            'sha256' => $sha256,
            'summary' => $parsed['summary'],
            'rows' => $parsed['rows'],
        ];
    }

    /**
     * @return array{summary:array<string,mixed>,rows:array<int,array<string,mixed>>}
     */
    private function summarize(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        if (method_exists($reader, 'setReadFilter')) {
            $reader->setReadFilter(new PreviewReadFilter(self::MAX_STORED_ROWS + 1));
        }

        $spreadsheet = $reader->load($path);
        $sheetNames = $spreadsheet->getSheetNames();
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = min($sheet->getHighestDataRow(), self::MAX_STORED_ROWS + 1);
        $highestColumn = $sheet->getHighestDataColumn();
        $rawRows = $sheet->rangeToArray('A1:' . $highestColumn . $highestRow, null, true, true, true);

        $headerRowNumber = $this->findHeaderRow($rawRows);
        $headers = $this->normalizeHeaders($rawRows[$headerRowNumber] ?? []);
        $rows = [];
        $preview = [];

        foreach ($rawRows as $rowNumber => $row) {
            if ($rowNumber <= $headerRowNumber || $this->isBlankRow($row)) {
                continue;
            }

            $payload = [];
            foreach ($headers as $column => $header) {
                $payload[$header] = $row[$column] ?? null;
            }

            if (!$this->isBlankPayload($payload)) {
                $rows[(int) $rowNumber] = $payload;
                if (count($preview) < self::MAX_PREVIEW_ROWS) {
                    $preview[] = $payload;
                }
            }
        }

        $summary = [
            'sheet_names' => $sheetNames,
            'selected_sheet' => $sheet->getTitle(),
            'header_row' => $headerRowNumber,
            'headers' => array_values($headers),
            'column_count' => count($headers),
            'row_count' => count($rows),
            'preview_rows' => $preview,
            'stored_row_limit' => self::MAX_STORED_ROWS,
            'truncated' => $sheet->getHighestDataRow() > self::MAX_STORED_ROWS + 1,
        ];

        $spreadsheet->disconnectWorksheets();

        return ['summary' => $summary, 'rows' => $rows];
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function findHeaderRow(array $rows): int
    {
        foreach ($rows as $rowNumber => $row) {
            if (count(array_filter($row, static fn ($value): bool => trim((string) $value) !== '')) >= 2) {
                return (int) $rowNumber;
            }
        }

        throw new RuntimeException('Nao foi localizada uma linha de cabecalho com ao menos duas colunas preenchidas.');
    }

    /** @param array<string,mixed> $row @return array<string,string> */
    private function normalizeHeaders(array $row): array
    {
        $headers = [];
        $used = [];
        foreach ($row as $column => $value) {
            $label = trim((string) $value);
            if ($label === '') {
                continue;
            }
            $base = mb_substr($label, 0, 120);
            $candidate = $base;
            $suffix = 2;
            while (isset($used[mb_strtolower($candidate)])) {
                $candidate = $base . ' (' . $suffix . ')';
                $suffix++;
            }
            $used[mb_strtolower($candidate)] = true;
            $headers[$column] = $candidate;
        }

        if (!$headers) {
            throw new RuntimeException('A planilha nao possui cabecalhos identificaveis.');
        }

        return $headers;
    }

    /** @param array<string,mixed> $row */
    private function isBlankRow(array $row): bool
    {
        return array_filter($row, static fn ($value): bool => trim((string) $value) !== '') === [];
    }

    /** @param array<string,mixed> $payload */
    private function isBlankPayload(array $payload): bool
    {
        return array_filter($payload, static fn ($value): bool => trim((string) $value) !== '') === [];
    }
}

final class PreviewReadFilter implements IReadFilter
{
    public function __construct(private readonly int $maxRow)
    {
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row <= $this->maxRow;
    }
}
