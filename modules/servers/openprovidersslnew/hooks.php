<?php

function hook_openprovidernewssl_acceptorder(array $params)
{
    $reply = null;

    try {
        $reply = opApiWrapper::createSslCert($params, 41);
    } catch (\Exception $e) {
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
}

add_hook('AcceptOrder', 1, 'hook_openprovidernewssl_acceptorder');
