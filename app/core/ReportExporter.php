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
        $columnWidths = self::xlsxColumnWidths($sheetRows);
        $useLandscape = self::shouldUseLandscape($headers, $rows);

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::rootRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::worksheetXml($sheetRows, $columnWidths, $useLandscape));
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
        $pdfRows = [];
        foreach ($rows as $row) {
            $pdfRows[] = array_map(
                static fn(mixed $value): string => self::normalizePdfValue($value),
                array_values($row)
            );
        }

        $pdfHeaders = array_map(
            static fn(mixed $value): string => self::normalizePdfValue($value),
            array_values($headers)
        );

        $useLandscape = self::shouldUseLandscape($headers, $rows);
        $pageWidth = $useLandscape ? 842.0 : 595.0;
        $pageHeight = $useLandscape ? 595.0 : 842.0;
        $margins = [
            'left' => 36.0,
            'right' => 36.0,
            'top' => 42.0,
            'bottom' => 26.0,
        ];

        $columnWidths = self::pdfColumnWidths($pdfHeaders, $pdfRows, $pageWidth - $margins['left'] - $margins['right']);
        $pageStreams = self::buildPdfPages($title, $pdfHeaders, $pdfRows, $columnWidths, $pageWidth, $pageHeight, $margins);
        $pdf = self::buildPdfDocument($pageStreams, $pageWidth, $pageHeight);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) strlen($pdf));
        echo $pdf;
        exit;
    }

    private static function buildPdfDocument(array $pages, float $pageWidth, float $pageHeight): string
    {
        $objects = [];
        $pageIds = [];
        $contentIds = [];
        $nextId = 3;

        foreach ($pages as $page) {
            $pageIds[] = $nextId++;
            $contentIds[] = $nextId++;
        }

        $fontRegularId = $nextId++;
        $fontBoldId = $nextId++;

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(static fn(int $id): string => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>';
        $objects[$fontRegularId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman >>';
        $objects[$fontBoldId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold >>';

        foreach ($pages as $index => $content) {
            $pageId = $pageIds[$index];
            $contentId = $contentIds[$index];

            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::pdfNumber($pageWidth) . ' ' . self::pdfNumber($pageHeight) . '] /Resources << /Font << /F1 ' . $fontRegularId . ' 0 R /F2 ' . $fontBoldId . ' 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
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

    private static function buildPdfPages(
        string $title,
        array $headers,
        array $rows,
        array $columnWidths,
        float $pageWidth,
        float $pageHeight,
        array $margins
    ): array {
        $pages = [];
        $pageCount = 0;
        $rowPadding = 4.0;
        $lineHeight = 9.0;
        $tableTopOffset = 34.0;

        $startNewPage = static function () use (&$pageCount): string {
            $pageCount++;
            return '';
        };

        $content = $startNewPage();
        $y = $pageHeight - $margins['top'];

        $content .= self::pdfTextCommand($title, $margins['left'], $y, 16.0, true);
        $y -= 20.0;
        $content .= self::pdfTextCommand('Ngay xuat: ' . date('d/m/Y H:i'), $margins['left'], $y, 9.0, false);
        $y -= $tableTopOffset;
        $content .= self::drawPdfTableHeader($headers, $columnWidths, $margins['left'], $y);
        $y -= 24.0;

        foreach ($rows as $row) {
            $wrappedCells = [];
            $maxLines = 1;

            foreach ($headers as $index => $unusedHeader) {
                $wrappedCells[$index] = self::wrapPdfCellText($row[$index] ?? '', $columnWidths[$index] - ($rowPadding * 2));
                $maxLines = max($maxLines, count($wrappedCells[$index]));
            }

            $rowHeight = max(24.0, 10.0 + ($maxLines * $lineHeight));
            if (($y - $rowHeight) < $margins['bottom']) {
                $pages[] = $content;
                $content = $startNewPage();
                $y = $pageHeight - $margins['top'];
                $content .= self::pdfTextCommand($title, $margins['left'], $y, 14.0, true);
                $y -= 18.0;
                $content .= self::drawPdfTableHeader($headers, $columnWidths, $margins['left'], $y);
                $y -= 24.0;
            }

            $content .= self::drawPdfTableRow($wrappedCells, $columnWidths, $margins['left'], $y, $rowHeight, $rowPadding, $lineHeight);
            $y -= $rowHeight;
        }

        $pages[] = $content;
        $totalPages = count($pages);

        foreach ($pages as $index => $pageContent) {
            $footer = 'Trang ' . ($index + 1) . '/' . $totalPages;
            $pages[$index] = $pageContent . self::pdfTextCommand($footer, $pageWidth - $margins['right'] - 48.0, $margins['bottom'] - 8.0, 9.0, false);
        }

        return $pages;
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

    private static function drawPdfTableHeader(array $headers, array $columnWidths, float $startX, float $topY): string
    {
        $commands = '';
        $x = $startX;
        $rowHeight = 24.0;
        $bottomY = $topY - $rowHeight;

        foreach ($headers as $index => $header) {
            $width = $columnWidths[$index];
            $commands .= "0.965 0.93 0.86 rg\n";
            $commands .= "0.68 0.49 0.25 RG\n";
            $commands .= "1 w\n";
            $commands .= self::pdfNumber($x) . ' ' . self::pdfNumber($bottomY) . ' ' . self::pdfNumber($width) . ' ' . self::pdfNumber($rowHeight) . " re B\n";
            $commands .= self::pdfTextCommand($header, $x + 4.0, $topY - 16.0, 8.8, true);
            $x += $width;
        }

        return $commands;
    }

    private static function drawPdfTableRow(
        array $wrappedCells,
        array $columnWidths,
        float $startX,
        float $topY,
        float $rowHeight,
        float $padding,
        float $lineHeight
    ): string {
        $commands = '';
        $x = $startX;
        $bottomY = $topY - $rowHeight;

        foreach ($wrappedCells as $index => $cellLines) {
            $width = $columnWidths[$index];
            $commands .= "1 1 1 rg\n";
            $commands .= "0.76 0.67 0.56 RG\n";
            $commands .= "0.8 w\n";
            $commands .= self::pdfNumber($x) . ' ' . self::pdfNumber($bottomY) . ' ' . self::pdfNumber($width) . ' ' . self::pdfNumber($rowHeight) . " re B\n";

            $textY = $topY - 14.0;
            foreach ($cellLines as $line) {
                $commands .= self::pdfTextCommand($line, $x + $padding, $textY, 8.4, false);
                $textY -= $lineHeight;
            }

            $x += $width;
        }

        return $commands;
    }

    private static function escapePdfText(string $value): string
    {
        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\(', '\)', '', ''],
            $value
        );
    }

    private static function normalizePdfValue(mixed $value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '-';
        }

        return ascii_text($normalized);
    }

    private static function pdfColumnWidths(array $headers, array $rows, float $usableWidth): array
    {
        $columnCount = count($headers);
        if ($columnCount === 0) {
            return [];
        }

        $weights = [];
        $totalWeight = 0.0;

        for ($index = 0; $index < $columnCount; $index++) {
            $maxLength = self::stringLength($headers[$index] ?? '');

            foreach (array_slice($rows, 0, 40) as $row) {
                $maxLength = max($maxLength, self::stringLength($row[$index] ?? ''));
            }

            $weight = max(8.0, min(28.0, (float) $maxLength + 2.0));
            $weights[$index] = $weight;
            $totalWeight += $weight;
        }

        $widths = [];
        $minimumWidth = max(42.0, min(64.0, ($usableWidth / max($columnCount, 1)) * 0.72));
        $remainingWidth = $usableWidth;

        foreach ($weights as $index => $weight) {
            $width = max($minimumWidth, ($usableWidth * $weight) / max($totalWeight, 1.0));
            $widths[$index] = $width;
            $remainingWidth -= $width;
        }

        if ($remainingWidth !== 0.0) {
            $widths[$columnCount - 1] += $remainingWidth;
        }

        return $widths;
    }

    private static function pdfNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private static function pdfTextCommand(string $text, float $x, float $y, float $fontSize, bool $bold): string
    {
        $fontKey = $bold ? '/F2' : '/F1';
        return "BT\n"
            . $fontKey . ' ' . self::pdfNumber($fontSize) . " Tf\n"
            . "0.23 0.15 0.10 rg\n"
            . "1 0 0 1 " . self::pdfNumber($x) . ' ' . self::pdfNumber($y) . " Tm\n"
            . '(' . self::escapePdfText($text) . ") Tj\n"
            . "ET\n";
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function shouldUseLandscape(array $headers, array $rows): bool
    {
        if (count($headers) >= 8) {
            return true;
        }

        $lengthBudget = 0;
        foreach ($headers as $header) {
            $lengthBudget += self::stringLength((string) $header);
        }

        foreach (array_slice($rows, 0, 8) as $row) {
            foreach ((array) $row as $value) {
                $lengthBudget += min(20, self::stringLength((string) $value));
            }
        }

        return $lengthBudget > 180;
    }

    private static function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Times New Roman"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF4A2D13"/><name val="Times New Roman"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8E8"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD5B894"/></left><right style="thin"><color rgb="FFD5B894"/></right><top style="thin"><color rgb="FFD5B894"/></top><bottom style="thin"><color rgb="FFD5B894"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" horizontal="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
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

    private static function worksheetXml(array $rows, array $columnWidths, bool $landscape): string
    {
        $lastColumn = self::xlsxColumnName(max(1, count($rows[0] ?? [])));
        $lastRow = max(1, count($rows));
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="20"/>';

        if ($columnWidths !== []) {
            $xml .= '<cols>';
            foreach ($columnWidths as $index => $width) {
                $columnNumber = $index + 1;
                $xml .= '<col min="' . $columnNumber . '" max="' . $columnNumber . '" width="' . self::xlsxWidthNumber($width) . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="' . ($rowIndex + 1) . '">';

            foreach (array_values($row) as $columnIndex => $value) {
                $cellReference = self::xlsxColumnName($columnIndex + 1) . ($rowIndex + 1);
                $cellStyle = $rowIndex === 0 ? ' s="1"' : ' s="0"';

                if (self::isNumericCell($value)) {
                    $xml .= '<c r="' . $cellReference . '"' . $cellStyle . '><v>' . e((string) $value) . '</v></c>';
                    continue;
                }

                $xml .= '<c r="' . $cellReference . '"' . $cellStyle . ' t="inlineStr"><is><t xml:space="preserve">' . e((string) $value) . '</t></is></c>';
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        $xml .= '<autoFilter ref="A1:' . $lastColumn . $lastRow . '"/>';
        $xml .= '<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>';
        $xml .= '<pageSetup orientation="' . ($landscape ? 'landscape' : 'portrait') . '" paperSize="9" fitToWidth="1" fitToHeight="0"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private static function wrapPdfCellText(string $value, float $width): array
    {
        $cleanValue = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        if ($cleanValue === '') {
            return ['-'];
        }

        $maxChars = max(4, (int) floor($width / 4.35));
        $words = preg_split('/\s+/', $cleanValue) ?: [];
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            if (self::stringLength($word) > $maxChars) {
                if ($currentLine !== '') {
                    $lines[] = $currentLine;
                    $currentLine = '';
                }

                foreach (str_split($word, $maxChars) as $chunk) {
                    $lines[] = $chunk;
                }

                continue;
            }

            $candidate = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            if (self::stringLength($candidate) <= $maxChars) {
                $currentLine = $candidate;
                continue;
            }

            $lines[] = $currentLine;
            $currentLine = $word;
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines === [] ? ['-'] : $lines;
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

    private static function xlsxColumnWidths(array $rows): array
    {
        $widths = [];

        foreach ($rows as $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $length = self::stringLength(trim((string) $value));
                $widths[$columnIndex] = max($widths[$columnIndex] ?? 12, min(40, $length + 3));
            }
        }

        return $widths;
    }

    private static function xlsxWidthNumber(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private static function isNumericCell(mixed $value): bool
    {
        return is_int($value)
            || is_float($value)
            || (is_string($value) && preg_match('/^-?\d+(?:\.\d+)?$/', trim($value)) === 1);
    }
}
