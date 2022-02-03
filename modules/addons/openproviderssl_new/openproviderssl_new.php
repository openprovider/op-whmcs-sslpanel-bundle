<?php

use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/../../servers/openprovidersslnew/vendor/autoload.php';

/**
 * @return array
 */
function openproviderssl_new_config()
{
    return [
        'name' => 'Openprovider SSL Panel',
        'description' => 'Addon for Openprovider SSL Panel',
        'version' => '2.0.1',
        'author' => 'Openprovider',
        'fields' => [
            'option1' => [
                'FriendlyName' => 'API Username',
                'Type' => 'text',
                'Size' => '25',
            ],
            'option2' => [
                'FriendlyName' => 'API Password',
                'Type' => 'password',
                'Size' => '25',
            ],
            'option3' => [
                'FriendlyName' => 'Test mode',
                'Type' => 'yesno',
            ],
        ],
    ];
}

/**
 * @param array $vars
 */
function openproviderssl_new_output($vars)
{
    if (!empty($_REQUEST['action'])) {
        $action = $_REQUEST['action'];
    } else {
        $action = 'default';
    }

    $view = [
        'global' => [
            'mod_url' => '?module=openproviderssl_new',
            'module' => 'openproviderssl_new',
            'overview_orders_url' => ConfigHelper::getRcpUrlFromConfig(),
        ],
    ];

    $reply = null;

    if ($action === 'list') {
        try {
            $reply = searchProducts($vars);
        } catch (opApiException $e) {
            $view['errorMessage'] = $e->getFullMessage();
        }

        $view['products'] = $reply['results'];
    } else {
        if ($action === 'update') {
            try {
                $reply = searchProducts($vars);

                Capsule::table('openprovidersslnew_products')->truncate();
                foreach ($reply['results'] as $product) {
                    Capsule::table('openprovidersslnew_products')->insert([
                        'id' => null,
                        'product_id' => $product['id'],
                        'name' => $product['name'],
                        'brand_name' => $product['brandName'],
                        'price' => $product['prices'][0]['price']['reseller']['price'],
                        'max_period' => $product['maxPeriod'],
                        'number_of_domains' => $product['numberOfDomains'],
                        'max_domains' => $product['maxDomains'],
                        'currency' => $product['prices'][0]['price']['reseller']['currency'],
                        'changed_at' => date('Y-m-d H:i:s', time()),
                    ]);
                }
            } catch (opApiException $e) {
                $view['errorMessage'] = "Unable to retrieve products: {$e->getFullMessage()}";
            } catch (\Exception $e) {
                $view['errorMessage'] = "Unable to update openprovidersslnew_products: {$e->getMessage()}";
            }
        } elseif ($action === 'import-ssl-products') {
            $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
            if ($isPost) {
                try {
                    $products = searchProducts($vars);
                    foreach ($products['results'] as $product) {
                        $annuallyPrice = floatval($product['prices'][0]['price']['product']['price']);
                        $bienniallyPrice = floatval($product['prices'][1]['price']['product']['price']);
                        $annuallyMargin = floatval($_POST['margin-percent']) / 100 * $annuallyPrice;
                        $bienniallyMargin = floatval($_POST['margin-percent']) / 100 * $bienniallyPrice;

                        $params = [
                            'name' => $product['name'],
                            'description' => $product['brandName'],
                            'gid' => $_POST['product-group-id'],
                            'type' => 'other',
                            'hidden' => false,
                            'paytype' => 'recurring',
                            'module' => 'openprovidersslnew',
                            'configoption1' => $vars['option1'],
                            'configoption2' => $vars['option2'],
                            'configoption3' => $product['name'],
                            'pricing' => [
                                1 => [
                                    'monthly' => -1,
                                    'quarterly' => -1,
                                    'semiannually' => -1,
                                    'annually' => $annuallyPrice + $annuallyMargin,
                                    'biennially' => $bienniallyPrice + $bienniallyMargin,
                                    'triennially' => -1,
                                ],
                            ],
                        ];
                        $result = localAPI('AddProduct', $params);
                        if ($result['result'] === 'error') {
                            throw new Exception($result['message']);
                        }
                    }

                } catch (opApiException $e) {
                    $view['errorMessage'] = "Unable to retrieve products: {$e->getFullMessage()}";
                } catch (\Exception $e) {
                    $view['errorMessage'] = "Unable to update openprovidersslnew_products: {$e->getMessage()}";
                }
            }
        } else {
            $action = 'default';
        }
    }

    $view['global']['mod_action_url'] = $view['global']['mod_url'] . '&action=' . $action;
    $view['global']['action'] = $action;

    require __DIR__ . '/templates/' . $action . '.php';
}

/**
 * @param $vars
 *
 * @return array|null
 */
function searchProducts($vars)
{
    $reply = opApiWrapper::searchProductSslCert(ConfigHelper::getAddonCredentialsArray($vars));

    return $reply;
}

/**
 *
 */
function openproviderssl_new_activate()
{
    //todo: try via Exception classes
    try {
        Capsule::schema()->create(
            'openprovidersslnew_products',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->integer('product_id');
                $table->string('name');
                $table->string('brand_name');
                $table->float('price');
                $table->string('currency');
                $table->integer('max_period');
                $table->integer('number_of_domains');
                $table->integer('max_domains');
                $table->string('changed_at', 19);
                $table->primary(['id']);
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create openprovidersslnew_products: {$e->getMessage()}";
    }

    try {
        Capsule::schema()->create(
            'openprovidersslnew_orders',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->integer('product_id');
                $table->integer('order_id');
                $table->string('status', 32);
                $table->string('creation_date', 19);
                $table->string('activation_date', 19);
                $table->string('expiration_date', 19);
                $table->string('changed_at', 19);
                $table->integer('service_id');
                $table->primary(['id']);
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create openprovidersslnew_orders: {$e->getMessage()}";
    }
}

/**
 * @return array
 */
function openproviderssl_new_deactivate()
{
    return ['status' => 'success', 'description' => ''];
    //return array('status'=>'error','description'=>'If an error occurs you can return an error message for display here');
    //return array('status'=>'info','description'=>'If you want to give an info message to a user you can return it here');
}

function openproviderssl_new_upgrade($vars)
{
    $version = $vars['version'];

    if ($version < 2.0) {
        //todo: try via Exception classes
        try {
            Capsule::schema()->table(
                'openprovidersslnew_products',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->integer('max_period');
                    $table->integer('number_of_domains');
                    $table->integer('max_domains');
                }
            );
        } catch (\Exception $e) {
            echo "Unable to update openprovidersslnew_products: {$e->getMessage()}";
        }
    }
}
