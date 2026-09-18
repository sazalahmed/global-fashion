<?php
// Truncate all tables in bizpos_test
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=bizpos_test', 'root', '');
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    if ($table !== 'migrations') {
        $pdo->exec("TRUNCATE TABLE `$table`");
    }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
echo "Truncated " . count($tables) . " tables.\n";
