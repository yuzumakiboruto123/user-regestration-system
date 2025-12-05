<?php
require_once '../includes/db.php';
$pdo = getDB();
echo "Connected DB: " . $pdo->query("SELECT DATABASE()")->fetchColumn();
