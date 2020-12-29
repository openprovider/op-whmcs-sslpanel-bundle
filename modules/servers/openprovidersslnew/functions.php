<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use libphonenumber\PhoneNumberUtil;

/**
 * @param $params
 *
 * @return string
 * @throws Exception
 * @throws opApiException
 */
function cancel($params)
{
    try {
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->first();
        $params['id'] = $order->order_id;

        if (!$params['id']) {
            return 'No ID for cancel';
        }

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
            $fullMessage . ', ' . $e->getTraceAsString()
        );

        return $fullMessage;
    } catch (\Exception $e) {
        $message = "Error occurred during order saving: {$e->getMessage()}";

        logModuleCall(
            'openprovidersslnew',
            'cancel',
            $params,
            $message,
            $message . ', ' . $e->getTraceAsString()
        );

        return $message;
    }

    return 'success';
}

/**
 * @param $params
 *
 * @return string
 * @throws Exception
 * @throws opApiException
 */
function renew($params)
{
    try {
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->first();
        $params['id'] = $order->order_id;

        $product = Capsule::table('openprovidersslnew_products')->where('name', $params['configoption6'])->first();
        $params['productId'] = $product->product_id;

        addCredentialsToParams($params);

        run_hook('hook_openprovidersslnew_pre_renew', $params);

        opApiWrapper::renewSslCert($params);

        updateOpOrdersTable($params);

        run_hook('hook_openprovidersslnew_renew', $params);
    } catch (opApiException $e) {
        $fullMessage = $e->getFullMessage();

        logModuleCall(
            'openprovidersslnew',
            'renew',
            $params,
            $fullMessage,
            $fullMessage . ', ' . $e->getTraceAsString()
        );

        return $fullMessage;
    } catch (\Exception $e) {
        $message = "Error occurred during order saving: {$e->getMessage()}";

        logModuleCall(
            'openprovidersslnew',
            'renew',
            $params,
            $message,
            $message . ', ' . $e->getTraceAsString()
        );

        return $message;
    }

    return 'success';
}

/**
 * @param $params
 *
 * @return string
 * @throws Exception
 * @throws opApiException
 */
function create($params)
{
    $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->first();

    if ($order) {
        return 'success';
    }

    try {
        if ($handle = createCustomer($params)) {
            $params['organizationHandle'] = $handle;
        }

        $config = ConfigHelper::getServerConfigurationFromParams(
            $params,
            EnvHelper::getServerEnvironmentFromParams($params)
        );

        if ($techHandle = ArrayHelper::getValue($config, 'defaultTechnicalContact')) {
            $params['technicalHandle'] = $techHandle;
        }

        $product = Capsule::table('openprovidersslnew_products')->where('name', $params['configoption6'])->first();
        $productId = $product->product_id;

        $hosting = Capsule::table('tblhosting')->where('id', $params['serviceid'])->first();
        $billingCycle = $hosting->billingcycle;

        $params['period'] = extractYearsFromParams($params, $billingCycle);
        $params['domainAmount'] = extractDomainAmountFromParamsAndProduct($params, $product);
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
            $fullMessage . ', ' . $e->getTraceAsString()
        );

        return $fullMessage;
    } catch (\Exception $e) {
        $message = "Error occurred during order saving: {$e->getMessage()}";

        logModuleCall(
            'openprovidersslnew',
            'create',
            $params,
            $message,
            $message . ', ' . $e->getTraceAsString()
        );

        return $message;
    }

    return 'success';
}

/**
 * @param $params
 * @param $product
 *
 * @return mixed
 */
function extractDomainAmountFromParamsAndProduct($params, $product)
{
    $domainAmount = ArrayHelper::getValue($params, 'configoptions.domainAmount',
        ArrayHelper::getValue($params, 'configoptions.domain amount'));
    $numberOfDomains = $product->number_of_domains ?: 3;

    return $domainAmount ? $domainAmount + $numberOfDomains : 1;
}

/**
 * @param $params
 * @param $billingCycle
 *
 * @return mixed
 */
function extractYearsFromParams($params, $billingCycle)
{
    $yearsMap = [
        'Annually' => 1,
        'Biennially' => 2,
        'Triennially' => 3,
    ];

    if ($years = ArrayHelper::getValue($params, 'configoptions.years')) {
        return $years;
    } else {
        return ArrayHelper::getValue($yearsMap, $billingCycle, 1);
    }
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

function addCredentialsToParams(&$params)
{
    $params = array_merge($params, ConfigHelper::getServerCredentialsArray($params));
}

function createCustomer($params)
{
    if (!isset($params['clientsdetails'])) {
        return null;
    }

    try {
        $customer = buildCustomer(ArrayHelper::getValue($params, 'clientsdetails', []));
    } catch (\Exception $e) {
        $message = "Cannot build customer data: {$e->getMessage()}";

        logModuleCall(
            'openprovidersslnew',
            'createClient',
            $params,
            $message,
            $message . ', ' . $e->getTraceAsString()
        );

        return null;
    }

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
            $fullMessage . ', ' . $e->getTraceAsString()
        );
    } catch (\Exception $e) {
        $message = "Error occurred during order saving: {$e->getMessage()}";

        logModuleCall(
            'openprovidersslnew',
            'createClient',
            $params,
            $message,
            $message . ', ' . $e->getTraceAsString()
        );
    }

    return null;
}

function buildCustomer(array $clientDetails)
{
    // sanitize
    foreach ($clientDetails as $key => $value) {
        $clientDetails[$key] = str_replace(['ь', 'ъ'], '', $value);
    }

    $fullAddress = StringHelper::toAscii(implode(' ', [
        ArrayHelper::getValue($clientDetails, 'address1'),
        ArrayHelper::getValue($clientDetails, 'address2'),
    ]));

    $country = ArrayHelper::getValue($clientDetails, 'country');
    $phoneUtil = PhoneNumberUtil::getInstance();
    $phone = $phoneUtil->parse(ArrayHelper::getValue($clientDetails, 'phonenumber'), $country);
    $streetParsed = str_replace([',', ', '], '', AddressHelper::parseAddress($fullAddress));

    return [
        'companyName' => StringHelper::toAscii(ArrayHelper::getValue($clientDetails, 'companyname')),
        'name' => [
            'firstName' => StringHelper::toAscii(ArrayHelper::getValue($clientDetails, 'firstname')),
            'lastName' => StringHelper::toAscii(ArrayHelper::getValue($clientDetails, 'lastname')),
        ],
        'gender' => 'M',
        'phone' => [
            'countryCode' => $phone->getCountryCode(),
            'areaCode' => substr($phone->getNationalNumber(), 0, 3),
            'subscriberNumber' => substr($phone->getNationalNumber(), 3),
        ],
        'address' => [
            'street' => ArrayHelper::getValue($streetParsed, 'street', $fullAddress),
            'number' => ArrayHelper::getValue($streetParsed, 'number', (int)$fullAddress),
            'suffix' => ArrayHelper::getValue($streetParsed, 'suffix'),
            'zipcode' => ArrayHelper::getValue($clientDetails, 'postcode'),
            'city' => StringHelper::toAscii(ArrayHelper::getValue($clientDetails, 'city')),
            'state' => StringHelper::toAscii(ArrayHelper::getValue($clientDetails, 'state')),
            'country' => $country,
        ],
        'email' => ArrayHelper::getValue($clientDetails, 'email'),
    ];
}