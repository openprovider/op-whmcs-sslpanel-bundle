<?php

include './lib/opApiWrapper.php';

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
        "Username" => [
            "Type" => "text",
            "Size" => "25",
            "Description" => "Openprovider login",
        ],
        "Password" => [
            "Type" => "password",
            "Size" => "25",
            "Description" => "Openprovider password",
        ],
        "OpenproviderAPI" => [
            "Type" => "text",
            "Size" => "60",
            "Description" => "Openprovider API URL",
        ],
    ];
}

function openprovidersslnew_ClientAreaCustomButtonArray()
{
    return [
        "Search ssl products" => "search",
    ];
}

function openprovidersslnew_search($params)
{
    $products = opApiWrapper::processRequest('searchProductSslCertRequest', $params, []);

    logModuleCall(
        'openprovidersslnew',
        'search',
        $params,
        $products,
        '',
        [$params["configoption1"],$params["configoption2"]]
    );

    return array(
        'templatefile' => 'search',
        'breadcrumb' => [
            'products.php?action=search' => 'search',
        ],
        'vars' => array(
            'products' => $products,
        ),
    );
}
