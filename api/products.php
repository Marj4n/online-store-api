<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

$result = $mysqli->query(
    "SELECT * FROM products"
);

$products = [];

while (
    $row = $result->fetch_assoc()
) {
    $products[] = $row;
}

jsonResponse($products);
