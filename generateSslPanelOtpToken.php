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
    $languages = [
        'russian' => 'ru_RU',
        'ukranian' => 'ru_RU',
    ];

    $allLanguages = ['en_GB', 'ru_RU', 'es_ES', 'nl_NL'];

    $serviceId = $_GET['serviceId'];

    $hosting = Capsule::table('tblhosting')->where('id', $serviceId)->first();

    if ((int)$ca->getUserID() !== (int)$hosting->userid) {
        logModuleCall(
            'openprovidersslnew',
            'generateSslPanelOtpToken',
            ['serviceId' => $serviceId, 'clientAreaUserId' => $ca->getUserID(), 'serviceUserId' => $hosting->userid],
            'Forbidden'
        );

        header('HTTP/1.0 403 Forbidden');

        echo 'You are forbidden!';

        exit;
    }

    $product = Capsule::table('tblproducts')->where('id', $hosting->packageid)->first();
    $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $serviceId)->first();

    require_once __DIR__ . '/modules/servers/openprovidersslnew/lib/helpers/ArrayHelper.php';
    require_once __DIR__ . '/modules/servers/openprovidersslnew/lib/API.php';
    require_once __DIR__ . '/modules/servers/openprovidersslnew/lib/opApiException.php';
    require_once __DIR__ . '/modules/servers/openprovidersslnew/lib/opApiWrapper.php';

    $env = $product->configoption7 ? 'test' : 'production';

    $reply = opApiWrapper::generateOtpToken([
        'username' => $env === 'production' ? $product->configoption1 : $product->configoption8,
        'password' => $env === 'production' ? $product->configoption2 : $product->configoption9,
        'apiUrl' => $env === 'production' ? $product->configoption3 : $product->configoption10,
        'id' => $order->order_id,
    ]);

    $token = $reply['token'];
    $defaultLanguage = isset($allLanguages[$product->configoption15]) ? $allLanguages[$product->configoption15] : null;
    $language = isset($languages[$ca->getClient()['language']]) ? $languages[$ca->getClient()['language']] : $defaultLanguage;

    header(sprintf('Location: %s/auth-order-otp-token?token=%s&language=%s',
        $env === 'production' ? $product->configoption4 : $product->configoption11, $token,
        $language));

    exit;
}

$ca->setTemplate('mypage');

$ca->output();
