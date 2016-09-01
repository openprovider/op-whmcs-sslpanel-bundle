<?php

include __DIR__.'/lib/opApiWrapper.php';

/**
 * @return array
 */
function openprovidersslnew_MetaData()
{
    return array(
        'DisplayName' => 'Openprovider ssl provisioning module',
        'APIVersion' => '1.1', // Use API Version 1.1
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
        "SSL Certificate Type" => [
            "Type" => "dropdown",
            "Options" => "PositiveSSL",
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
    $reply = null;

    try {
        $reply = opApiWrapper::createSslCert($params, 41);
    } catch (opApiException $e) {
        logModuleCall(
            'openprovidersslnew',
            'hook_openprovidernewssl_acceptorder',
            $params,
            $reply,
            [
                'errorMessage' => $e->getMessage(),
            ],
            [$params["configoption1"], $params["configoption2"]]
        );
    }

    return "success";   
}

