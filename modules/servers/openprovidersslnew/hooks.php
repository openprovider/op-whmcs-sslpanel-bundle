<?php

use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

add_hook('CancelOrder', 1, function ($parameters) {
    $orderId = ArrayHelper::getValue($parameters, 'orderid');

    $service = Capsule::table('tblhosting')->where('orderid', $orderId)->first();
    $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();

    cancel(array_merge((array)$product, ['serviceid' => $service->id]));
});

add_hook('PreCronJob', 1, function () {
    try {
        $orders = Capsule::table('openprovidersslnew_orders')->where('status', ['PAI', 'REQ', 'REJ'])->get();

        foreach ($orders as $order) {
            $service = Capsule::table('tblhosting')->where('id', $order->service_id)->first();
            $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();

            updateOpOrdersTable(array_merge((array)$product, ['id' => $order->order_id, 'serviceid' => $service->id]));
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
});
