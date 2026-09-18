<?php
// Reset test database
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
$pdo->exec('DROP DATABASE IF EXISTS bizpos_test');
$pdo->exec('CREATE DATABASE bizpos_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo "bizpos_test database reset successfully.\n";
