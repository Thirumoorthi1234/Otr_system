<?php
/**
 * trainer/export_skill_matrix.php
 * Export Skill Matrix as Excel (.xlsx) — Syrma SGS Format
 * Uses PhpSpreadsheet for proper formatting with merged cells, borders, and colors.
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) session_start();
checkRole('trainer');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Color;

$trainer_id = $_SESSION['user_id'];
$report_id  = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

if (!$report_id) { die('No report selected.'); }

// ── Load Report ──
$stmt = $pdo->prepare("SELECT * FROM skill_matrix_reports WHERE id = ? AND trainer_id = ?");
$stmt->execute([$report_id, $trainer_id]);
$report = $stmt->fetch();
if (!$report) { die('Report not found.'); }

// ── Load Skills ──
$stmt = $pdo->prepare("SELECT * FROM skill_matrix_skills WHERE report_id = ? ORDER BY sort_order ASC");
$stmt->execute([$report_id]);
$skills = $stmt->fetchAll();

// Organize by category
$processSkills = [];
$operatingSkills = [];
$processSubCats = [];

foreach ($skills as $sk) {
    if ($sk['category_group'] === 'Process Knowledge') {
        $processSkills[] = $sk;
        $sub = $sk['sub_category'] ?: 'Other';
        if (!isset($processSubCats[$sub])) $processSubCats[$sub] = [];
        $processSubCats[$sub][] = $sk;
    } else {
        $operatingSkills[] = $sk;
    }
}

// ── Load Trainees ──
$trainees = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name, u.employee_id, u.department
    FROM assignments a
    JOIN users u ON a.trainee_id = u.id
    WHERE a.trainer_id = ? AND u.status = 'active'
    ORDER BY u.full_name ASC
");
$trainees->execute([$trainer_id]);
$trainees = $trainees->fetchAll();

// ── Load Scores ──
$scoreMap = [];
$stmt = $pdo->prepare("SELECT trainee_id, skill_id, score FROM skill_matrix_entries WHERE report_id = ?");
$stmt->execute([$report_id]);
foreach ($stmt->fetchAll() as $row) {
    $scoreMap[$row['trainee_id']][$row['skill_id']] = $row['score'];
}

// ═══════════════════════════════════════
// BUILD EXCEL
// ═══════════════════════════════════════
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Skill Matrix');

// ── Color constants ──
$darkBlue    = '1E3A8A';
$medBlue     = '1E40AF';
$lightBlue   = '3B82F6';
$paleBlue    = 'DBEAFE';
$veryPaleBlue= 'EFF6FF';
$amber       = 'F59E0B';
$darkAmber   = 'B45309';
$green       = '059669';
$darkGreen   = '064E3B';
$purple      = '7C3AED';
$white       = 'FFFFFF';
$black       = '000000';
$lightGray   = 'F1F5F9';
$borderColor = 'CBD5E1';

// ── Helper: Apply style to range ──
function applyStyle($sheet, $range, $opts) {
    $style = $sheet->getStyle($range);
    if (isset($opts['bold'])) $style->getFont()->setBold($opts['bold']);
    if (isset($opts['size'])) $style->getFont()->setSize($opts['size']);
    if (isset($opts['color'])) $style->getFont()->getColor()->setRGB($opts['color']);
    if (isset($opts['bg'])) {
        $style->getFill()->setFillType(Fill::FILL_SOLID);
        $style->getFill()->getStartColor()->setRGB($opts['bg']);
    }
    if (isset($opts['halign'])) $style->getAlignment()->setHorizontal($opts['halign']);
    if (isset($opts['valign'])) $style->getAlignment()->setVertical($opts['valign']);
    if (isset($opts['wrap'])) $style->getAlignment()->setWrapText($opts['wrap']);
    if (isset($opts['border'])) {
        $borderStyle = Border::BORDER_THIN;
        $borderColor = $opts['borderColor'] ?? 'CBD5E1';
        $style->getBorders()->getAllBorders()->setBorderStyle($borderStyle);
        $style->getBorders()->getAllBorders()->getColor()->setRGB($borderColor);
    }
    if (isset($opts['rotation'])) $style->getAlignment()->setTextRotation($opts['rotation']);
}

$totalSkills = count($processSkills) + count($operatingSkills);
$fixedCols = 3; // S.No, Emp no., Name
$idxCols = 3;   // Total Process, Total Operating, Individual
$totalCols = $fixedCols + $totalSkills + $idxCols;

// Column letter helper
function colLetter($colIndex) {
    $letter = '';
    while ($colIndex >= 0) {
        $letter = chr(65 + ($colIndex % 26)) . $letter;
        $colIndex = intdiv($colIndex, 26) - 1;
    }
    return $letter;
}
$lastCol = colLetter($totalCols - 1);

// ═══ ROW 1: Company Header ═══
$row = 1;
$sheet->mergeCells("A{$row}:C{$row}");
$sheet->setCellValue("A{$row}", "SYRMA SGS");
applyStyle($sheet, "A{$row}:C{$row}", ['bold' => true, 'size' => 14, 'color' => $medBlue, 'halign' => Alignment::HORIZONTAL_LEFT, 'valign' => Alignment::VERTICAL_CENTER]);

$titleStart = colLetter($fixedCols);
$titleEnd = colLetter($fixedCols + $totalSkills - 1);
$sheet->mergeCells("{$titleStart}{$row}:{$titleEnd}{$row}");
$sheet->setCellValue("{$titleStart}{$row}", $report['report_title']);
applyStyle($sheet, "{$titleStart}{$row}:{$titleEnd}{$row}", ['bold' => true, 'size' => 16, 'color' => $black, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER]);

$sheet->getRowDimension($row)->setRowHeight(30);

// ═══ ROW 2: Metadata Fields ═══
$row = 2;
$sheet->setCellValue("A{$row}", "Date");
$sheet->setCellValue("B{$row}", date('d.m.Y', strtotime($report['report_date'])));
applyStyle($sheet, "A{$row}", ['bold' => true, 'size' => 9, 'color' => $medBlue]);
applyStyle($sheet, "B{$row}", ['bold' => true, 'size' => 9]);

// Site Name
$siteCol = colLetter(3);
$siteValCol = colLetter(4);
$sheet->setCellValue("{$siteCol}{$row}", "Site Name");
$sheet->setCellValue("{$siteValCol}{$row}", $report['site_name']);
applyStyle($sheet, "{$siteCol}{$row}", ['bold' => true, 'size' => 9, 'color' => $medBlue]);
applyStyle($sheet, "{$siteValCol}{$row}", ['bold' => true, 'size' => 9]);

// Department
$deptCol = colLetter(6);
$deptValCol = colLetter(7);
$sheet->setCellValue("{$deptCol}{$row}", "Department");
$sheet->setCellValue("{$deptValCol}{$row}", $report['department']);
applyStyle($sheet, "{$deptCol}{$row}", ['bold' => true, 'size' => 9, 'color' => $medBlue]);
applyStyle($sheet, "{$deptValCol}{$row}", ['bold' => true, 'size' => 9]);

// Supervisor
$supCol = colLetter(9);
$supValCol = colLetter(10);
if ($totalCols > 10) {
    $sheet->setCellValue("{$supCol}{$row}", "Supervisor Name");
    $sheet->setCellValue("{$supValCol}{$row}", $report['supervisor_name']);
    applyStyle($sheet, "{$supCol}{$row}", ['bold' => true, 'size' => 9, 'color' => $medBlue]);
    applyStyle($sheet, "{$supValCol}{$row}", ['bold' => true, 'size' => 9]);
}

$sheet->getRowDimension($row)->setRowHeight(20);

// ═══ ROW 3: Blank separator ═══
$row = 3;
$sheet->getRowDimension($row)->setRowHeight(6);

// ═══ ROW 4: Category Group Headers ═══
$row = 4;
// S.No, Emp no., Name — merged down 3 rows
$sheet->mergeCells("A{$row}:A" . ($row + 2));
$sheet->setCellValue("A{$row}", "S.No");
applyStyle($sheet, "A{$row}:A" . ($row + 2), ['bold' => true, 'size' => 9, 'color' => $white, 'bg' => $darkBlue, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $white]);

$sheet->mergeCells("B{$row}:B" . ($row + 2));
$sheet->setCellValue("B{$row}", "Emp no.");
applyStyle($sheet, "B{$row}:B" . ($row + 2), ['bold' => true, 'size' => 9, 'color' => $white, 'bg' => $darkBlue, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $white]);

$sheet->mergeCells("C{$row}:C" . ($row + 2));
$sheet->setCellValue("C{$row}", "Name");
applyStyle($sheet, "C{$row}:C" . ($row + 2), ['bold' => true, 'size' => 9, 'color' => $white, 'bg' => $darkBlue, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $white]);

$colIdx = $fixedCols; // Start after fixed columns

// Process Knowledge header
if (count($processSkills) > 0) {
    $startLetter = colLetter($colIdx);
    $endLetter = colLetter($colIdx + count($processSkills) - 1);
    $sheet->mergeCells("{$startLetter}{$row}:{$endLetter}{$row}");
    $sheet->setCellValue("{$startLetter}{$row}", "Process Knowledge");
    applyStyle($sheet, "{$startLetter}{$row}:{$endLetter}{$row}", ['bold' => true, 'size' => 10, 'color' => $white, 'bg' => $medBlue, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $white]);
}

$opStartIdx = $colIdx + count($processSkills);

// Operating Knowledge header
if (count($operatingSkills) > 0) {
    $startLetter = colLetter($opStartIdx);
    $endLetter = colLetter($opStartIdx + count($operatingSkills) - 1);
    $sheet->mergeCells("{$startLetter}{$row}:{$endLetter}{$row}");
    $sheet->setCellValue("{$startLetter}{$row}", "Operating Knowledge");
    applyStyle($sheet, "{$startLetter}{$row}:{$endLetter}{$row}", ['bold' => true, 'size' => 10, 'color' => $white, 'bg' => $darkAmber, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $white]);
}

$idxStartIdx = $opStartIdx + count($operatingSkills);

// Skill Index header
$startLetter = colLetter($idxStartIdx);
$endLetter = colLetter($idxStartIdx + 2);
$sheet->mergeCells("{$startLetter}{$row}:{$endLetter}{$row}");
$sheet->setCellValue("{$startLetter}{$row}", "Skill Index");
applyStyle($sheet, "{$startLetter}{$row}:{$endLetter}{$row}", ['bold' => true, 'size' => 10, 'color' => $white, 'bg' => $purple, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $white]);

$sheet->getRowDimension($row)->setRowHeight(22);

// ═══ ROW 5: Sub-Category Headers ═══
$row = 5;
$colIdx = $fixedCols;

foreach ($processSubCats as $subName => $subSkills) {
    $startLetter = colLetter($colIdx);
    $endLetter = colLetter($colIdx + count($subSkills) - 1);
    if (count($subSkills) > 1) {
        $sheet->mergeCells("{$startLetter}{$row}:{$endLetter}{$row}");
    }
    $sheet->setCellValue("{$startLetter}{$row}", $subName);
    applyStyle($sheet, "{$startLetter}{$row}:{$endLetter}{$row}", ['bold' => true, 'size' => 8, 'color' => $white, 'bg' => $lightBlue, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $white, 'wrap' => true]);
    $colIdx += count($subSkills);
}

// Operating Knowledge sub-category row (blank or merged)
if (count($operatingSkills) > 0) {
    $startLetter = colLetter($opStartIdx);
    $endLetter = colLetter($opStartIdx + count($operatingSkills) - 1);
    if (count($operatingSkills) > 1) {
        $sheet->mergeCells("{$startLetter}{$row}:{$endLetter}{$row}");
    }
    $sheet->setCellValue("{$startLetter}{$row}", "");
    applyStyle($sheet, "{$startLetter}{$row}:{$endLetter}{$row}", ['bg' => $amber, 'border' => true, 'borderColor' => $white]);
}

// Index sub-headers
$idxLabels = ['Total Process skill', 'Total Operating skill', 'Individual Skill Index'];
$idxBgs    = [$green, $amber, $purple];
$idxColors = [$white, $black, $white];
for ($i = 0; $i < 3; $i++) {
    $letter = colLetter($idxStartIdx + $i);
    $sheet->mergeCells("{$letter}{$row}:{$letter}" . ($row + 1));
    $sheet->setCellValue("{$letter}{$row}", $idxLabels[$i]);
    applyStyle($sheet, "{$letter}{$row}:{$letter}" . ($row + 1), [
        'bold' => true, 'size' => 8, 'color' => $idxColors[$i], 'bg' => $idxBgs[$i],
        'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER,
        'border' => true, 'borderColor' => $white, 'wrap' => true
    ]);
}

$sheet->getRowDimension($row)->setRowHeight(22);

// ═══ ROW 6: Individual Skill Names ═══
$row = 6;
$colIdx = $fixedCols;

foreach ($processSkills as $sk) {
    $letter = colLetter($colIdx);
    $sheet->setCellValue("{$letter}{$row}", $sk['skill_name']);
    applyStyle($sheet, "{$letter}{$row}", [
        'bold' => true, 'size' => 8, 'color' => $darkBlue, 'bg' => $veryPaleBlue,
        'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_BOTTOM,
        'border' => true, 'borderColor' => $borderColor, 'rotation' => 90, 'wrap' => true
    ]);
    $colIdx++;
}

foreach ($operatingSkills as $sk) {
    $letter = colLetter($colIdx);
    $sheet->setCellValue("{$letter}{$row}", $sk['skill_name']);
    applyStyle($sheet, "{$letter}{$row}", [
        'bold' => true, 'size' => 8, 'color' => $darkAmber, 'bg' => 'FEF3C7',
        'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_BOTTOM,
        'border' => true, 'borderColor' => $borderColor, 'rotation' => 90, 'wrap' => true
    ]);
    $colIdx++;
}

$sheet->getRowDimension($row)->setRowHeight(100);

// ═══ ROW 7: Required Skill Levels ═══
$row = 7;
$sheet->setCellValue("A{$row}", "");
$sheet->setCellValue("B{$row}", "");
$sheet->setCellValue("C{$row}", "Required Skill Levels");
applyStyle($sheet, "A{$row}:C{$row}", ['bold' => true, 'size' => 8, 'color' => $medBlue, 'bg' => $paleBlue, 'halign' => Alignment::HORIZONTAL_LEFT, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor]);

$colIdx = $fixedCols;
foreach ($processSkills as $sk) {
    $letter = colLetter($colIdx);
    $sheet->setCellValue("{$letter}{$row}", $sk['required_level']);
    applyStyle($sheet, "{$letter}{$row}", ['bold' => true, 'size' => 10, 'color' => $medBlue, 'bg' => $paleBlue, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor]);
    $colIdx++;
}
foreach ($operatingSkills as $sk) {
    $letter = colLetter($colIdx);
    $sheet->setCellValue("{$letter}{$row}", $sk['required_level']);
    applyStyle($sheet, "{$letter}{$row}", ['bold' => true, 'size' => 10, 'color' => $medBlue, 'bg' => $paleBlue, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor]);
    $colIdx++;
}

// Index totals for required row
$processMaxTotal  = array_sum(array_column($processSkills, 'required_level'));
$opMaxTotal       = array_sum(array_column($operatingSkills, 'required_level'));

$letter = colLetter($idxStartIdx);
$sheet->setCellValue("{$letter}{$row}", $processMaxTotal);
applyStyle($sheet, "{$letter}{$row}", ['bold' => true, 'size' => 10, 'color' => $darkGreen, 'bg' => 'D1FAE5', 'halign' => Alignment::HORIZONTAL_CENTER, 'border' => true, 'borderColor' => $borderColor]);

$letter = colLetter($idxStartIdx + 1);
$sheet->setCellValue("{$letter}{$row}", $opMaxTotal);
applyStyle($sheet, "{$letter}{$row}", ['bold' => true, 'size' => 10, 'color' => $darkAmber, 'bg' => 'FEF3C7', 'halign' => Alignment::HORIZONTAL_CENTER, 'border' => true, 'borderColor' => $borderColor]);

$letter = colLetter($idxStartIdx + 2);
$sheet->setCellValue("{$letter}{$row}", 100);
applyStyle($sheet, "{$letter}{$row}", ['bold' => true, 'size' => 10, 'color' => $purple, 'bg' => 'EDE9FE', 'halign' => Alignment::HORIZONTAL_CENTER, 'border' => true, 'borderColor' => $borderColor]);

$sheet->getRowDimension($row)->setRowHeight(20);

// ═══ DATA ROWS ═══
$dataStartRow = 8;
$sno = 0;
foreach ($trainees as $t) {
    $sno++;
    $row = $dataStartRow + $sno - 1;
    $bgColor = ($sno % 2 === 0) ? $lightGray : $white;
    
    // S.No
    $sheet->setCellValue("A{$row}", $sno);
    applyStyle($sheet, "A{$row}", ['bold' => true, 'size' => 9, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor, 'bg' => $bgColor]);
    
    // Emp No
    $sheet->setCellValue("B{$row}", $t['employee_id']);
    applyStyle($sheet, "B{$row}", ['bold' => true, 'size' => 9, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor, 'bg' => $bgColor]);
    
    // Name
    $sheet->setCellValue("C{$row}", $t['full_name']);
    applyStyle($sheet, "C{$row}", ['bold' => true, 'size' => 9, 'halign' => Alignment::HORIZONTAL_LEFT, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor, 'bg' => $bgColor]);
    
    // Process Skills
    $processTotal = 0;
    $colIdx = $fixedCols;
    foreach ($processSkills as $sk) {
        $letter = colLetter($colIdx);
        $score = $scoreMap[$t['id']][$sk['id']] ?? null;
        if ($score !== null) {
            $sheet->setCellValue("{$letter}{$row}", (int)$score);
            $processTotal += (int)$score;
            // Color coding
            $scoreColor = $black;
            if ($score == 4) $scoreColor = $green;
            elseif ($score == 3) $scoreColor = '0284C7';
            elseif ($score == 2) $scoreColor = 'D97706';
            elseif ($score == 1) $scoreColor = 'DC2626';
            applyStyle($sheet, "{$letter}{$row}", ['bold' => true, 'size' => 10, 'color' => $scoreColor, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor, 'bg' => $bgColor]);
        } else {
            $sheet->setCellValue("{$letter}{$row}", "NA");
            applyStyle($sheet, "{$letter}{$row}", ['size' => 8, 'color' => '94A3B8', 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor, 'bg' => $bgColor]);
        }
        $colIdx++;
    }
    
    // Operating Skills
    $opTotal = 0;
    foreach ($operatingSkills as $sk) {
        $letter = colLetter($colIdx);
        $score = $scoreMap[$t['id']][$sk['id']] ?? null;
        if ($score !== null) {
            $sheet->setCellValue("{$letter}{$row}", (int)$score);
            $opTotal += (int)$score;
            $scoreColor = $black;
            if ($score == 4) $scoreColor = $green;
            elseif ($score == 3) $scoreColor = '0284C7';
            elseif ($score == 2) $scoreColor = 'D97706';
            elseif ($score == 1) $scoreColor = 'DC2626';
            applyStyle($sheet, "{$letter}{$row}", ['bold' => true, 'size' => 10, 'color' => $scoreColor, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor, 'bg' => $bgColor]);
        } else {
            $sheet->setCellValue("{$letter}{$row}", "NA");
            applyStyle($sheet, "{$letter}{$row}", ['size' => 8, 'color' => '94A3B8', 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor, 'bg' => $bgColor]);
        }
        $colIdx++;
    }
    
    // Skill Index values
    $totalMax = $processMaxTotal + $opMaxTotal;
    $totalScore = $processTotal + $opTotal;
    $skillIndex = $totalMax > 0 ? round(($totalScore / $totalMax) * 100) : 0;
    
    // Total Process
    $letter = colLetter($idxStartIdx);
    $sheet->setCellValue("{$letter}{$row}", $processTotal);
    applyStyle($sheet, "{$letter}{$row}", ['bold' => true, 'size' => 10, 'color' => $green, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor, 'bg' => $bgColor]);
    
    // Total Operating
    $letter = colLetter($idxStartIdx + 1);
    $sheet->setCellValue("{$letter}{$row}", $opTotal);
    applyStyle($sheet, "{$letter}{$row}", ['bold' => true, 'size' => 10, 'color' => $darkAmber, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor, 'bg' => $bgColor]);
    
    // Individual Skill Index
    $letter = colLetter($idxStartIdx + 2);
    $sheet->setCellValue("{$letter}{$row}", $skillIndex);
    $idxColor = $purple;
    if ($skillIndex >= 80) $idxColor = $green;
    elseif ($skillIndex >= 60) $idxColor = 'D97706';
    else $idxColor = 'DC2626';
    applyStyle($sheet, "{$letter}{$row}", ['bold' => true, 'size' => 11, 'color' => $idxColor, 'halign' => Alignment::HORIZONTAL_CENTER, 'valign' => Alignment::VERTICAL_CENTER, 'border' => true, 'borderColor' => $borderColor, 'bg' => $bgColor]);
    
    $sheet->getRowDimension($row)->setRowHeight(22);
}

// ═══ Column Widths ═══
$sheet->getColumnDimension('A')->setWidth(5);   // S.No
$sheet->getColumnDimension('B')->setWidth(10);  // Emp No
$sheet->getColumnDimension('C')->setWidth(18);  // Name

$colIdx = $fixedCols;
for ($i = 0; $i < $totalSkills; $i++) {
    $letter = colLetter($colIdx + $i);
    $sheet->getColumnDimension($letter)->setWidth(5);
}
for ($i = 0; $i < 3; $i++) {
    $letter = colLetter($idxStartIdx + $i);
    $sheet->getColumnDimension($letter)->setWidth(8);
}

// ═══ Freeze Panes ═══
$freezeCol = colLetter($fixedCols);
$sheet->freezePane("{$freezeCol}" . ($dataStartRow));

// ═══ Print settings ═══
$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A3);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

// ═══ Output ═══
ob_end_clean();
$fileName = 'Skill_Matrix_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $report['report_title']) . '_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
