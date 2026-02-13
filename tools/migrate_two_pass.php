<?php
/**
 * Two-pass migrator:
 * 1) Create all tables by executing CREATE TABLE statements with FOREIGN KEY clauses removed.
 * 2) After tables exist, add the foreign keys using ALTER TABLE statements extracted from the migrations.
 */
require __DIR__ . '/../vendor/autoload.php';

$path = __DIR__ . '/../Database/migrations';
$files = array_values(array_filter(scandir($path), fn($f) => str_ends_with($f, '.php')));

if (empty($files)) {
    echo "No migration files found in {$path}\n";
    exit(0);
}

$db = App\Core\Database::getInstance()->getConnection();

$fkQueue = [];

foreach ($files as $file) {
    $content = file_get_contents($path . DIRECTORY_SEPARATOR . $file);
    // extract the SQL inside the first $this->execute("..."); block
    if (!preg_match('/\$this->execute\("(.*)"\);/sU', $content, $m)) {
        echo "Skipping (no execute block): {$file}\n";
        continue;
    }
    $sql = $m[1];

    // find CREATE TABLE table name
    if (!preg_match('/CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $sql, $tm)) {
        echo "Skipping (no CREATE TABLE found): {$file}\n";
        continue;
    }
    $tableName = $tm[2];

    // Extract FOREIGN KEY definitions (simple heuristic)
    preg_match_all('/FOREIGN\s+KEY\s*\(([^)]+)\)\s*REFERENCES\s+`?([a-zA-Z0-9_]+)`?\s*\(([^)]+)\)([^,;]*)/i', $sql, $fks, PREG_SET_ORDER);
    foreach ($fks as $fk) {
        $cols = trim($fk[1]);
        $refTable = $fk[2];
        $refCols = trim($fk[3]);
        $options = trim($fk[4]);
        $fkQueue[] = [
            'table' => $tableName,
            'cols'  => $cols,
            'ref_table' => $refTable,
            'ref_cols'  => $refCols,
            'options' => $options,
            'file' => $file,
        ];
    }

    // Remove FOREIGN KEY lines from CREATE TABLE (simple removal)
    $sanitized = preg_replace('/,?\s*FOREIGN\s+KEY\s*\([^\)]+\)\s*REFERENCES\s+`?[a-zA-Z0-9_]+`?\s*\([^\)]+\)\s*[^,;\n]*/i', '', $sql);
    // Clean-up: remove trailing commas before closing parenthesis
    $sanitized = preg_replace('/,\s*\)\s*ENGINE=/i', '\n) ENGINE=', $sanitized);

    try {
        echo "Executing (sanitized): {$file} -> create table {$tableName} ... ";
        $db->exec($sanitized);
        echo "OK\n";
    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

// Second pass: add foreign keys
foreach ($fkQueue as $fk) {
    $table = $fk['table'];
    $cols = $fk['cols'];
    $refTable = $fk['ref_table'];
    $refCols = $fk['ref_cols'];
    $options = $fk['options'];

    $alter = sprintf('ALTER TABLE `%s` ADD FOREIGN KEY (%s) REFERENCES `%s` (%s) %s;', $table, $cols, $refTable, $refCols, $options);
    // Cleanup duplicate spaces
    $alter = preg_replace('/\s+/', ' ', $alter);

    try {
        echo "Adding FK on {$table} -> references {$refTable} ... ";
        $db->exec($alter);
        echo "OK\n";
    } catch (\Throwable $e) {
        echo "ERROR adding FK ({$table} -> {$refTable}): " . $e->getMessage() . "\n";
    }
}

echo "Two-pass migration complete.\n";
