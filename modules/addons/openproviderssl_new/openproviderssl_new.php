<?php

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
            foreach ($reply['results'] as $product) {
                mysql_query(
                    "INSERT INTO table (`id`, `product_id`, `name`, `brand_name`, `price`, `currency`, `changed_at`) VALUES "
                    . "("
                    . "NULL" . ","
                    . $product['id'] . ","
                    . $product['name'] . ","
                    . $product['brandName'] . ","
                    . $product['warranty']['reseller']['price'] . ","
                    . $product['warranty']['reseller']['currency'] . ","
                    . date('Y-m-d H:i:s', time()) . ","
                    .")"
                );
            }
        } catch (opApiException $e) {
            $view['errorMessage'] = $e->getMessage();
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
    $queryString = " CREATE TABLE IF NOT EXISTS `openprovidersslnew_products` (
                                      `id` INT AUTO_INCREMENT ,
                                      `product_id` INT ,
                                      `name` VARCHAR( 255 ) ,
                                      `brand_name` VARCHAR( 255 ) ,
                                      `price` FLOAT,
                                      `currency`
                                      `changed_at` VARCHAR( 19 ) ,
                                      PRIMARY KEY ( `id` )
                        ) ENGINE = MYISAM ";
    mysql_query($queryString);
}

function openproviderssl_new_deactivate()
{
    return array('status' => 'success', 'description' => '');
    //return array('status'=>'error','description'=>'If an error occurs you can return an error message for display here');
    //return array('status'=>'info','description'=>'If you want to give an info message to a user you can return it here');
}
