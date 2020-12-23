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
                'FriendlyName' => 'Openprovider API URL',
                'Type' => 'text',
                'Size' => '255',
                'Default' => 'https://api.cte.openprovider.eu',
            ],
            'option2' => [
                'FriendlyName' => 'API Username',
                'Type' => 'text',
                'Size' => '25',
            ],
            'option3' => [
                'FriendlyName' => 'API Password',
                'Type' => 'password',
                'Size' => '25',
            ],
            'option4' => [
                'FriendlyName' => 'SSL Panel URL',
                'Type' => 'text',
                'Size' => '255',
                'Default' => 'https://sslinhva.cte.openprovider.eu',
            ],
            'option5' => [
                'FriendlyName' => 'Openprovider RCP URL',
                'Type' => 'text',
                'Size' => '255',
                'Default' => 'https://rcp.cte.openprovider.eu',
            ],
            'option6' => [
                'FriendlyName' => '!TEST! Mode?',
                'Type' => 'yesno',
            ],
            'option7' => [
                'FriendlyName' => '!TEST! Openprovider API URL',
                'Type' => 'text',
                'Size' => '255',
                'Default' => 'https://api.cte.openprovider.eu',
            ],
            'option8' => [
                'FriendlyName' => '!TEST! API Username',
                'Type' => 'text',
                'Size' => '25',
            ],
            'option9' => [
                'FriendlyName' => '!TEST! API Password',
                'Type' => 'password',
                'Size' => '25',
            ],
            'option10' => [
                'FriendlyName' => '!TEST! SSL Panel URL',
                'Type' => 'text',
                'Size' => '255',
                'Default' => 'https://sslinhva.cte.openprovider.eu',
            ],
            'option11' => [
                'FriendlyName' => '!TEST! Openprovider RCP URL',
                'Type' => 'text',
                'Size' => '255',
                'Default' => 'https://rcp.cte.openprovider.eu',
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
            'overview_orders_url' => $vars['option5'],
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
                $params = [
                    'name' => 'Name of my new product3',
                    'description' => 'Description of my new product',
                    'gid' => $_POST['product-group-id'], // id of product group
                    'type' => 'other',
                    'hidden' => false,
                    'paytype' => 'recurring',
                    'module' => 'openprovidersslnew',
                    'configoption1' => $vars['option2'],
                    'configoption2' => $vars['option3'],
                    'configoption3' => $vars['option1'],
                    'configoption4' => $vars['option4'],
                    'configoption5' => $vars['option5'],
                    'configoption6' => 'EssentialSSL', // EssentialSSL and etc
                    'pricing' => [
                        1 => [
                            'monthly' => 8.00,
                            'quarterly' => null,
                            'semiannually' => null,
                            'annually' => 80.00,
                            'biennially' => null,
                            'triennially' => null,
                        ],
                    ],
                ];
                $result = localAPI('AddProduct', $params);
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
