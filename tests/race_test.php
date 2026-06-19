<?php

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Reset test inventory
|--------------------------------------------------------------------------
*/

$mysqli->query("
    UPDATE products
    SET inventory = 10
    WHERE id = 1
");

$url =
    'http://localhost:8000/online-store-api/api/orders';

$multiCurl = curl_multi_init();

$curlHandles = [];

for ($i = 0; $i < 100; $i++) {

    $payload = json_encode([
        'items' => [
            [
                'product_id' => 1,
                'quantity' => 1
            ]
        ]
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        [
            'Content-Type: application/json'
        ]
    );

    curl_multi_add_handle(
        $multiCurl,
        $ch
    );

    $curlHandles[] = $ch;
}

$running = null;

do {

    curl_multi_exec(
        $multiCurl,
        $running
    );
} while ($running);

$success = 0;
$failed = 0;

foreach ($curlHandles as $ch) {

    $response = json_decode(
        curl_multi_getcontent($ch),
        true
    );

    if (
        isset($response['success']) &&
        $response['success'] === true
    ) {
        $success++;
    } else {
        $failed++;
    }

    curl_multi_remove_handle(
        $multiCurl,
        $ch
    );
}

curl_multi_close(
    $multiCurl
);

/*
|--------------------------------------------------------------------------
| Final Inventory Check
|--------------------------------------------------------------------------
*/

$result = $mysqli->query("
    SELECT inventory
    FROM products
    WHERE id = 1
");

$product = $result->fetch_assoc();

echo PHP_EOL;
echo "========================";
echo PHP_EOL;
echo "FLASH SALE TEST RESULT";
echo PHP_EOL;
echo "========================";
echo PHP_EOL;
echo "SUCCESS : $success";
echo PHP_EOL;
echo "FAILED  : $failed";
echo PHP_EOL;
echo "FINAL INVENTORY : " . $product['inventory'];
echo PHP_EOL;
echo "========================";
echo PHP_EOL;
