<?php

function hook_openprovidernewssl_acceptorder(array $params)
{
    $results = localAPI("getorders", ['id' => $params['orderid']], "admin");

    logModuleCall(
        'openprovidersslnew',
        'hook',
        $params,
        $results,
        [],
        [$params["configoption1"], $params["configoption2"]]
    );
}

add_hook('AcceptOrder', 1, 'hook_openprovidernewssl_acceptorder');
