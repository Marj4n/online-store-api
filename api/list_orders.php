<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

$result = $mysqli->query("
    SELECT *
    FROM orders
    ORDER BY id DESC
");

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

jsonResponse($data);
