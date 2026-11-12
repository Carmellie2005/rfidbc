<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipArchive;

class MonthlyRecordExport
{
    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public static function download(string $format, string $filenameBase, array $headers, iterable $rows): StreamedResponse|BinaryFileResponse
    {
        return $format === 'xlsx'
            ? self::xlsx($filenameBase.'.xlsx', $headers, $rows)
            : self::csv($filenameBase.'.csv', $headers, $rows);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    private static function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'wb');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_map(self::cellValue(...), array_values($row)));
            }

            fclose($handle);
        }, $filename, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    private static function xlsx(string $filename, array $headers, iterable $rows): BinaryFileResponse
    {
        File::ensureDirectoryExists(storage_path('app'));

        $temporaryPath = tempnam(storage_path('app'), 'monthly-export-');

        if ($temporaryPath === false) {
            throw new \RuntimeException('The Excel export file could not be created.');
        }

        try {
            $zip = new ZipArchive();

            if ($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('The Excel export archive could not be created.');
            }

            $normalisedRows = [];

            foreach ($rows as $row) {
                $normalisedRows[] = array_map(self::cellValue(...), array_values($row));
            }

            $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
            $zip->addFromString('_rels/.rels', self::rootRelationshipsXml());
            $zip->addFromString('xl/workbook.xml', self::workbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelationshipsXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', self::worksheetXml($headers, $normalisedRows));

            if (! $zip->close()) {
                throw new \RuntimeException('The Excel export archive could not be finalized.');
            }
        } catch (Throwable $exception) {
            @unlink($temporaryPath);

            throw $exception;
        }

        return response()
            ->download($temporaryPath, $filename, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    private static function cellValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    private static function worksheetXml(array $headers, array $rows): string
    {
        $allRows = [array_values($headers), ...$rows];
        $lastColumn = self::columnName(max(0, count($headers) - 1));
        $lastRow = count($allRows);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<dimension ref="A1:'.$lastColumn.$lastRow.'"/><sheetViews><sheetView workbookViewId="0"/></sheetViews>';
        $xml .= '<sheetData>';

        foreach ($allRows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $xml .= '<row r="'.$excelRow.'">';

            foreach (array_values($row) as $columnIndex => $value) {
                $cellReference = self::columnName($columnIndex).$excelRow;
                $escapedValue = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

                $xml .= '<c r="'.$cellReference.'" t="inlineStr"><is><t xml:space="preserve">'.$escapedValue.'</t></is></c>';
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData><autoFilter ref="A1:'.$lastColumn.$lastRow.'"/></worksheet>';

        return $xml;
    }

    private static function columnName(int $index): string
    {
        $name = '';
        $number = $index + 1;

        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $number = intdiv($number - 1, 26);
        }

        return $name;
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private static function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Records" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }
}
