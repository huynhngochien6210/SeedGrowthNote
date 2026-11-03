
<?php
require_once __DIR__ . '/config/database.php';

echo "Database hien tai: ";
echo $pdo->query("SELECT DATABASE()")->fetchColumn();
echo PHP_EOL . PHP_EOL;

echo "Danh sach bang:" . PHP_EOL;

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "- " . $table . PHP_EOL;
}
