<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * @return array
 */
function openprovidersslnew_MetaData()
{
    return array(
        'DisplayName' => 'Openprovider ssl provisioning module',
        'APIVersion' => '1.0', // Use API Version 1.0
        'RequiresServer' => true, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '1111', // Default Non-SSL Connection Port
        'DefaultSSLPort' => '1112', // Default SSL Connection Port
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin',
    );
}

/**
 * @return array
 */
function openprovidersslnew_ConfigOptions()
{
    $products = [];
    foreach (Capsule::table('openprovidersslnew_products')->get() as $product) {
        $products[] = $product->name;
    }

    return [
        "username" => [
            "Type" => "text",
            "Size" => "25",
            "Description" => "Openprovider login",
        ],
        "password" => [
            "Type" => "password",
            "Size" => "25",
            "Description" => "Openprovider password",
        ],
        "apiUrl" => [
            "Type" => "text",
            "Size" => "60",
            "Description" => "Openprovider API URL",
        ],
        "sslApiUrl" => [
            "Type" => "text",
            "Size" => "60",
            "Description" => "SSL API URL",
        ],
        "SSL Certificate Type" => [
            "Type" => "dropdown",
            "Options" => implode(',', $products),
        ],
    ];
}

/**
 * @param array $params
 *
 * @return string
 */
function openprovidersslnew_CreateAccount($params)
{
    include __DIR__ . '/lib/opApiWrapper.php';
    $reply = null;

    try {
        $product_id = array_shift(
            Capsule::table('openprovidersslnew_products')->where('name', $params['configoption5'])->get()
        )->product_id;

        if (isset($params['configoptions']) && isset($params['configoptions']['years'])) {
            $params['period'] = $params['configoptions']['years'];
        } else {
            $params['period'] = 1;
        }

        opApiWrapper::createSslCert($params, $product_id);
    } catch (opApiException $e) {
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_CreateAccount',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getFullMessage();
    }

    return "success";
}

function openprovidersslnew_ClientArea($params) 
{
    include __DIR__ . '/lib/opApiWrapper.php';
    $token = null;

    try {
        //todo: get OP order id from DB
        $token = opApiWrapper::generateOtpToken($params, 444)['token'];
    } catch (opApiException $e) {
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_ClientArea',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getFullMessage();
    }

    return [
        'templatefile' => 'templates/clientarea.tpl',
        'templateVariables' => [
            'linkValue' => $params['configoption5'] . 'auth-order-otp-token?token=' . $token,
            'linkName' => 'sslinhva link',
        ],
    ];
}
