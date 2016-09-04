<?php

function openproviderssl_new_config()
{
    return [
        "name" => "Openprovidersslnew addon",
        "description" => "Openprovidersslnew addon config",
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
                "Type" => "username",
                "Size" => "25",
                "Description" => "Username",
            ],
            "option3" => [
                "FriendlyName" => "password",
                "Type" => "password",
                "Size" => "25",
                "Description" => "Password",
            ],
        ]
    ];
}

function openproviderssl_new_output($vars)
{
    echo '<p>The date & time are currently '.date("Y-m-d H:i:s").'</p>';
}