<?php

$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if (
    $request == '/online-store-api/api/products'
    && $method == 'GET'
) {
    require 'api/products.php';
}

elseif (
    $request == '/online-store-api/api/orders'
    && $method == 'POST'
) {
    require 'api/orders.php';
}

elseif (
    $request == '/online-store-api/api/orders'
    && $method == 'GET'
) {
    require 'api/list_orders.php';
}

else {

    http_response_code(404);

    echo json_encode([
        'message' => 'Route not found'
    ]);
}