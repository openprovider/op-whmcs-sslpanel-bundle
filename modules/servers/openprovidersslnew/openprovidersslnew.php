<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use libphonenumber\PhoneNumberUtil;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * @return array
 */
function openprovidersslnew_MetaData()
{
    return [
        'DisplayName' => 'Openprovider ssl provisioning module',
        'APIVersion' => '1.0', // Use API Version 1.0
        'RequiresServer' => true, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '1111', // Default Non-SSL Connection Port
        'DefaultSSLPort' => '1112', // Default SSL Connection Port
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin',
    ];
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
        'API Username' => [
            'Type' => 'text',
            'Size' => '25',
        ],
        'API Password' => [
            'Type' => 'password',
            'Size' => '25',
        ],
        'Openprovider API URL' => [
            'Type' => 'text',
            'Size' => '60',
        ],
        'SSL Panel URL' => [
            'Type' => 'text',
            'Size' => '60',
        ],
        'SSL Product' => [
            'Type' => 'dropdown',
            'Options' => implode(',', $products),
        ],
        '!TEST! Mode?' => [
            'Type' => 'yesno',
            'Size' => '25',
        ],
        '!TEST! API Username' => [
            'Type' => 'text',
            'Size' => '25',
        ],
        '!TEST! API Password' => [
            'Type' => 'password',
            'Size' => '25',
        ],
        '!TEST! Openprovider API URL' => [
            'Type' => 'text',
            'Size' => '60',
        ],
        '!TEST! SSL Panel URL' => [
            'Type' => 'text',
            'Size' => '60',
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
    return create($params);
}

/**
 * @param $params
 *
 * @return string
 */
function openprovidersslnew_Renew($params)
{
    return renew($params);
}

/**
 * @param $params
 *
 * @return string
 */
function openprovidersslnew_Create($params)
{
    return create($params);
}

/**
 * @param $params
 *
 * @return string
 */
function openprovidersslnew_Cancel($params)
{
    return cancel($params);
}

/**
 * @param array $params
 *
 * @return string
 */
function cancel($params)
{
    try {
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->get();
        $order = array_shift($order);
        $params['id'] = $order->order_id;
        addCredentialsToParams($params);
        opApiWrapper::cancelSslCert($params);
        updateOpOrdersTable($params);
    } catch (opApiException $e) {
        $fullMessage = $e->getFullMessage();
        logModuleCall(
            'openprovidersslnew',
            'cancel',
            $params,
            $fullMessage,
            $e->getTraceAsString()
        );

        return $fullMessage;
    } catch (\Exception $e) {
        $message = "Error occurred during order saving: {$e->getMessage()}";
        logModuleCall(
            'openprovidersslnew',
            'cancel',
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
    try {
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->get();
        $order = array_shift($order);
        $params['id'] = $order->order_id;
        addCredentialsToParams($params);
        opApiWrapper::renewSslCert($params);
        updateOpOrdersTable($params);
    } catch (opApiException $e) {
        $fullMessage = $e->getFullMessage();
        logModuleCall(
            'openprovidersslnew',
            'renew',
            $params,
            $fullMessage,
            $e->getTraceAsString()
        );

        return $fullMessage;
    } catch (\Exception $e) {
        $message = "Error occurred during order saving: {$e->getMessage()}";
        logModuleCall(
            'openprovidersslnew',
            'renew',
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
    try {
        if ($handle = createCustomer($params)) {
            $params['organizationHandle'] = $handle;
        }

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
            'create',
            $params,
            $fullMessage,
            $e->getTraceAsString()
        );

        return $fullMessage;
    } catch (\Exception $e) {
        $message = "Error occurred during order saving: {$e->getMessage()}";
        logModuleCall(
            'openprovidersslnew',
            'create',
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
    } else {
        if ($billingCycle && $billingCycle === 'Annually') {
            return 1;
        } else {
            if ($billingCycle && $billingCycle === 'Biennially') {
                return 2;
            } else {
                if ($billingCycle && $billingCycle === 'Triennially') {
                    return 3;
                } else {
                    return 1;
                }
            }
        }
    }
}

function addCredentialsToParams(&$params)
{
    $params = array_merge($params, ConfigHelper::getServerCredentialsArray($params));
}

/**
 * @param array $params
 *
 * @return array|string
 */
function openprovidersslnew_ClientArea($params)
{
    $fullMessage = null;
    $order = null;
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

/**
 * @return array
 */
function openprovidersslnew_AdminCustomButtonArray()
{
    return [
        "Cancel" => "Cancel",
        "Renew" => "Renew",
        "Open ssl panel detail page" => "OpenSslPanelDetailPage",
    ];
}

function openprovidersslnew_AdminServicesTabFields($params)
{
    $reply = null;
    $product = null;
    $serviceId = $params['serviceid'];

    try {
        $hosting = Capsule::table('tblhosting')->where('id', $serviceId)->get();
        $hosting = array_shift($hosting);
        $product = Capsule::table('tblproducts')->where('id', $hosting->packageid)->get();
        $product = array_shift($product);
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $serviceId)->get();
        $order = array_shift($order);

        $reply = opApiWrapper::retrieveOrder(array_merge(
            ConfigHelper::getServerCredentialsArray($params),
            ['id' => $order->order_id]
        ));
    } catch (opApiException $e) {
        $fullMessage = $e->getFullMessage();
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_OpenSslPanelDetailPage',
            $params,
            $fullMessage,
            $e->getTraceAsString()
        );
    } catch (\Exception $e) {
        $fullMessage = $e->getMessage();
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_OpenSslPanelDetailPage',
            $params,
            $fullMessage,
            $e->getTraceAsString()
        );
    }

    $sslinhvaOrderId = $reply['sslinhvaOrderId'];

    $link = $product->configoption4 . "?utm_source=rcp&utm_medium=order_overview_link&utm_campaign=new_order_details#/orders/{$sslinhvaOrderId}/details";

    return [
        "Ssl panel" => '<a href="' . $link . '">open order details page</a>',
    ];
}

function createCustomer($params)
{
    if (!isset($params['clientsdetails'])) {
        return null;
    }

    $customer = buildCustomer(ArrayHelper::getValue($params, 'clientsdetails', []));

    $params['customer'] = $customer;

    try {
        $reply = opApiWrapper::processRequest(
            'createCustomerRequest',
            ConfigHelper::getServerCredentialsArray($params),
            $customer
        );

        return ArrayHelper::getValue($reply, 'handle');
    } catch (opApiException $e) {
        $fullMessage = $e->getFullMessage();

        logModuleCall(
            'openprovidersslnew',
            'createClient',
            $params,
            $fullMessage,
            implode(', ', [$fullMessage, $e->getTraceAsString()])
        );
    } catch (\Exception $e) {
        $message = "Error occurred during order saving: {$e->getMessage()}";

        logModuleCall(
            'openprovidersslnew',
            'createClient',
            $params,
            $message,
            $e->getTraceAsString()
        );
    }

    return null;
}

function buildCustomer(array $clientDetails)
{
    $street = implode(', ', [
        ArrayHelper::getValue($clientDetails, 'address1'),
        ArrayHelper::getValue($clientDetails, 'address2'),
    ]);

    $country = ArrayHelper::getValue($clientDetails, 'country');
    $phoneUtil = PhoneNumberUtil::getInstance();
    $phone = $phoneUtil->parse(ArrayHelper::getValue($clientDetails, 'phonenumber'), $country);

    return [
        'companyName' => ArrayHelper::getValue($clientDetails, 'companyname'),
        'name' => [
            'firstName' => ArrayHelper::getValue($clientDetails, 'firstname'),
            'lastName' => ArrayHelper::getValue($clientDetails, 'lastname'),
        ],
        'gender' => 'M',
        'phone' => [
            'countryCode' => $phone->getCountryCode(),
            'areaCode' => 0,
            'subscriberNumber' => $phone->getNationalNumber(),
        ],
        'address' => [
            'street' => $street,
            'number' => (int)$street,
            'zipcode' => ArrayHelper::getValue($clientDetails, 'postcode'),
            'city' => ArrayHelper::getValue($clientDetails, 'city'),
            'state' => ArrayHelper::getValue($clientDetails, 'state'),
            'country' => $country,
        ],
        'email' => ArrayHelper::getValue($clientDetails, 'email'),
    ];
}