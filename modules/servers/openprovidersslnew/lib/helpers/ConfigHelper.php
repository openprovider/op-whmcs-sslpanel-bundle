<?php

/**
 * Class ConfigHelper
 */
class ConfigHelper
{
    /**
     * @var array
     */
    private static $serverParamsMap = [
        'production' => [
            'username' => 'configoption1',
            'password' => 'configoption2',
            'opApiUrl' => 'configoption3',
            'sslPanelUrl' => 'configoption4',
        ],
        'test' => [
            'username' => 'configoption7',
            'password' => 'configoption8',
            'opApiUrl' => 'configoption9',
            'sslPanelUrl' => 'configoption10',
        ],
    ];

    /**
     * @var array
     */
    private static $addonParamsMap = [
        'production' => [
            'opApiUrl' => 'option1',
            'username' => 'option2',
            'password' => 'option3',
            'sslPanelUrl' => 'option4',
            'opRcpUrl' => 'option5',
        ],
        'test' => [
            'opApiUrl' => 'option7',
            'username' => 'option8',
            'password' => 'option9',
            'sslPanelUrl' => 'option10',
            'opRcpUrl' => 'option11',
        ],
    ];

    /**
     * @param array $params
     *
     * @return array
     */
    public static function getServerCredentialsArray(array $params)
    {
        $config = ConfigHelper::getServerConfigurationFromParams(
            $params,
            EnvHelper::getServerEnvironmentFromParams($params)
        );

        return [
            'username' => ArrayHelper::getValue($config, 'username'),
            'password' => ArrayHelper::getValue($config, 'password'),
            'apiUrl' => ArrayHelper::getValue($config, 'opApiUrl'),
        ];
    }

    /**
     * @param array  $params
     * @param string $env
     *
     * @return array
     */
    public static function getServerConfigurationFromParams(array $params, $env = 'production')
    {
        return self::getConfigurationFromParamsAndMap($params, ArrayHelper::getValue(self::$serverParamsMap, $env));
    }

    /**
     * @param array $params
     * @param array $map
     *
     * @return array
     */
    private static function getConfigurationFromParamsAndMap(array $params, array $map)
    {
        $configuration = [];

        foreach ($map as $key => $optionKey) {
            $configuration[$key] = ArrayHelper::getValue($params, $optionKey);
        }

        return $configuration;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public static function getAddonCredentialsArray(array $params)
    {
        $config = ConfigHelper::getAddonConfigurationFromParams(
            $params,
            EnvHelper::getAddonEnvironmentFromParams($params)
        );

        return [
            'username' => ArrayHelper::getValue($config, 'username'),
            'password' => ArrayHelper::getValue($config, 'password'),
            'apiUrl' => ArrayHelper::getValue($config, 'opApiUrl'),
        ];
    }

    /**
     * @param array  $params
     * @param string $env
     *
     * @return array
     */
    public static function getAddonConfigurationFromParams(array $params, $env = 'production')
    {
        return self::getConfigurationFromParamsAndMap($params, ArrayHelper::getValue(self::$addonParamsMap, $env));
    }
}