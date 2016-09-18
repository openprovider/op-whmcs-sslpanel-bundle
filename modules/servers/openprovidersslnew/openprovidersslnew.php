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
        "sslUrl" => [
            "Type" => "text",
            "Size" => "60",
            "Description" => "SSL URL",
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

        $reply = opApiWrapper::createSslCert($params, $product_id);

        Capsule::table('openprovidersslnew_orders')->insert([
            'id' => null,
            'product_id' => $product_id,
            'order_id' => $reply['id'],
            'status' => 'REQ',
            'creation_date' => date('Y-m-d H:i:s', time()),
            'activation_date' => '1970-01-01 00:00:00',
            'expiration_date' => '1970-01-01 00:00:00',
            'changed_at' => date('Y-m-d H:i:s', time()),
            'service_id' => $params['serviceid'],
        ]);
    } catch (opApiException $e) {
        $fullMessage = $e->getFullMessage();
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_CreateAccount',
            $params,
            $fullMessage,
            $e->getTraceAsString()
        );

        return $fullMessage;
    } catch (\Exception $e) {
        $message = "Error occurred during order saving: {$e->getMessage()}";
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_CreateAccount',
            $params,
            $message,
            $e->getTraceAsString()
        );

        return $message;
    }

    return "success";
}

/**
 * @param array $params
 *
 * @return array|string
 */
function openprovidersslnew_ClientArea($params)
{
    include __DIR__ . '/lib/opApiWrapper.php';
    $reply = null;
    $fullMessage = null;
    $order = null;
    $token = null;
    $status = null;
    $dates = [];

    try {
        $order = array_shift(
            Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->get()
        );
        // generate otp token
        $reply = opApiWrapper::generateOtpToken($params, $order->order_id);
        $token = $reply['token'];
        //update status
        $reply = opApiWrapper::retrieveOrder($params, $order->order_id);
        $status = $reply['status'];
        $dates['creationDate'] = $reply['orderDate'];
        $dates['activationDate'] = $reply['activeDate'];
        $dates['expirationDate'] = $reply['expirationDate'];
        //save update status into a DB
        Capsule::table('openprovidersslnew_orders')->lockForUpdate();
        Capsule::table('openprovidersslnew_orders')
            ->where('service_id', $params['serviceid'])
            ->update([
                'status' => $status,
                'creation_date' => $dates['creationDate'],
                'activation_date' => $dates['activation_date'],
                'expiration_date' => $dates['expirationDate'],
            ]);
    } catch (opApiException $e) {
        $fullMessage = $e->getFullMessage();
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_ClientArea',
            $params,
            $fullMessage,
            $e->getTraceAsString()
        );
    } catch (\Exception $e) {
        $fullMessage = $e->getMessage();
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_ClientArea',
            $params,
            $fullMessage,
            $e->getTraceAsString()
        );
    }

    return [
        'templatefile' => 'templates/clientarea.tpl',
        'templateVariables' => [
            'linkValue' => $params['configoption4'] . 'auth-order-otp-token?token=' . $token,
            'linkName' => 'ssl panel',
            'errorMessage' => $fullMessage,
            'status' => $status,
            'creationDate' => $dates['creationDate'],
            'activationDate' => $dates['activationDate'],
            'expirationDate' => $dates['expirationDate'],
        ],
    ];
}
