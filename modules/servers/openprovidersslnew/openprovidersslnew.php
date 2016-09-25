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
    create($params);
}

/**
 * @param array $params
 *
 * @return string
 */
function openprovidersslnew_Renew($params)
{
    renew($params);
}

/**
 * @param array $params
 *
 * @return string
 */
function cancel($params)
{
    include __DIR__ . '/lib/opApiWrapper.php';

    try {
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->get();
        $order = array_shift($order);
        $params['id'] = $order->order_id;
        addCredentialsToParams($params);
        opApiWrapper::cancelSslCert($params);
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
 * @return string
 */
function renew($params)
{
    include __DIR__ . '/lib/opApiWrapper.php';

    try {
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->get();
        $order = array_shift($order);
        $params['id'] = $order->order_id;
        addCredentialsToParams($params);
        opApiWrapper::renewSslCert($params);
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
 * @return string
 */
function create($params)
{
    include __DIR__ . '/lib/opApiWrapper.php';
    $reply = null;

    try {
        $product = Capsule::table('openprovidersslnew_products')->where('name', $params['configoption5'])->get();
        $product = array_shift($product);
        $productId = $product->product_id;

        $hosting = Capsule::table('tblhosting')->where('id', $params['serviceid'])->get();
        $hosting = array_shift($hosting);
        $billingCycle = $hosting->billingcycle;

        $params['period'] = extractYearsFromParams($params, $billingCycle);
        $params['domainAmount'] = extractDomainAmountFromParams($params);
        $params['productId'] = $productId;

        addCredentialsToParams($params);
        $reply = opApiWrapper::createSslCert($params);

        Capsule::table('openprovidersslnew_orders')->insert([
            'id' => null,
            'product_id' => $productId,
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
 * @param $params
 *
 * @return mixed
 */
function extractDomainAmountFromParams($params)
{
    if (isset($params['configoptions']) && isset($params['configoptions']['domain amount'])) {
        return $params['configoptions']['domain amount'] + 3; //3 is preset domains
    } else {
        return 1;
    }
}

/**
 * @param $params
 * @param $billingCycle
 *
 * @return mixed
 */
function extractYearsFromParams($params, $billingCycle)
{
    if (isset($params['configoptions']) && isset($params['configoptions']['years'])) {
        return $params['configoptions']['years'];
    } else if ($billingCycle && $billingCycle === 'Annually') {
        return 1;
    } else if ($billingCycle && $billingCycle === 'Biennially') {
        return 2;
    } else if ($billingCycle && $billingCycle === 'Triennially') {
        return 3;
    } else {
        return 1;
    }
}

function addCredentialsToParams(&$params)
{
    $params['username'] = $params['configoption1'] ?: null;
    $params['password'] = $params['configoption2'] ?: null;
    $params['apiUrl'] = $params['configoption3'] ?: null;
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
    $updatedData = [];

    try {
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->get();
        $order = array_shift($order);
        //update status
        $params['id'] = $order->order_id;
        $updatedData = updateOpOrdersTable($params);
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
            'linkValue' => 'serviceId=' . $params['serviceid'],
            'linkName' => 'ssl panel',
            'errorMessage' => $fullMessage,
            'status' => $updatedData['status'],
            'creationDate' => $updatedData['creationDate'],
            'activationDate' => $updatedData['activationDate'],
            'expirationDate' => $updatedData['expirationDate'],
        ],
    ];
}

/**
 * @param $params
 *
 * @return array
 */
function updateOpOrdersTable($params)
{
    addCredentialsToParams($params);
    $reply = opApiWrapper::retrieveOrder($params);
    //save update status into a DB
    Capsule::table('openprovidersslnew_orders')->lockForUpdate();
    Capsule::table('openprovidersslnew_orders')
        ->where('service_id', $params['serviceid'])
        ->update([
            'status' => $reply['status'],
            'creation_date' => $reply['orderDate'],
            'activation_date' => $reply['activeDate'],
            'expiration_date' => $reply['expirationDate'],
        ]);
    return [
        'status' => $reply['status'],
        'creationDate' => $reply['orderDate'],
        'activationDate' => $reply['activeDate'],
        'expirationDate' => $reply['expirationDate'],
    ];
}
