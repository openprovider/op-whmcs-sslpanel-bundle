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
            'opRcpUrl' => 'configoption5',
            'defaultTechnicalContact' => 'configoption13',
            'defaultLanguage' => 'configoption15',
        ],
        'test' => [
            'username' => 'configoption8',
            'password' => 'configoption9',
            'opApiUrl' => 'configoption10',
            'sslPanelUrl' => 'configoption11',
            'opRcpUrl' => 'configoption12',
            'defaultTechnicalContact' => 'configoption14',
            'defaultLanguage' => 'configoption15',
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
    public static function getServerCredentialsArray($params)
    {
        $config = ConfigHelper::getServerConfigurationFromParams(
            $params,
            EnvHelper::getServerEnvironmentFromParams($params)
        );

        return [
            'username' => ArrayHelper::getValue($config, 'username'),
            'password' => ArrayHelper::getValue($config, 'password'),
            'apiUrl' => ArrayHelper::getValue($config, 'opApiUrl'),
            'clientInformation' => self::getClientInformationFrom($params),
        ];
    }

    /**
     * @param array  $params
     * @param string $env
     *
     * @return array
     */
    public static function getServerConfigurationFromParams($params, $env = 'production')
    {
        return self::getConfigurationFromParamsAndMap($params, ArrayHelper::getValue(self::$serverParamsMap, $env));
    }

    /**
     * @param array $params
     * @param array $map
     *
     * @return array
     */
    private static function getConfigurationFromParamsAndMap($params, array $map)
    {
        $params = (array)$params;
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
    private static function getClientInformationFrom(array $params)
    {
        return [
            'name' => 'whmcs',
            'version' => ArrayHelper::getValue($params, 'whmcsVersion'),
        ];
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public static function getAddonCredentialsArray($params)
    {
        $config = ConfigHelper::getAddonConfigurationFromParams(
            $params,
            EnvHelper::getAddonEnvironmentFromParams($params)
        );

        return [
            'username' => ArrayHelper::getValue($config, 'username'),
            'password' => ArrayHelper::getValue($config, 'password'),
            'apiUrl' => ArrayHelper::getValue($config, 'opApiUrl'),
            'clientInformation' => self::getClientInformationFrom($params),
        ];
    }

    /**
     * @param array  $params
     * @param string $env
     *
     * @return array
     */
    public static function getAddonConfigurationFromParams($params, $env = 'production')
    {
        return self::getConfigurationFromParamsAndMap($params, ArrayHelper::getValue(self::$addonParamsMap, $env));
    }
}