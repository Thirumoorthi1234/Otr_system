<?php
// admin/export_emp_master.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

checkRole(['admin', 'management']);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Emp Master');

// Set Title Row (matches the screenshot showing 'Employee Generated Date...')
$currentDateTime = date('d-M-Y H:i A');
$sheet->setCellValue('A1', "Employee Generated Date: " . $currentDateTime);
$sheet->mergeCells('A1:X1');
$sheet->getStyle('A1')->getFont()->setBold(true);

// Header data
$headers = [
    'S.No', 'Employee Code', 'Old Emp Id', 'Full Name', 'Reporting To Code', 'Reporting To Name',
    'Date Of Joining', 'Date Of Re joining', 'Uid Emp No', 'Business', 'Region', 'Location',
    'Work Location', 'Org Unit - 1', 'Org Unit - 2', 'Roll', 'Category', 'Sub Category',
    'Payroll Group', 'Gender', 'Mob No', 'Aadhar No', 'Education Details', 'Home Town'
];

$column = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($column . '2', $header);
    $column++;
}

// Format Headers
$lastColumn = $sheet->getHighestColumn();
$headerRange = 'A2:' . $lastColumn . '2';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'argb' => 'FFEFEFEF', // Light gray background
        ],
    ],
]);

// Auto size columns (mostly)
foreach (range('A', $lastColumn) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Query the database
$stmt = $pdo->query("SELECT * FROM users WHERE status = 'active' ORDER BY full_name ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rowNum = 3;
$sno = 1;

foreach ($users as $user) {
    // Format dates
    $doj = !empty($user['doj']) ? date('d-m-Y', strtotime($user['doj'])) : '';
    $dorj = !empty($user['date_of_re_joining']) ? date('d-m-Y', strtotime($user['date_of_re_joining'])) : '';

    $rowData = [
        $sno++,
        $user['employee_id'],
        $user['old_emp_id'] ?? '',
        $user['full_name'],
        $user['reporting_to_code'] ?? '',
        $user['reporting_to_name'] ?? '',
        $doj,
        $dorj,
        $user['uid_emp_no'] ?? '',
        $user['business'] ?? '',
        $user['region'] ?? '',
        $user['location'] ?? '',
        $user['work_location'] ?? '',
        $user['org_unit_1'] ?? '',
        $user['org_unit_2'] ?? '',
        $user['roll'] ?? '',
        $user['category'] ?? '',
        $user['sub_category'] ?? '',
        $user['payroll_group'] ?? '',
        $user['gender'] ?? '',
        $user['mobile_number'] ?? '',
        $user['aadhar_number'] ?? '',
        $user['qualification'] ?? '',
        $user['home_town'] ?? ''
    ];

    $col = 'A';
    foreach ($rowData as $value) {
        $sheet->setCellValueExplicit($col . $rowNum, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $col++;
    }

    $rowNum++;
}

// Add borders to data rows
if ($rowNum > 3) {
    $dataRange = 'A3:' . $lastColumn . ($rowNum - 1);
    $sheet->getStyle($dataRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);
}

// Export as Excel
$filename = "Employee_Master_" . date('Y_m_d_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
