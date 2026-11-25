<?php
defined('SKYFR') || exit;

const XLSX_STYLE_TEXT = 0;
const XLSX_STYLE_HEAD = 1;
const XLSX_STYLE_MONEY = 2;
const XLSX_STYLE_PERCENT = 3;
const XLSX_STYLE_BOLD = 4;
const XLSX_STYLE_BOLD_MONEY = 5;
const XLSX_STYLE_TITLE = 6;
const XLSX_STYLE_INT = 7;

function xlsx_available(): bool
{
    return class_exists('ZipArchive');
}

function xlsx_column(int $index): string
{
    $name = '';
    for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
        $name = chr(65 + ($n - 1) % 26) . $name;
    }
    return $name;
}

function xlsx_text(string $value): string
{
    $value = (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value);
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function xlsx_cell(mixed $cell, string $ref): string
{
    $value = is_array($cell) ? ($cell['v'] ?? null) : $cell;
    $style = is_array($cell) ? (int) ($cell['s'] ?? XLSX_STYLE_TEXT) : (is_int($value) ? XLSX_STYLE_INT : XLSX_STYLE_TEXT);
    if ($value === null || $value === '') {
        return $style === XLSX_STYLE_TEXT ? '' : '<c r="' . $ref . '" s="' . $style . '"/>';
    }
    if (is_int($value) || is_float($value)) {
        return '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
    }
    return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . xlsx_text((string) $value) . '</t></is></c>';
}

function xlsx_sheet_xml(array $sheet): string
{
    $rows = $sheet['rows'];
    $cols = '';
    foreach ((array) ($sheet['widths'] ?? []) as $i => $width) {
        $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) $width . '" customWidth="1"/>';
    }
    $freeze = (int) ($sheet['freeze'] ?? 0);
    $view = $freeze > 0
        ? '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $freeze . '" topLeftCell="A' . ($freeze + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        : '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
    $xml = '';
    foreach ($rows as $r => $row) {
        $cells = '';
        foreach (array_values($row) as $c => $cell) {
            $cells .= xlsx_cell($cell, xlsx_column($c) . ($r + 1));
        }
        $xml .= '<row r="' . ($r + 1) . '">' . $cells . '</row>';
    }
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . $view . ($cols !== '' ? '<cols>' . $cols . '</cols>' : '') . '<sheetData>' . $xml . '</sheetData></worksheet>';
}

function xlsx_styles_xml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="0.0%"/></numFmts>'
        . '<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="14"/><name val="Calibri"/></font></fonts>'
        . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF2E9DC"/><bgColor indexed="64"/></patternFill></fill></fills>'
        . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left/><right/><top/><bottom style="thin"><color rgb="FFC9B89A"/></bottom><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="8">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
        . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
        . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
        . '<xf numFmtId="164" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/>'
        . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
        . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
        . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
}

function xlsx_build(array $sheets): string
{
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Could not create the spreadsheet.');
    }
    $sheetEntries = '';
    $sheetRels = '';
    $overrides = '';
    foreach (array_values($sheets) as $i => $sheet) {
        $n = $i + 1;
        $name = mb_substr((string) preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', (string) $sheet['name']), 0, 31);
        $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', xlsx_sheet_xml($sheet));
        $sheetEntries .= '<sheet name="' . xlsx_text($name) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        $sheetRels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
        $overrides .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    }
    $styleRel = count($sheets) + 1;
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $overrides . '</Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $sheetEntries . '</sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $sheetRels . '<Relationship Id="rId' . $styleRel . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->addFromString('xl/styles.xml', xlsx_styles_xml());
    $zip->close();
    $bytes = (string) file_get_contents($path);
    @unlink($path);
    return $bytes;
}
