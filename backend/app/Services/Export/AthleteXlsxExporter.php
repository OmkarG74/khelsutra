<?php

namespace App\Services\Export;

use ZipArchive;

class AthleteXlsxExporter
{
    /**
     * Build a safe, meaningful .xlsx filename based on active sport/status filters.
     */
    public static function buildFilename(?string $sportName = null, ?string $status = null): string
    {
        $parts = ['khelsutra', 'athletes'];

        if (!empty($sportName)) {
            $sportSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($sportName)));
            $sportSlug = trim((string)$sportSlug, '-');
            if ($sportSlug !== '') {
                $parts[] = $sportSlug;
            }
        }

        if (!empty($status)) {
            $statusClean = strtolower(trim($status));
            if (in_array($statusClean, ['active', 'inactive', 'injured', 'suspended'], true)) {
                $parts[] = $statusClean;
            }
        }

        return implode('-', $parts) . '.xlsx';
    }

    /**
     * Transform raw athlete rows into normalized export table rows (without internal database IDs).
     */
    public static function formatRows(array $athletes): array
    {
        $formatted = [];
        foreach ($athletes as $ath) {
            $fullName = trim(($ath['first_name'] ?? '') . ' ' . ($ath['last_name'] ?? ''));
            $genderRaw = $ath['gender'] ?? '';
            $gender = (!empty($genderRaw) && $genderRaw !== 'not_specified')
                ? ucfirst(str_replace('_', ' ', (string)$genderRaw))
                : 'Not specified';

            $dob = !empty($ath['date_of_birth'])
                ? date('Y-m-d', strtotime((string)$ath['date_of_birth']))
                : 'Not provided';

            $sport = !empty($ath['sport_name']) ? (string)$ath['sport_name'] : 'Not specified';

            if (!empty($ath['all_teams_label'])) {
                $assignedTeam = (string)$ath['all_teams_label'];
            } elseif (!empty($ath['teams']) && is_array($ath['teams'])) {
                $teamNames = array_values(array_filter(array_column($ath['teams'], 'name')));
                $assignedTeam = !empty($teamNames) ? implode(', ', $teamNames) : 'Unassigned';
            } elseif (!empty($ath['team_name'])) {
                $assignedTeam = (string)$ath['team_name'];
            } else {
                $assignedTeam = 'Unassigned';
            }

            $phone = !empty($ath['phone']) ? (string)$ath['phone'] : 'Not provided';
            $email = !empty($ath['email']) ? (string)$ath['email'] : 'Not provided';
            $status = !empty($ath['status']) ? ucfirst((string)$ath['status']) : 'Active';

            if (!empty($ath['registration_date'])) {
                $regDate = date('Y-m-d', strtotime((string)$ath['registration_date']));
            } elseif (!empty($ath['created_at'])) {
                $regDate = date('Y-m-d', strtotime((string)$ath['created_at']));
            } else {
                $regDate = 'Not provided';
            }

            $formatted[] = [
                'Registration ID'   => (string)($ath['athlete_code'] ?? ''),
                'Athlete Name'      => $fullName,
                'Gender'            => $gender,
                'Date of Birth'     => $dob,
                'Sport'             => $sport,
                'Assigned Team'     => $assignedTeam,
                'Phone'             => $phone,
                'Email'             => $email,
                'Status'            => $status,
                'Registration Date' => $regDate,
            ];
        }

        return $formatted;
    }

    /**
     * Generate a genuine OpenXML .xlsx binary string for the given athlete records.
     */
    public static function generateXlsxBinary(array $athletes): string
    {
        $headers = [
            'Registration ID',
            'Athlete Name',
            'Gender',
            'Date of Birth',
            'Sport',
            'Assigned Team',
            'Phone',
            'Email',
            'Status',
            'Registration Date',
        ];

        $colWidths = [
            1 => 18, // Registration ID
            2 => 26, // Athlete Name
            3 => 14, // Gender
            4 => 16, // Date of Birth
            5 => 18, // Sport
            6 => 32, // Assigned Team
            7 => 18, // Phone
            8 => 28, // Email
            9 => 14, // Status
            10 => 18, // Registration Date
        ];

        $rows = self::formatRows($athletes);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ks_xlsx_');
        if ($tmpFile === false) {
            throw new \RuntimeException('Unable to allocate temporary file for XLSX generation.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpFile);
            throw new \RuntimeException('Unable to initialize XLSX archive.');
        }

        $zip->addFromString('[Content_Types].xml', self::buildContentTypesXml());
        $zip->addFromString('_rels/.rels', self::buildRootRelsXml());
        $zip->addFromString('docProps/app.xml', self::buildAppXml());
        $zip->addFromString('docProps/core.xml', self::buildCoreXml());
        $zip->addFromString('xl/workbook.xml', self::buildWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::buildWorkbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::buildStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::buildSheetXml($headers, $rows, $colWidths));

        $zip->close();

        $binary = file_get_contents($tmpFile);
        @unlink($tmpFile);

        if ($binary === false) {
            throw new \RuntimeException('Failed to read generated XLSX binary.');
        }

        return $binary;
    }

    protected static function escapeXml(string $value): string
    {
        // Strip invalid XML 1.0 control characters
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return htmlspecialchars((string)$clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected static function columnLetter(int $colIndex1Based): string
    {
        $letter = '';
        while ($colIndex1Based > 0) {
            $mod = ($colIndex1Based - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $colIndex1Based = (int)(($colIndex1Based - $mod) / 26);
        }
        return $letter;
    }

    protected static function buildSheetXml(array $headers, array $rows, array $colWidths): string
    {
        $colCount = count($headers);
        $rowCount = count($rows) + 1;
        $lastColLetter = self::columnLetter($colCount);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<dimension ref="A1:' . $lastColLetter . $rowCount . '"/>';
        $xml .= '<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="18"/>';

        // Column widths
        $xml .= '<cols>';
        foreach ($colWidths as $colIdx => $width) {
            $xml .= '<col min="' . (int)$colIdx . '" max="' . (int)$colIdx . '" width="' . (float)$width . '" customWidth="1"/>';
        }
        $xml .= '</cols>';

        $xml .= '<sheetData>';

        // Row 1: Header row (style s="1")
        $xml .= '<row r="1" ht="24" customHeight="1">';
        foreach ($headers as $i => $headerText) {
            $colLetter = self::columnLetter($i + 1);
            $cellRef = $colLetter . '1';
            $xml .= '<c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . self::escapeXml($headerText) . '</t></is></c>';
        }
        $xml .= '</row>';

        // Data rows (style s="2")
        $rowNum = 2;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rowNum . '" ht="20" customHeight="1">';
            foreach ($headers as $i => $headerKey) {
                $colLetter = self::columnLetter($i + 1);
                $cellRef = $colLetter . $rowNum;
                $val = (string)($row[$headerKey] ?? '');
                $xml .= '<c r="' . $cellRef . '" s="2" t="inlineStr"><is><t xml:space="preserve">' . self::escapeXml($val) . '</t></is></c>';
            }
            $xml .= '</row>';
            $rowNum++;
        }

        $xml .= '</sheetData>';
        $xml .= '</worksheet>';

        return $xml;
    }

    protected static function buildContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    protected static function buildRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    protected static function buildAppXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>KhelSutra Sports Management Platform</Application>'
            . '</Properties>';
    }

    protected static function buildCoreXml(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>KhelSutra Athletes Report</dc:title>'
            . '<dc:creator>KhelSutra</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '</cp:coreProperties>';
    }

    protected static function buildWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="Athletes" sheetId="1" r:id="rId1"/>'
            . '</sheets>'
            . '</workbook>';
    }

    protected static function buildWorkbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    protected static function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFE2E8F0"/></left>'
            . '<right style="thin"><color rgb="FFE2E8F0"/></right>'
            . '<top style="thin"><color rgb="FFE2E8F0"/></top>'
            . '<bottom style="thin"><color rgb="FFE2E8F0"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
    }
}
