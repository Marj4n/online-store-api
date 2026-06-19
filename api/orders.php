<?php

/*
 * Flash Sale Protection
 *
 * FOR UPDATE locks the selected product row
 * so concurrent transactions cannot modify
 * inventory simultaneously.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

$data = json_decode(
    file_get_contents('php://input'),
    true
);

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if (
    !isset($data['items']) ||
    !is_array($data['items']) ||
    count($data['items']) === 0
) {
    jsonResponse([
        'success' => false,
        'message' => 'Order must contain at least one item'
    ], 400);
}

$item = $data['items'][0];

if (
    !isset($item['product_id']) ||
    !isset($item['quantity'])
) {
    jsonResponse([
        'success' => false,
        'message' => 'product_id and quantity are required'
    ], 400);
}

$productId = (int) $item['product_id'];
$qty       = (int) $item['quantity'];

if ($qty <= 0) {
    jsonResponse([
        'success' => false,
        'message' => 'Quantity must be greater than 0'
    ], 400);
}

/*
|--------------------------------------------------------------------------
| Transaction
|--------------------------------------------------------------------------
*/

try {

    $mysqli->begin_transaction();

    /*
     * Lock product row during flash sale
     */
    $stmt = $mysqli->prepare("
        SELECT id, name, price, inventory
        FROM products
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->bind_param(
        "i",
        $productId
    );

    $stmt->execute();

    $product = $stmt
        ->get_result()
        ->fetch_assoc();

    if (!$product) {

        $mysqli->rollback();

        jsonResponse([
            'success' => false,
            'message' => 'Product not found'
        ], 404);
    }

    /*
     * Prevent negative inventory
     */
    if ($product['inventory'] < $qty) {

        $mysqli->rollback();

        jsonResponse([
            'success' => false,
            'message' => 'Insufficient inventory'
        ], 409);
    }

    /*
     * Update inventory
     */
    $newInventory =
        $product['inventory'] - $qty;

    $update = $mysqli->prepare("
        UPDATE products
        SET inventory = ?
        WHERE id = ?
    ");

    $update->bind_param(
        "ii",
        $newInventory,
        $productId
    );

    $update->execute();

    /*
     * Create order
     */
    $totalPrice =
        $product['price'] * $qty;

    $order = $mysqli->prepare("
        INSERT INTO orders
        (
            total_price
        )
        VALUES
        (?)
    ");

    $order->bind_param(
        "d",
        $totalPrice
    );

    $order->execute();

    $orderId =
        $mysqli->insert_id;

    /*
     * Create order item
     */
    $orderItem = $mysqli->prepare("
        INSERT INTO order_items
        (
            order_id,
            product_id,
            quantity,
            price
        )
        VALUES
        (
            ?, ?, ?, ?
        )
    ");

    $orderItem->bind_param(
        "iiid",
        $orderId,
        $productId,
        $qty,
        $product['price']
    );

    $orderItem->execute();

    /*
     * Commit transaction
     */
    $mysqli->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Order created successfully',
        'order_id' => $orderId,
        'product_id' => $productId,
        'quantity' => $qty,
        'remaining_inventory' => $newInventory
    ], 201);
} catch (Exception $e) {

    $mysqli->rollback();

    jsonResponse([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ], 500);
}
