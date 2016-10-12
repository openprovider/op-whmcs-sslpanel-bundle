<?php

/**
 * Replace this file into a whmcs root dir
 */
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

define("CLIENTAREA", true);

require_once 'init.php';

$ca = new WHMCS_ClientArea();

$ca->setPageTitle('Redirecting to SSL Panel...');

$ca->addToBreadCrumb('index.php', Lang::trans('globalsystemname'));
$ca->addToBreadCrumb('generateToken.php', 'Redirecting to SSL Panel...');

$ca->initPage();

$ca->requireLogin();

if ($ca->isLoggedIn()) {
    $serviceId = $_GET['serviceId'];

    $hosting = Capsule::table('tblhosting')->where('id', $serviceId)->get();
    $hosting = array_shift($hosting);

    if ($ca->getUserID() !== $hosting->userid) {
        header('HTTP/1.0 403 Forbidden');

        echo 'You are forbidden!';

        exit;
    }

    $product = Capsule::table('tblproducts')->where('id', $hosting->packageid)->get();
    $product = array_shift($product);
    $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $serviceId)->get();
    $order = array_shift($order);

    require_once __DIR__ . '/modules/servers/openprovidersslnew/lib/API.php';
    require_once __DIR__ . '/modules/servers/openprovidersslnew/lib/opApiException.php';
    require_once __DIR__ . '/modules/servers/openprovidersslnew/lib/opApiWrapper.php';

    $reply = opApiWrapper::generateOtpToken([
        'username' => $product->configoption1,
        'password' => $product->configoption2,
        'apiUrl' => $product->configoption3,
        'id' => $order->order_id,
    ]);

    $token = $reply['token'];

    header(sprintf('Location: %s/auth-order-otp-token?token=%s', $product->configoption4, $token));

    exit;
}

$ca->setTemplate('mypage');

$ca->output();
