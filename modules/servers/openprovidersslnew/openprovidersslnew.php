<?php

use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

const PAGE_GENERATE_SSL_PANEL_OTP_TOKEN = 'generateSslPanelOtpToken.php';

/**
 * @return array
 */
function openprovidersslnew_MetaData()
{
    return [
        'DisplayName' => 'Openprovider SSL Panel',
        'APIVersion' => '1.0', // Use API Version 1.0
        'RequiresServer' => false,
    ];
}

/**
 * @return array
 */
function openprovidersslnew_ConfigOptions($params)
{
    $products = [];
    foreach (Capsule::table('openprovidersslnew_products')->get() as $product) {
        $products[] = $product->name;
    }

    $jsToCheckDefaultTechnicalHandle = <<<HTML
<script>
    const regex = /^[A-Z]{2}[\d]{6,}\-[A-Z]{2}$/ // regex to check if value is not handle
    const handle = $("input[name='packageconfigoption[5]']"); // handle input
    if (!handle[0].value.match(regex)) {
        const description = $(handle).parent().prev(); // handle label
        description.text(description.text() + '*') // add '*' to label
        description.css('color', 'red') // set red color to label
        handle.css('color', 'red').after("<span style='color: red; font-size: 13px'>Handle should be like this: " + regex + "</span>")
    }
</script>
HTML;
    
    return [
        'API Username' => [
            'Type' => 'text',
            'Size' => '25',
            'SimpleMode' => true,
        ],
        'API Password' => [
            'Type' => 'password',
            'Size' => '25',
            'SimpleMode' => true,
        ],
        'SSL Product' => [
            'Type' => 'dropdown',
            'Options' => implode(',', $products),
            'SimpleMode' => true,
        ],
        'Test mode' => [
            'Type' => 'yesno',
            'Size' => '25',
            'SimpleMode' => false,
        ],
        'Default technical contact handle' => [
            'Type' => 'text',
            'Size' => '60',
            'SimpleMode' => false,
            'Description' => $jsToCheckDefaultTechnicalHandle,
        ],
        'Default language' => [
            'Type' => 'dropdown',
            'Options' => ['en_GB', 'ru_RU', 'es_ES', 'nl_NL'],
            'SimpleMode' => true,
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
    try {
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_CreateAccount',
            $params,
            '',
            'Attempt to create new order',
            ConfigHelper::getParametersToMaskInLogs($params)
        );

        return create($params);
    } catch (\Exception $e) {
        return $e->getMessage();
    }
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
function openprovidersslnew_Cancel($params)
{
    return cancel($params);
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
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->first();

        $params['id'] = $order->order_id;

        $updatedData = updateOpOrdersTable($params);
    } catch (opApiException $e) {
        $fullMessage = $e->getFullMessage();
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_ClientArea',
            $params,
            $fullMessage,
            $e->getTraceAsString(),
            ConfigHelper::getParametersToMaskInLogs($params)
        );
    } catch (\Exception $e) {
        $fullMessage = $e->getMessage();
        logModuleCall(
            'openprovidersslnew',
            'openprovidersslnew_ClientArea',
            $params,
            $fullMessage,
            $e->getTraceAsString(),
            ConfigHelper::getParametersToMaskInLogs($params)
        );
    }

    $statusMap = [
        'PAI' => 'Paid',
        'REQ' => 'Requested',
        'REJ' => 'Rejected',
        'FAI' => 'Failed',
        'EXP' => 'Expired',
        'ACT' => 'Active',
    ];

    $scriptFileName = $_SERVER['SCRIPT_NAME'];
    $prefix = substr($scriptFileName, 0, strlen(basename($scriptFileName)) * -1);

    return [
        'templatefile' => 'templates/clientarea.tpl',
        'templateVariables' => [
            'linkName' => 'SSL Panel',
            'linkUrl' => $prefix . PAGE_GENERATE_SSL_PANEL_OTP_TOKEN . '?serviceId=' . $params['serviceid'],
            'errorMessage' => $fullMessage,
            'status' => ArrayHelper::getValue($statusMap, $updatedData['status']),
            'creationDate' => $updatedData['creationDate'],
            'activationDate' => $updatedData['activationDate'],
            'expirationDate' => $updatedData['expirationDate'],
        ],
    ];
}

/**
 * @return array
 */
function openprovidersslnew_AdminCustomButtonArray()
{
    return [
        'Cancel' => 'Cancel',
        'Renew' => 'Renew',
    ];
}

function openprovidersslnew_AdminServicesTabFields($params)
{
    if (isset($_GET['viewDetails'])) {
        $service = Capsule::table('tblhosting')->where('id', $params['serviceid'])->first();
        $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $service->id)->first();

        $html = '';

        if ($order->order_id) {
            $configuration = ConfigHelper::getServerConfigurationFromParams($product,
                EnvHelper::getServerEnvironmentFromParams($product));

            $apiCredentials = [
                'username' => ArrayHelper::getValue($configuration, 'username'),
                'password' => ArrayHelper::getValue($configuration, 'password'),
                'apiUrl' => ConfigHelper::getApiUrlFromConfig(EnvHelper::getServerEnvironmentFromParams($product)),
                'id' => $order->order_id,
            ];

            $reply = opApiWrapper::retrieveOrder($apiCredentials);

            $link1 = ConfigHelper::getRcpUrlFromConfig(EnvHelper::getServerEnvironmentFromParams($product))
                . '/ssl/order-details.php?ssl_order_id=' . $reply['id'];
            $link2 = ConfigHelper::getSslPanelUrlFromConfig(EnvHelper::getServerEnvironmentFromParams($product))
                . '/#/orders/' . $reply['sslinhvaOrderId'] . '/details';

            $html = '<br /><a href=\'' . $link1 . '\' target=\'_blank\'>' . $link1 . '</a><br />';
            $html .= '<a href=\'' . $link2 . '\' target=\'_blank\'>' . $link2 . '</a><br /><br />';


            $html .= '<table style=\'border: solid 1px;\'>';

            $csrFieldMap = [
                'countryName' => 'Country',
                'stateOrProvinceName' => 'State',
                'localityName' => 'Locality',
                'organizationName' => 'Organization',
                'organizationalUnitName' => 'Organization Unit',
                'commonName' => 'Common Name',
                'emailAddress' => 'Email',
            ];

            foreach ($reply as $key => $value) {
                $html .= '<tr style=\'border: solid 1px;\'><td style=\'border: solid 1px;\'>' . $key . '</td><td>';

                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        // fix if $v is array
                        if (is_array($v)) {
                            foreach ($v as $kk => $vv) {
                                    $html .= $kk ? $kk . ':' . $vv . '<br />' : nl2br($vv) . '<br />';
                            }
                        } else {
                            $html .= $k ? $k . ':' . $v . '<br />' : nl2br($v) . '<br />';
                        }
                    }
                } else {
                    $html .= nl2br($value);

                    if ($key === 'csr' && $value) {
                        $csrData = opApiWrapper::processRequest(
                            'decodeCsrSslCertRequest',
                            opApiWrapper::buildParams($apiCredentials),
                            ['csr' => $value]
                        );

                        $html .= '<br /><strong>Decoded CSR:</strong><br /><table>';

                        foreach ($csrData as $k => $v) {
                            $html .= '<tr><td><b>' . $csrFieldMap[$k] . '</b>:</td><td>' . $v . '</td></tr>';
                        }

                        $html .= '</table>';
                    }
                }

                $html .= '</td></tr>' . PHP_EOL;
            }

            $html .= '</table>';
        }

        $fieldsarray = ['Certificate Info' => $html];
    } else {
        $fieldsarray = ['Certificate Info' => '<input type="button" value="View Info" onclick="window.location=\'?userid=' . $params['clientdetails']['userid'] . '&id=' . $params['serviceid'] . '&viewDetails\'" />'];
    }


    return $fieldsarray;
}
