<?php

use Illuminate\Database\Capsule\Manager as Capsule;

function openproviderssl_new_config()
{
    return [
        "name" => "Openprovidersslnew addon",
        "description" => "Openprovidersslnew addon for interaction with OP API",
        "version" => "1.0",
        "author" => "Openprovider",
        "fields" => [
            "option1" => [
                "FriendlyName" => "apiUrl",
                "Type" => "text", "Size" => "255",
                "Description" => "Openprovider Api Url",
                "Default" => "https://api.cte.openprovider.eu/",
            ],
            "option2" => [
                "FriendlyName" => "username",
                "Type" => "text",
                "Size" => "25",
            ],
            "option3" => [
                "FriendlyName" => "password",
                "Type" => "password",
                "Size" => "25",
            ],
        ]
    ];
}

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
        ],
    ];

    $reply = null;

    if ($action === 'list') {
        try {
            $reply = search_products($vars);
        } catch (opApiException $e) {
            $view['errorMessage'] = $e->getMessage();
        }

        $view['products'] = $reply['results'];
    } else if ($action === 'update') {
        try {
            $reply = search_products($vars);
        } catch (opApiException $e) {
            $view['errorMessage'] = $e->getMessage();
        }
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('truncate openprovidersslnew_products');
            $statement->execute();

            foreach ($reply['results'] as $product) {
                //todo: INSERT INTO...
                $statement = $pdo->prepare('INSERT INTO openprovidersslnew_products (id, product_id, name, brand_name, price, currency, changed_at) VALUES (:id, :product_id, :name, :brand_name, :price, :currency, :changed_at)');
                $statement->execute([
                    ':id' => null,
                    ':product_id' => $product['id'],
                    ':name' => $product['name'],
                    ':brand_name' => $product['brandName'],
                    ':price' => $product['warranty']['reseller']['price'],
                    ':currency' => $product['warranty']['reseller']['currency'],
                    ':changed_at' => date('Y-m-d H:i:s', time()),
                ]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $view['errorMessage'] = "Unable to update openprovidersslnew_products: {$e->getMessage()}";
            $pdo->rollBack();
        }
    } else {
        $action = 'default';
    }

    $view['global']['mod_action_url'] = $view['global']['mod_url'] . '&action=' . $action;
    $view['global']['action'] = $action;

    include dirname(__FILE__) . '/templates/' . $action . '.php';
}

/**
 * @param $vars
 *
 * @return array|null
 */
function search_products($vars)
{
    include __DIR__ . '/../../servers/openprovidersslnew/lib/opApiWrapper.php';

    $reply = opApiWrapper::searchProductSslCert([
        'apiUrl' => $vars['option1'],
        'username' => $vars['option2'],
        'password' => $vars['option3'],
    ]);

    return $reply;
}

function openproviderssl_new_activate()
{
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

function openproviderssl_new_deactivate()
{
    return array('status' => 'success', 'description' => '');
    //return array('status'=>'error','description'=>'If an error occurs you can return an error message for display here');
    //return array('status'=>'info','description'=>'If you want to give an info message to a user you can return it here');
}
