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
        "SSL Certificate Type" => [
            "Type" => "dropdown",
            "Options" => "Symantec Secure Site," .
                "Symantec Secure Site Pro," .
                "Symantec Secure Site with EV," .
                "Symantec Secure Site Pro with EV," .
                "GeoTrust QuickSSL Premium," .
                "GeoTrust True Business ID," .
                "GeoTrust True Business ID with EV," .
                "GeoTrust True Business ID Multi-Domain," .
                "GeoTrust True Business ID with EV Multi-Domain," .
                "GeoTrust True Business ID Wildcard," .
                "thawte SSL123,thawte Web Server," .
                "thawte Web Server with EV," .
                "thawte Web Server Wildcard," .
                "thawte SGC SuperCert," .
                "Comodo EssentialSSL," .
                "Comodo InstantSSL," .
                "Comodo InstantSSL Pro," .
                "Comodo PremiumSSL," .
                "Comodo EV SSL," .
                "Comodo EV SGC SSL," .
                "Comodo Instant SGC SSL," .
                "Comodo Unified Communications Certificate," .
                "Comodo EVSSL Multi-Domain," .
                "Comodo EssentialSSL Wildcard," .
                "Comodo PremiumSSL Wildcard," .
                "Comodo Instant SGC Wildcard SSL," .
                "RapidSSL," .
                "RapidSSL Wildcard",
        ],
        "Validity Period" => [
            "Type" => "dropdown",
            "Options" => "1,2,3",
            "Description" => "Years",
        ],
        "" => [
            "Type" => "na",
            "Description" => "Do not have a Openprovider SSL account? Visit <a href=\"https://www.openprovider.co.uk/register/\" target=\"_blank\">www.openprovider.co.uk/</a> to signup free.",
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
