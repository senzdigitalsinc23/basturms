<?php
/**
 * CSV Parsing Test Script
 * Usage: php test_csv_parse.php <path_to_csv_file>
 */

if ($argc < 2) {
    echo "Usage: php test_csv_parse.php <path_to_csv_file>\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    echo "File not found: $filePath\n";
    exit(1);
}

echo "Parsing CSV file: $filePath\n";
echo str_repeat("=", 80) . "\n\n";

$handle = fopen($filePath, 'r');
$rowIndex = 0;
$header = null;

while (($row = fgetcsv($handle, 10000, ',', '"', '\\')) !== false) {
    if ($rowIndex === 0) {
        // Header row
        echo "HEADER ROW (Raw):\n";
        print_r($row);
        echo "\n";
        
        // Clean headers
        $header = array_map(function($col) {
            $col = trim($col);
            $col = str_replace("\xEF\xBB\xBF", '', $col); // Remove UTF-8 BOM
            $col = preg_replace('/\s+/', ' ', $col);
            return $col;
        }, $row);
        
        echo "HEADER ROW (Cleaned):\n";
        print_r($header);
        echo "\n";
        echo "Number of columns: " . count($header) . "\n";
        echo str_repeat("-", 80) . "\n\n";
        
    } elseif ($rowIndex <= 3) {
        // Show first 3 data rows
        echo "DATA ROW $rowIndex:\n";
        echo "Number of columns: " . count($row) . "\n";
        
        if (count($row) !== count($header)) {
            echo "WARNING: Column count mismatch! Expected " . count($header) . " but got " . count($row) . "\n";
        }
        
        // Combine with header
        if (count($row) === count($header)) {
            $combined = array_combine($header, array_map('trim', $row));
            echo "\nCombined data:\n";
            foreach ($combined as $key => $value) {
                $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                echo "  [$key] => '$displayValue'\n";
            }
        } else {
            echo "Raw row data:\n";
            print_r($row);
        }
        
        echo "\n" . str_repeat("-", 80) . "\n\n";
    }
    
    $rowIndex++;
}

fclose($handle);

echo "Total rows (including header): $rowIndex\n";
echo "Data rows: " . ($rowIndex - 1) . "\n";
