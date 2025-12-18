<?php

/**
 * Excel Export Class
 * Simple CSV export that opens in Excel
 */
class ExcelExport {
    
    /**
     * Export data to Excel (CSV format)
     */
    public function export($title, $columns, $data, $filename) {
        // Set headers for Excel
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for UTF-8
        echo "\xEF\xBB\xBF";
        
        // Start HTML table
        echo '<html dir="rtl">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        echo '<h2>' . $title . '</h2>';
        echo '<p>التاريخ: ' . date('d/m/Y H:i:s') . '</p>';
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        
        // Header
        echo '<thead style="background: #FF5722; color: white;">';
        echo '<tr>';
        foreach ($columns as $col) {
            echo '<th style="padding: 10px; text-align: right;">' . $col . '</th>';
        }
        echo '</tr>';
        echo '</thead>';
        
        // Data
        echo '<tbody>';
        $totals = [];
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $i => $cell) {
                $value = $cell;
                // Format currency
                if (is_numeric($cell) && $i > 0) {
                    $value = number_format($cell, 2) . ' ₪';
                    if (!isset($totals[$i])) $totals[$i] = 0;
                    $totals[$i] += $cell;
                }
                echo '<td style="padding: 8px; text-align: right;">' . $value . '</td>';
            }
            echo '</tr>';
        }
        
        // Totals row
        if (!empty($totals)) {
            echo '<tr style="background: #f5f5f5; font-weight: bold;">';
            echo '<td style="padding: 10px;">الإجمالي</td>';
            for ($i = 1; $i < count($columns); $i++) {
                if (isset($totals[$i])) {
                    echo '<td style="padding: 10px; text-align: right;">' . number_format($totals[$i], 2) . ' ₪</td>';
                } else {
                    echo '<td></td>';
                }
            }
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '<p style="margin-top: 20px; color: #666; font-size: 12px;">';
        echo SITE_NAME . ' | تم الإنشاء: ' . date('d/m/Y H:i') . '</p>';
        echo '</body>';
        echo '</html>';
    }
    
    /**
     * Export to pure CSV format
     */
    public function exportCSV($columns, $data, $filename) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        // Add BOM for UTF-8
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // Header
        fputcsv($output, $columns);
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
}
?>