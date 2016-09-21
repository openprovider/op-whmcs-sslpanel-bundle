<?php

include __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

define("CLIENTAREA", true);

require("init.php");

$ca = new WHMCS_ClientArea();

$ca->setPageTitle("Your Page Title Goes Here");

$ca->addToBreadCrumb('index.php', Lang::trans('globalsystemname'));
$ca->addToBreadCrumb('generateToken.php', 'Your Custom Page Name');

$ca->initPage();

$ca->requireLogin();

if ($ca->isLoggedIn()) {
    include __DIR__ . '/modules/servers/openprovidersslnew/lib/opApiWrapper.php';

    $serviceId = $_GET['serviceId'];

    $hosting = array_shift(Capsule::table('tblhosting')->where('id', $serviceId)->get());
    $products = array_shift(Capsule::table('tblproducts')->where('id', $hosting->packageid)->get());
    $order = array_shift(Capsule::table('openprovidersslnew_orders')->where('service_id', $serviceId)->get());

    $reply = opApiWrapper::generateOtpToken([
        'username' => $products->configoption1,
        'password' => $products->configoption2,
        'apiUrl' => $products->configoption3,
    ], $order->order_id);

    $token = $reply['token'];

    Header("Location: " . $products->configoption3 . "?auth-order-otp-token&token=" . $token);
}

$ca->setTemplate('mypage');

$ca->output();
