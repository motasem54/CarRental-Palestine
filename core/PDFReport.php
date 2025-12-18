<?php

/**
 * PDF Report Generator
 * Generates PDF reports from data
 */
class PDFReport {
    
    /**
     * Generate PDF report (HTML version)
     */
    public function generate($title, $columns, $data, $startDate, $endDate) {
        // Set headers
        header('Content-Type: text/html; charset=UTF-8');
        
        $html = '
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #FF5722;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #FF5722;
        }
        .report-title {
            font-size: 24px;
            margin: 20px 0;
        }
        .report-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: right;
        }
        table th {
            background: #FF5722;
            color: white;
            font-weight: bold;
        }
        .total-row {
            background: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 2px solid #ddd;
            padding-top: 20px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">' . SITE_NAME . '</div>
        <div>' . COMPANY_ADDRESS . '</div>
        <div>هاتف: ' . COMPANY_PHONE . ' | ' . COMPANY_EMAIL . '</div>
    </div>
    
    <div class="report-title" style="text-align: center;">
        <strong>' . $title . '</strong>
    </div>
    
    <div class="report-info">
        <strong>الفترة:</strong> من ' . formatDate($startDate, 'd/m/Y') . ' إلى ' . formatDate($endDate, 'd/m/Y') . '<br>
        <strong>تاريخ الإنشاء:</strong> ' . date('d/m/Y H:i:s') . '<br>
        <strong>عدد السجلات:</strong> ' . count($data) . '
    </div>
    
    <table>
        <thead>
            <tr>';
        
        foreach ($columns as $col) {
            $html .= '<th>' . $col . '</th>';
        }
        
        $html .= '
            </tr>
        </thead>
        <tbody>';
        
        $totals = [];
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $i => $cell) {
                $value = $cell;
                // Format numbers
                if (is_numeric($cell) && $i > 0 && strpos($cell, '%') === false) {
                    $value = number_format($cell, 2) . ' ₪';
                    if (!isset($totals[$i])) $totals[$i] = 0;
                    $totals[$i] += $cell;
                }
                $html .= '<td>' . $value . '</td>';
            }
            $html .= '</tr>';
        }
        
        // Add totals row
        if (!empty($totals)) {
            $html .= '<tr class="total-row">';
            $html .= '<td>الإجمالي</td>';
            for ($i = 1; $i < count($columns); $i++) {
                if (isset($totals[$i])) {
                    $html .= '<td>' . number_format($totals[$i], 2) . ' ₪</td>';
                } else {
                    $html .= '<td>-</td>';
                }
            }
            $html .= '</tr>';
        }
        
        $html .= '
        </tbody>
    </table>
    
    <div class="footer">
        <p><strong>' . SITE_NAME . '</strong></p>
        <p>' . COMPANY_ADDRESS . ' | ' . COMPANY_PHONE . ' | ' . COMPANY_EMAIL . '</p>
        <p>🇵🇸 صُنع بكل حب في فلسطين</p>
    </div>
    
    <div class="no-print" style="text-align: center; margin: 30px 0;">
        <button onclick="window.print()" style="padding: 15px 30px; background: #FF5722; color: white; border: none; border-radius: 10px; font-size: 16px; cursor: pointer;">
            <i class="fas fa-print"></i> طباعة / حفظ PDF
        </button>
        <button onclick="window.close()" style="padding: 15px 30px; background: #666; color: white; border: none; border-radius: 10px; font-size: 16px; cursor: pointer; margin-right: 10px;">
            إغلاق
        </button>
    </div>
</body>
</html>';
        
        echo $html;
    }
}
?>