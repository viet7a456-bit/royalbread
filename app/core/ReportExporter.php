<?php

declare(strict_types=1);

class ReportExporter
{
    public static function download(string $filenameBase, array $headers, array $rows, string $format, string $title = ''): void
    {
        $normalizedFormat = strtolower(trim($format));

        if ($normalizedFormat === 'xlsx') {
            self::downloadXlsx($filenameBase . '.xlsx', $headers, $rows);
        }

        if ($normalizedFormat === 'pdf') {
            self::downloadPdf($filenameBase . '.pdf', $headers, $rows, $title !== '' ? $title : $filenameBase);
        }

        self::downloadCsv($filenameBase . '.csv', $headers, $rows);
    }

    public static function downloadCsv(string $filename, array $headers, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            exit;
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public static function downloadXlsx(string $filename, array $headers, array $rows): void
    {
        if (!class_exists('ZipArchive')) {
            self::downloadCsv(str_replace('.xlsx', '.csv', $filename), $headers, $rows);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'rbxlsx_');
        if ($tempFile === false) {
            self::downloadCsv(str_replace('.xlsx', '.csv', $filename), $headers, $rows);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempFile);
            self::downloadCsv(str_replace('.xlsx', '.csv', $filename), $headers, $rows);
        }

        $sheetRows = array_merge([$headers], $rows);

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::rootRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::worksheetXml($sheetRows));
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($tempFile));
        readfile($tempFile);
        @unlink($tempFile);
        exit;
    }

    public static function downloadPdf(string $filename, array $headers, array $rows, string $title): void
    {
        $lines = [
            ascii_text($title),
            ascii_text('Ngay xuat: ' . date('d/m/Y H:i')),
            str_repeat('=', 100),
            self::buildPdfRow($headers),
            str_repeat('-', 100),
        ];

        foreach ($rows as $row) {
            $wrapped = wordwrap(self::buildPdfRow($row), 100, "\n", true);
            foreach (explode("\n", $wrapped) as $line) {
                $lines[] = $line;
            }
        }

        $pages = array_chunk($lines, 42);
        $pdf = self::buildPdfDocument($pages);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) strlen($pdf));
        echo $pdf;
        exit;
    }

    private static function buildPdfRow(array $row): string
    {
        $sanitized = array_map(
            static fn(mixed $value): string => trim(ascii_text((string) $value)),
            $row
        );

        return implode(' | ', $sanitized);
    }

    private static function buildPdfDocument(array $pages): string
    {
        $objects = [];
        $pageIds = [];
        $contentIds = [];
        $nextId = 3;

        foreach ($pages as $pageLines) {
            $pageIds[] = $nextId++;
            $contentIds[] = $nextId++;
        }

        $fontId = $nextId++;

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(static fn(int $id): string => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>';
        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';

        foreach ($pages as $index => $pageLines) {
            $pageId = $pageIds[$index];
            $contentId = $contentIds[$index];

            $content = self::pdfContentStream($pageLines, $index + 1, count($pages));
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $fontId . ' 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $objects[$contentId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($index = 1; $index <= count($objects); $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index] ?? 0);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    private static function pdfContentStream(array $lines, int $pageNumber, int $pageCount): string
    {
        $content = "BT\n/F1 11 Tf\n14 TL\n50 792 Td\n";

        foreach ($lines as $line) {
            $content .= '(' . self::escapePdfText((string) $line) . ") Tj\nT*\n";
        }

        $content .= "T*\n(" . self::escapePdfText('Trang ' . $pageNumber . '/' . $pageCount) . ") Tj\n";
        $content .= "ET";

        return $content;
    }

    private static function escapePdfText(string $value): string
    {
        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\(', '\)', '', ''],
            $value
        );
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="BaoCao" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    private static function worksheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="' . ($rowIndex + 1) . '">';

            foreach (array_values($row) as $columnIndex => $value) {
                $cellReference = self::xlsxColumnName($columnIndex + 1) . ($rowIndex + 1);
                $cellStyle = $rowIndex === 0 ? ' s="1"' : '';

                if (is_int($value) || is_float($value) || (is_string($value) && preg_match('/^-?\d+(?:\.\d+)?$/', trim($value)) === 1)) {
                    $xml .= '<c r="' . $cellReference . '"' . $cellStyle . '><v>' . e((string) $value) . '</v></c>';
                    continue;
                }

                $xml .= '<c r="' . $cellReference . '"' . $cellStyle . ' t="inlineStr"><is><t xml:space="preserve">' . e((string) $value) . '</t></is></c>';
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    private static function xlsxColumnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
