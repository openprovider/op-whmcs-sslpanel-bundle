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

    if ('list' == $action) {
        include __DIR__ . '/../../servers/openprovidersslnew/lib/opApiWrapper.php';
        $reply = null;

        try {
            $reply = opApiWrapper::searchProductSslCert([
		'apiUrl' => $vars['option1'],
		'username' => $vars['option2'],
		'password' => $vars['option3'],
            ]);
        } catch (opApiException $e) {
	    $view['errorMessage'] = $e->getMessage();
        }

        $view['products'] = $reply['results'];
    } else {
        $action = 'default';
    }

    $view['global']['mod_action_url'] = $view['global']['mod_url'] . '&action=' . $action;
    $view['global']['action'] = $action;

    include dirname(__FILE__) . '/templates/' . $action . '.php';
}
