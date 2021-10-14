<?php

/**
 * Class LogHelper
 */
class LogHelper
{
    const LOG_MODULE_NAME = 'openprovidersslnew';

    /**
     * @param string $command action name
     * @param array|string $request
     * @param array|string $response
     * @param array|string $data
     * @param array $dataToMask
     */
    public static function log($command, $request = '', $response = '', $data = '', $dataToMask = [])
    {
        logModuleCall(
            self::LOG_MODULE_NAME,
            $command,
            $request,
            $response,
            $data,
            $dataToMask
        );
    }
}
