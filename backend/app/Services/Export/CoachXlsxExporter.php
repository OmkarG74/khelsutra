<?php

namespace App\Services\Export;

use ZipArchive;

class CoachXlsxExporter
{
    /**
     * Build a safe, sanitized .xlsx filename based on active specialization/status filters.
     * Defaults to "khelsutra-coaches.xlsx".
     */
    public static function buildFilename(?string $specialization = null, ?string $status = null): string
    {
        $parts = ['khelsutra', 'coaches'];

        if (!empty($specialization)) {
            $specSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($specialization)));
            $specSlug = trim((string)$specSlug, '-');
            if ($specSlug !== '' && strlen($specSlug) <= 40) {
                $parts[] = $specSlug;
            }
        }

        if (!empty($status)) {
            $statusClean = strtolower(trim($status));
            if (in_array($statusClean, ['active', 'inactive'], true)) {
                $parts[] = $statusClean;
            }
        }

        return implode('-', $parts) . '.xlsx';
    }

    /**
     * Format experience years cleanly (e.g., 9.00 -> "9 Years", 18.50 -> "18.5 Years").
     */
    public static function formatExperience($years): string
    {
        if ($years === null || $years === '') {
            return 'Not provided';
        }
        $num = (float)$years;
        $clean = ((float)(int)$num === $num) ? (string)(int)$num : rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
        return $clean . ' ' . ($num == 1.0 ? 'Year' : 'Years');
    }

    /**
     * Transform raw coach rows into normalized export table rows (without internal database IDs).
     */
    public static function formatRows(array $coaches): array
    {
        $formatted = [];
        foreach ($coaches as $coach) {
            $fullName = trim(
                ($coach['first_name'] ?? '') . ' ' .
                (!empty($coach['middle_name']) ? trim((string)$coach['middle_name']) . ' ' : '') .
                ($coach['last_name'] ?? '')
            );

            $designation = !empty($coach['designation']) ? (string)$coach['designation'] : 'Coach';
            $department = !empty($coach['department_name']) ? (string)$coach['department_name'] : 'Sports Department';

            $empTypeRaw = $coach['employment_type'] ?? '';
            $employmentType = !empty($empTypeRaw)
                ? ucwords(str_replace('_', ' ', (string)$empTypeRaw))
                : 'Full Time';

            $specialization = !empty($coach['specialization']) ? (string)$coach['specialization'] : 'General Coaching';
            $experience = self::formatExperience($coach['experience_years'] ?? null);
            $qualification = !empty($coach['qualification']) ? (string)$coach['qualification'] : 'Not provided';
            $licenseNumber = !empty($coach['license_number']) ? (string)$coach['license_number'] : 'Not provided';

            if (!empty($coach['all_teams_label'])) {
                $assignedTeams = (string)$coach['all_teams_label'];
            } elseif (!empty($coach['teams']) && is_array($coach['teams'])) {
                $teamNames = array_values(array_filter(array_column($coach['teams'], 'team_name')));
                $assignedTeams = !empty($teamNames) ? implode(', ', $teamNames) : 'Unassigned';
            } elseif (!empty($coach['assigned_teams'])) {
                $assignedTeams = (string)$coach['assigned_teams'];
            } else {
                $assignedTeams = 'Unassigned';
            }

            $phone = !empty($coach['phone']) ? (string)$coach['phone'] : 'Not provided';
            $email = !empty($coach['email']) ? (string)$coach['email'] : 'Not provided';

            $statusRaw = $coach['coach_status'] ?? ($coach['status'] ?? 'active');
            $status = ucfirst(strtolower((string)$statusRaw));

            $rawJoinDate = $coach['joining_date'] ?? ($coach['coach_joining_date'] ?? ($coach['created_at'] ?? null));
            $joiningDate = !empty($rawJoinDate)
                ? date('Y-m-d', strtotime((string)$rawJoinDate))
                : 'Not provided';

            $formatted[] = [
                'Coach Code'                    => (string)($coach['coach_code'] ?? ''),
                'Coach Name'                    => $fullName,
                'Designation'                   => $designation,
                'Department'                    => $department,
                'Employment Type'               => $employmentType,
                'Specialization'                => $specialization,
                'Experience'                    => $experience,
                'Qualification'                 => $qualification,
                'License / Registration Number' => $licenseNumber,
                'Assigned Teams'                => $assignedTeams,
                'Phone'                         => $phone,
                'Email'                         => $email,
                'Status'                        => $status,
                'Joining Date'                  => $joiningDate,
            ];
        }

        return $formatted;
    }

    /**
     * Generate a genuine OpenXML .xlsx binary string for the given coach records.
     */
    public static function generateXlsxBinary(array $coaches): string
    {
        $headers = [
            'Coach Code',
            'Coach Name',
            'Designation',
            'Department',
            'Employment Type',
            'Specialization',
            'Experience',
            'Qualification',
            'License / Registration Number',
            'Assigned Teams',
            'Phone',
            'Email',
            'Status',
            'Joining Date',
        ];

        $colWidths = [
            1  => 18, // Coach Code
            2  => 24, // Coach Name
            3  => 22, // Designation
            4  => 22, // Department
            5  => 16, // Employment Type
            6  => 30, // Specialization
            7  => 14, // Experience
            8  => 24, // Qualification
            9  => 26, // License / Registration Number
            10 => 32, // Assigned Teams
            11 => 18, // Phone
            12 => 28, // Email
            13 => 14, // Status
            14 => 16, // Joining Date
        ];

        $rows = self::formatRows($coaches);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ks_coach_xlsx_');
        if ($tmpFile === false) {
            throw new \RuntimeException('Unable to allocate temporary file for Coach XLSX generation.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpFile);
            throw new \RuntimeException('Unable to initialize Coach XLSX archive.');
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
            throw new \RuntimeException('Failed to read generated Coach XLSX binary.');
        }

        return $binary;
    }

    protected static function escapeXml(string $value): string
    {
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
            . '<dc:title>KhelSutra Coaches Report</dc:title>'
            . '<dc:creator>KhelSutra</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '</cp:coreProperties>';
    }

    protected static function buildWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="Coaches" sheetId="1" r:id="rId1"/>'
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
