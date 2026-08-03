<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class IndividualFacultyLoadExcelExporter
{
    /** @param Collection<int, array<string, mixed>> $reports */
    public function export(string $course, string $department, User $dean, Collection $reports): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'faculty-load-');

        if ($temporaryFile === false) {
            throw new RuntimeException('Unable to create the Excel workbook.');
        }

        $zip = new ZipArchive;

        if ($zip->open($temporaryFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($temporaryFile);
            throw new RuntimeException('Unable to open the Excel workbook.');
        }

        try {
            $sheetNames = $this->sheetNames($reports);
            $departmentLogos = [
                'BSIT' => 'bsit-department-logo.jpg',
                'BSBA' => 'bsba-department-logo.jpg',
                'BSHM' => 'bshm-department-logo.jpg',
                'BSED' => 'education-department-logo.jpg',
                'BEED' => 'education-department-logo.jpg',
            ];
            $mccLogo = file_get_contents(public_path('images/mcc-college-logo.png'));
            $departmentLogo = file_get_contents(public_path('images/'.($departmentLogos[$course] ?? 'bsit-department-logo.jpg')));

            if ($mccLogo === false || $departmentLogo === false) {
                throw new RuntimeException('Unable to load the report logos for the Excel workbook.');
            }

            $zip->addFromString('[Content_Types].xml', $this->contentTypes($reports->count()));
            $zip->addFromString('_rels/.rels', $this->rootRelationships());
            $zip->addFromString('docProps/app.xml', $this->applicationProperties($sheetNames));
            $zip->addFromString('docProps/core.xml', $this->coreProperties());
            $zip->addFromString('xl/workbook.xml', $this->workbook($sheetNames));
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships($reports->count()));
            $zip->addFromString('xl/styles.xml', $this->styles());
            $zip->addFromString('xl/media/mcc-logo.png', $mccLogo);
            $zip->addFromString('xl/media/department-logo.jpg', $departmentLogo);
            $zip->addFromString(
                'xl/media/header-divider.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
            );

            foreach ($reports->values() as $index => $report) {
                $sheetNumber = $index + 1;
                $zip->addFromString(
                    'xl/worksheets/sheet'.$sheetNumber.'.xml',
                    $this->worksheet($course, $department, $dean, $report),
                );
                $zip->addFromString(
                    'xl/worksheets/_rels/sheet'.$sheetNumber.'.xml.rels',
                    $this->worksheetRelationships($sheetNumber),
                );
                $zip->addFromString('xl/drawings/drawing'.$sheetNumber.'.xml', $this->drawing());
                $zip->addFromString(
                    'xl/drawings/_rels/drawing'.$sheetNumber.'.xml.rels',
                    $this->drawingRelationships(),
                );
            }
        } finally {
            $zip->close();
        }

        try {
            $contents = file_get_contents($temporaryFile);

            if ($contents === false) {
                throw new RuntimeException('Unable to read the Excel workbook.');
            }

            return $contents;
        } finally {
            @unlink($temporaryFile);
        }
    }

    /** @param Collection<int, array<string, mixed>> $reports
     *  @return array<int, string>
     */
    private function sheetNames(Collection $reports): array
    {
        $used = [];

        return $reports->values()->map(function (array $report, int $index) use (&$used): string {
            /** @var User $instructor */
            $instructor = $report['instructor'];
            $base = trim((string) ($instructor->last_name ?: $instructor->first_name ?: 'Faculty '.($index + 1)));
            $base = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '', $base) ?: 'Faculty '.($index + 1);
            $period = trim(implode(' ', array_filter([$report['semester'] ?? null, $report['academic_year'] ?? null])));
            $base = mb_substr(trim($base.($period !== '' ? ' '.$period : '')), 0, 31);
            $name = $base;
            $suffix = 2;

            while (isset($used[mb_strtolower($name)])) {
                $ending = ' '.$suffix++;
                $name = mb_substr($base, 0, 31 - mb_strlen($ending)).$ending;
            }

            $used[mb_strtolower($name)] = true;

            return $name;
        })->all();
    }

    /** @param array<string, mixed> $report */
    private function worksheet(string $course, string $department, User $dean, array $report): string
    {
        /** @var User $instructor */
        $instructor = $report['instructor'];
        /** @var Collection<int, ClassSchedule> $schedules */
        $schedules = $report['schedules'];
        $semester = match (strtolower((string) ($report['semester'] ?? ''))) {
            '1st', 'first' => 'First Semester',
            '2nd', 'second' => 'Second Semester',
            'summer' => 'Summer',
            default => (string) ($report['semester'] ?: 'Academic Period Not Assigned'),
        };
        $academicYear = (string) ($report['academic_year'] ?: 'Not Assigned');
        $middleInitial = filled($instructor->middle_name) ? strtoupper(mb_substr($instructor->middle_name, 0, 1)).'.' : '—';
        $isRegular = $instructor->employment_type === 'full_time';
        $isPartTime = in_array($instructor->employment_type, ['industry_part_time', 'flexible_part_time', 'part_time'], true);
        $rows = [];

        $rows[] = $this->row(1, [$this->textCell('B1', 'Madridejos Community College', 1)], 24);
        $rows[] = $this->row(2, [$this->textCell('B2', $department, 2)], 20);
        $rows[] = $this->row(3, [$this->textCell('B3', 'Crossing Bunakan, Madridejos, Cebu', 3)], 15);
        $rows[] = $this->row(4, [$this->textCell('B4', 'Email: collegeofinfotech2023@gmail.com', 3)], 18);
        $rows[] = $this->row(5, [$this->textCell('A5', $department, 4)], 36);
        $rows[] = $this->row(6, [$this->textCell('A6', "{$semester}, School Year {$academicYear}", 5)], 18);
        $rows[] = $this->row(7, [$this->textCell('A7', 'INDIVIDUAL FACULTY LOAD SHEET', 6)], 22);
        $rows[] = $this->row(9, [
            $this->textCell('A9', 'Family Name:', 7), $this->textCell('B9', strtoupper((string) $instructor->last_name), 8),
            $this->textCell('D9', 'First Name:', 7), $this->textCell('E9', strtoupper((string) $instructor->first_name), 8),
            $this->textCell('G9', 'Middle Initial:', 7), $this->textCell('H9', $middleInitial, 8),
            $this->textCell('I9', 'Suffix:', 7), $this->textCell('J9', strtoupper((string) ($instructor->suffix ?: '—')), 8),
        ], 28);
        $rows[] = $this->row(10, [
            $this->textCell('A10', 'Employment Status:', 7),
            $this->textCell('C10', ($isRegular ? '☒' : '☐').' Regular / Full-Time', 23),
            $this->textCell('E10', '☐ Probationary', 23),
            $this->textCell('G10', '☐ Contractual', 23),
            $this->textCell('I10', ($isPartTime ? '☒' : '☐').' Part-Time', 23),
        ], 20);

        $rowNumber = 11;
        $rows[] = $this->row($rowNumber, [$this->textCell('A'.$rowNumber, 'A. BASIC LOAD / BUILT-IN', 9)], 18);
        $basicTitleRow = $rowNumber++;
        $basicHeaders = ['Code', 'Descriptive Title', 'Day', 'Time', 'Section', 'Room', "Units\n(Lec)", "Units\n(Lab)", "Total\nUnits", "Total\nHours"];
        $rows[] = $this->row($rowNumber, collect($basicHeaders)->map(
            fn (string $header, int $index): string => $this->textCell($this->column($index + 1).$rowNumber, $header, 10),
        )->all(), 28);
        $rowNumber++;
        $basicStartRow = $rowNumber;

        foreach ($schedules as $schedule) {
            $duration = (strtotime($schedule->end_time) - strtotime($schedule->start_time)) / 3600;
            $hours = $duration * count(ClassSchedule::daysForPattern($schedule->day));
            $units = (float) ($schedule->subject?->units ?? 0);
            $isLaboratory = strcasecmp((string) $schedule->subject?->subject_type, 'Laboratory') === 0;
            $sectionName = $course.'-'.preg_replace('/\s*-\s*/', '', strtoupper((string) $schedule->section?->name));

            $rows[] = $this->row($rowNumber, [
                $this->textCell('A'.$rowNumber, (string) ($schedule->subject?->code ?? 'TBA'), 11),
                $this->textCell('B'.$rowNumber, strtoupper((string) ($schedule->subject?->name ?? 'TBA')), 12),
                $this->textCell('C'.$rowNumber, (string) $schedule->day, 11),
                $this->textCell('D'.$rowNumber, date('g:i A', strtotime($schedule->start_time)).'–'.date('g:i A', strtotime($schedule->end_time)), 11),
                $this->textCell('E'.$rowNumber, $sectionName, 11),
                $this->textCell('F'.$rowNumber, (string) ($schedule->room?->name ?? 'TBA'), 11),
                $this->numberCell('G'.$rowNumber, $isLaboratory ? 0 : $units, 13),
                $this->numberCell('H'.$rowNumber, $isLaboratory ? $units : 0, 13),
                $this->numberCell('I'.$rowNumber, $units, 13),
                $this->numberCell('J'.$rowNumber, $hours, 13),
            ], 21);
            $rowNumber++;
        }

        while ($rowNumber < $basicStartRow + 5) {
            $rows[] = $this->borderedBlankRow($rowNumber, 10);
            $rowNumber++;
        }

        $basicEndRow = $rowNumber - 1;
        $rows[] = $this->row($rowNumber, [
            $this->textCell('A'.$rowNumber, 'Total Number of Units / Hours (Basic)', 14),
            $this->formulaCell('I'.$rowNumber, "SUM(I{$basicStartRow}:I{$basicEndRow})", (float) $report['total_units'], 15),
            $this->formulaCell('J'.$rowNumber, "SUM(J{$basicStartRow}:J{$basicEndRow})", (float) $report['total_hours'], 15),
        ], 19);
        $basicTotalRow = $rowNumber++;

        $rows[] = $this->row($rowNumber, [$this->textCell('A'.$rowNumber, 'B. OTHER ACADEMIC-RELATED FUNCTIONS', 9)], 18);
        $functionsTitleRow = $rowNumber++;
        $functionHeaders = ['Code', 'Descriptive Title', 'Day', 'Time', 'No. of Students', "Units\n(Lec)", "Units\n(Lab)", "Total\nUnits", "Total\nHours"];
        $rows[] = $this->row($rowNumber, [
            ...collect(array_slice($functionHeaders, 0, 7))->map(
                fn (string $header, int $index): string => $this->textCell($this->column($index + 1).$rowNumber, $header, 10),
            )->all(),
            $this->textCell('H'.$rowNumber, $functionHeaders[7], 10),
            $this->textCell('J'.$rowNumber, $functionHeaders[8], 10),
        ], 28);
        $functionsHeaderRow = $rowNumber++;
        $functionsStartRow = $rowNumber;
        for ($index = 0; $index < 4; $index++, $rowNumber++) {
            $rows[] = $this->borderedBlankRow($rowNumber, 10);
        }
        $functionsEndRow = $rowNumber - 1;
        $rows[] = $this->row($rowNumber, [
            $this->textCell('A'.$rowNumber, 'Total Number of Units / Hours (Other Functions)', 14),
            $this->formulaCell('H'.$rowNumber, "SUM(H{$functionsStartRow}:H{$functionsEndRow})", 0, 15),
            $this->formulaCell('J'.$rowNumber, "SUM(J{$functionsStartRow}:J{$functionsEndRow})", 0, 15),
        ], 19);
        $functionsTotalRow = $rowNumber++;

        $rows[] = $this->row($rowNumber, [$this->textCell('A'.$rowNumber, 'C. CONSULTATION HOURS', 9)], 18);
        $consultationTitleRow = $rowNumber++;
        $rows[] = $this->row($rowNumber, [
            $this->textCell('A'.$rowNumber, 'Day', 10),
            $this->textCell('C'.$rowNumber, 'Time', 10),
            $this->textCell('E'.$rowNumber, 'Venue', 10),
            $this->textCell('I'.$rowNumber, 'Number of Hours', 10),
        ], 24);
        $consultationHeaderRow = $rowNumber++;
        $consultationStartRow = $rowNumber;
        for ($index = 0; $index < 4; $index++, $rowNumber++) {
            $rows[] = $this->borderedBlankRow($rowNumber, 10);
        }
        $consultationEndRow = $rowNumber - 1;
        $rows[] = $this->row($rowNumber, [
            $this->textCell('A'.$rowNumber, 'Total Number of Units / Hours (Consultation)', 14),
            $this->formulaCell('I'.$rowNumber, "SUM(I{$consultationStartRow}:I{$consultationEndRow})", 0, 15),
        ], 19);
        $consultationTotalRow = $rowNumber++;

        $rows[] = $this->row($rowNumber, [$this->textCell('A'.$rowNumber, 'D. OVERLOAD', 9)], 18);
        $overloadTitleRow = $rowNumber++;
        $rows[] = $this->row($rowNumber, collect($basicHeaders)->map(
            fn (string $header, int $index): string => $this->textCell($this->column($index + 1).$rowNumber, $header, 10),
        )->all(), 28);
        $overloadHeaderRow = $rowNumber++;
        $overloadStartRow = $rowNumber;
        for ($index = 0; $index < 3; $index++, $rowNumber++) {
            $rows[] = $this->borderedBlankRow($rowNumber, 10);
        }
        $overloadEndRow = $rowNumber - 1;
        $rows[] = $this->row($rowNumber, [
            $this->textCell('A'.$rowNumber, 'Total Number of Units / Hours (Overload)', 14),
            $this->formulaCell('I'.$rowNumber, "SUM(I{$overloadStartRow}:I{$overloadEndRow})", 0, 15),
            $this->formulaCell('J'.$rowNumber, "SUM(J{$overloadStartRow}:J{$overloadEndRow})", 0, 15),
        ], 19);
        $overloadTotalRow = $rowNumber++;
        $rows[] = $this->row($rowNumber, [
            $this->textCell('A'.$rowNumber, 'Grand Total Number of Units / Hours (A–D)', 16),
            $this->formulaCell('I'.$rowNumber, "I{$basicTotalRow}+H{$functionsTotalRow}+I{$overloadTotalRow}", (float) $report['total_units'], 17),
            $this->formulaCell('J'.$rowNumber, "J{$basicTotalRow}+J{$functionsTotalRow}+I{$consultationTotalRow}+J{$overloadTotalRow}", (float) $report['total_hours'], 17),
        ], 20);
        $grandTotalRow = $rowNumber++;

        $rowNumber++;
        $rows[] = $this->row($rowNumber, [
            $this->textCell('A'.$rowNumber, 'Prepared by:', 18),
            $this->textCell('F'.$rowNumber, 'Recommending Approval:', 18),
        ]);
        $rowNumber += 2;
        $rows[] = $this->row($rowNumber, [
            $this->textCell('A'.$rowNumber, strtoupper($dean->name), 19),
            $this->textCell('F'.$rowNumber, 'DR. FLORPISA A. MONTECILLO, LPT', 19),
        ], 19);
        $rows[] = $this->row(++$rowNumber, [
            $this->textCell('A'.$rowNumber, 'Program Head / College Dean', 20),
            $this->textCell('F'.$rowNumber, 'College President', 20),
        ]);
        $rows[] = $this->row(++$rowNumber, [
            $this->textCell('A'.$rowNumber, 'Date Signed: ____________________', 21),
            $this->textCell('F'.$rowNumber, 'Date Signed: ____________________', 21),
        ]);
        $rowNumber += 2;
        $rows[] = $this->row($rowNumber, [
            $this->textCell('A'.$rowNumber, 'Approved by:', 18),
            $this->textCell('F'.$rowNumber, 'Conforme:', 18),
        ]);
        $rowNumber += 2;
        $rows[] = $this->row($rowNumber, [
            $this->textCell('A'.$rowNumber, 'HON. ROMEO A. VILLACERAN', 19),
            $this->textCell('F'.$rowNumber, strtoupper($instructor->name), 19),
        ], 19);
        $rows[] = $this->row(++$rowNumber, [
            $this->textCell('A'.$rowNumber, 'Chairman, Board of Trustees', 20),
            $this->textCell('F'.$rowNumber, 'Instructor', 20),
        ]);
        $rows[] = $this->row(++$rowNumber, [
            $this->textCell('A'.$rowNumber, 'Date Signed: ____________________', 21),
            $this->textCell('F'.$rowNumber, 'Date Signed: ____________________', 21),
        ]);

        $lastRow = $rowNumber;
        $mergeCells = [
            'B1:I1', 'B2:I2', 'B3:I3', 'B4:I4', 'A5:J5', 'A6:J6', 'A7:J7',
            'B9:C9', 'E9:F9', 'A10:B10', 'C10:D10', 'E10:F10', 'G10:H10', 'I10:J10',
            'A'.$basicTitleRow.':J'.$basicTitleRow, 'A'.$basicTotalRow.':H'.$basicTotalRow,
            'A'.$functionsTitleRow.':J'.$functionsTitleRow, 'H'.$functionsHeaderRow.':I'.$functionsHeaderRow,
            'A'.$functionsTotalRow.':G'.$functionsTotalRow, 'H'.$functionsTotalRow.':I'.$functionsTotalRow,
            'A'.$consultationTitleRow.':J'.$consultationTitleRow,
            'A'.$consultationHeaderRow.':B'.$consultationHeaderRow, 'C'.$consultationHeaderRow.':D'.$consultationHeaderRow,
            'E'.$consultationHeaderRow.':H'.$consultationHeaderRow, 'I'.$consultationHeaderRow.':J'.$consultationHeaderRow,
            'A'.$consultationTotalRow.':H'.$consultationTotalRow, 'I'.$consultationTotalRow.':J'.$consultationTotalRow,
            'A'.$overloadTitleRow.':J'.$overloadTitleRow, 'A'.$overloadTotalRow.':H'.$overloadTotalRow,
            'A'.$grandTotalRow.':H'.$grandTotalRow,
        ];

        for ($row = $functionsStartRow; $row <= $functionsEndRow; $row++) {
            $mergeCells[] = 'H'.$row.':I'.$row;
        }
        for ($row = $consultationStartRow; $row <= $consultationEndRow; $row++) {
            $mergeCells[] = 'A'.$row.':B'.$row;
            $mergeCells[] = 'C'.$row.':D'.$row;
            $mergeCells[] = 'E'.$row.':H'.$row;
            $mergeCells[] = 'I'.$row.':J'.$row;
        }
        foreach ([
            [$lastRow - 10, $lastRow - 8, $lastRow - 7, $lastRow - 6],
            [$lastRow - 4, $lastRow - 2, $lastRow - 1, $lastRow],
        ] as [$labelRow, $nameRow, $roleRow, $dateRow]) {
            foreach (['A:E', 'F:J'] as $columns) {
                [$start, $end] = explode(':', $columns);
                $mergeCells[] = $start.$labelRow.':'.$end.$labelRow;
                $mergeCells[] = $start.$nameRow.':'.$end.$nameRow;
                $mergeCells[] = $start.$roleRow.':'.$end.$roleRow;
                $mergeCells[] = $start.$dateRow.':'.$end.$dateRow;
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<dimension ref="A1:J'.$lastRow.'"/>'
            .'<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols><col min="1" max="1" width="10" customWidth="1"/><col min="2" max="2" width="27" customWidth="1"/><col min="3" max="3" width="9" customWidth="1"/><col min="4" max="4" width="17" customWidth="1"/><col min="5" max="5" width="13" customWidth="1"/><col min="6" max="6" width="11" customWidth="1"/><col min="7" max="8" width="8" customWidth="1"/><col min="9" max="10" width="9" customWidth="1"/></cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'<mergeCells count="'.count($mergeCells).'">'.collect($mergeCells)->map(fn (string $range): string => '<mergeCell ref="'.$range.'"/>')->implode('').'</mergeCells>'
            .'<printOptions horizontalCentered="1"/>'
            .'<pageMargins left="0.25" right="0.25" top="0.3" bottom="0.3" header="0.15" footer="0.15"/>'
            .'<pageSetup orientation="portrait" paperSize="9" fitToWidth="1" fitToHeight="1"/>'
            .'<drawing r:id="rId1"/>'
            .'</worksheet>';
    }

    /** @param array<int, string> $cells */
    private function row(int $number, array $cells, ?int $height = null): string
    {
        $attributes = ' r="'.$number.'"'.($height !== null ? ' ht="'.$height.'" customHeight="1"' : '');

        return '<row'.$attributes.'>'.implode('', $cells).'</row>';
    }

    private function textCell(string $reference, string $value, int $style = 0): string
    {
        return '<c r="'.$reference.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.$this->escape($value).'</t></is></c>';
    }

    private function numberCell(string $reference, float $value, int $style = 0): string
    {
        return '<c r="'.$reference.'" s="'.$style.'"><v>'.rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').'</v></c>';
    }

    private function formulaCell(string $reference, string $formula, float $cachedValue, int $style = 0): string
    {
        return '<c r="'.$reference.'" s="'.$style.'"><f>'.$this->escape($formula).'</f><v>'
            .rtrim(rtrim(number_format($cachedValue, 2, '.', ''), '0'), '.').'</v></c>';
    }

    private function borderedBlankRow(int $number, int $columns): string
    {
        $cells = [];
        for ($column = 1; $column <= $columns; $column++) {
            $cells[] = $this->textCell($this->column($column).$number, '', 22);
        }

        return $this->row($number, $cells, 19);
    }

    private function column(int $number): string
    {
        $column = '';
        while ($number > 0) {
            $number--;
            $column = chr(65 + ($number % 26)).$column;
            $number = intdiv($number, 26);
        }

        return $column;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypes(int $sheetCount): string
    {
        $sheets = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sheets .= '<Override PartName="/xl/drawings/drawing'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Default Extension="png" ContentType="image/png"/>'
            .'<Default Extension="jpg" ContentType="image/jpeg"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .$sheets.'</Types>';
    }

    private function worksheetRelationships(int $sheetNumber): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing'.$sheetNumber.'.xml"/>'
            .'</Relationships>';
    }

    private function drawing(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .$this->drawingImage(1, 'MCC Logo', 'rId1', 0, 0, 30, 10, 86, 86)
            .$this->drawingImage(2, 'Department Logo', 'rId2', 8, 0, 0, 10, 86, 86)
            .$this->drawingImage(3, 'Header Divider', 'rId3', 1, 3, 58, 22, 448, 1)
            .'</xdr:wsDr>';
    }

    private function drawingImage(
        int $id,
        string $name,
        string $relationshipId,
        int $column,
        int $row,
        int $columnOffsetPixels,
        int $rowOffsetPixels,
        int $widthPixels,
        int $heightPixels,
    ): string {
        $emusPerPixel = 9525;
        $columnOffset = $columnOffsetPixels * $emusPerPixel;
        $rowOffset = $rowOffsetPixels * $emusPerPixel;
        $width = $widthPixels * $emusPerPixel;
        $height = $heightPixels * $emusPerPixel;

        return '<xdr:oneCellAnchor>'
            .'<xdr:from><xdr:col>'.$column.'</xdr:col><xdr:colOff>'.$columnOffset.'</xdr:colOff><xdr:row>'.$row.'</xdr:row><xdr:rowOff>'.$rowOffset.'</xdr:rowOff></xdr:from>'
            .'<xdr:ext cx="'.$width.'" cy="'.$height.'"/>'
            .'<xdr:pic><xdr:nvPicPr><xdr:cNvPr id="'.$id.'" name="'.$this->escape($name).'"/><xdr:cNvPicPr/></xdr:nvPicPr>'
            .'<xdr:blipFill><a:blip r:embed="'.$relationshipId.'"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
            .'<xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/>'
            .'</xdr:oneCellAnchor>';
    }

    private function drawingRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/mcc-logo.png"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/department-logo.jpg"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/header-divider.png"/>'
            .'</Relationships>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    /** @param array<int, string> $sheetNames */
    private function applicationProperties(array $sheetNames): string
    {
        $titles = collect($sheetNames)->map(fn (string $name): string => '<vt:lpstr>'.$this->escape($name).'</vt:lpstr>')->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>MCC Scheduler</Application><HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>'.count($sheetNames).'</vt:i4></vt:variant></vt:vector></HeadingPairs>'
            .'<TitlesOfParts><vt:vector size="'.count($sheetNames).'" baseType="lpstr">'.$titles.'</vt:vector></TitlesOfParts></Properties>';
    }

    private function coreProperties(): string
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>Individual Faculty Load Sheets</dc:title><dc:creator>MCC Scheduler</dc:creator><cp:lastModifiedBy>MCC Scheduler</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:modified></cp:coreProperties>';
    }

    /** @param array<int, string> $sheetNames */
    private function workbook(array $sheetNames): string
    {
        $sheets = collect($sheetNames)->map(
            fn (string $name, int $index): string => '<sheet name="'.$this->escape($name).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>',
        )->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="14000"/></bookViews><sheets>'.$sheets.'</sheets><calcPr calcId="191029"/></workbook>';
    }

    private function workbookRelationships(int $sheetCount): string
    {
        $relationships = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $relationships .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
        }
        $relationships .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relationships.'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="0.##"/></numFmts>'
            .'<fonts count="11">'
            .'<font><sz val="8"/><name val="Arial"/></font>'
            .'<font><b/><sz val="18"/><name val="Georgia"/></font>'
            .'<font><b/><sz val="14"/><name val="Georgia"/></font>'
            .'<font><sz val="9"/><name val="Georgia"/></font>'
            .'<font><b/><sz val="11"/><name val="Georgia"/></font>'
            .'<font><sz val="10"/><name val="Georgia"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Georgia"/></font>'
            .'<font><b/><sz val="8"/><name val="Georgia"/></font>'
            .'<font><sz val="8"/><name val="Arial"/></font>'
            .'<font><b/><sz val="8"/><name val="Arial"/></font>'
            .'<font><b/><sz val="9"/><name val="Georgia"/></font>'
            .'</fonts>'
            .'<fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF202020"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF5F5F5"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE3EFD9"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="4">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color rgb="FF222222"/></left><right style="thin"><color rgb="FF222222"/></right><top style="thin"><color rgb="FF222222"/></top><bottom style="thin"><color rgb="FF222222"/></bottom><diagonal/></border>'
            .'<border><left/><right/><top/><bottom style="thin"><color rgb="FF111111"/></bottom><diagonal/></border>'
            .'<border><left/><right/><top style="thin"><color rgb="FF111111"/></top><bottom/><diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="24">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="6" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="9" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="9" fillId="0" borderId="2" xfId="0" applyAlignment="1"><alignment vertical="bottom"/></xf>'
            .'<xf numFmtId="0" fontId="7" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="7" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="8" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="8" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="164" fontId="8" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="9" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="9" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="9" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="9" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="7" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="10" fillId="0" borderId="3" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="bottom"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="8" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="8" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
