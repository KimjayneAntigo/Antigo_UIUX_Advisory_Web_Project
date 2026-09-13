<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SHOW CREATE TABLE users");
echo $stmt->fetch(PDO::FETCH_ASSOC)['Create Table'] . "\n\n";
$stmt2 = $pdo->query("SHOW CREATE TABLE projects");
echo $stmt2->fetch(PDO::FETCH_ASSOC)['Create Table'] . "\n";
