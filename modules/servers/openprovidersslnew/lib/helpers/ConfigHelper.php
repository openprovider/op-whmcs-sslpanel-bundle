<?php

/**
 * Class ConfigHelper
 */
class ConfigHelper
{
    const ENV_PRODUCTION = 'production';
    const ENV_TEST = 'test';

    /**
     * @var array
     */
    private static $serverParamsMap = [
        self::ENV_PRODUCTION => [
            'username' => 'configoption1',
            'password' => 'configoption2',
            'opRcpUrl' => 'configoption3',
            'defaultTechnicalContact' => 'configoption10',
            'defaultLanguage' => 'configoption12',
        ],
        self::ENV_TEST => [
            'username' => 'configoption6',
            'password' => 'configoption7',
            'opRcpUrl' => 'configoption8',
            'defaultTechnicalContact' => 'configoption11',
            'defaultLanguage' => 'configoption12',
        ],
    ];

    private static $parametersToMaskInLogs = [
        'configoption2',
        'configoption7',
    ];

    /**
     * @var array
     */
    private static $addonParamsMap = [
        self::ENV_PRODUCTION => [
            'username' => 'option1',
            'password' => 'option2',
            'opRcpUrl' => 'option3',
        ],
        self::ENV_TEST => [
            'username' => 'option5',
            'password' => 'option6',
            'opRcpUrl' => 'option7',
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
            'apiUrl' => self::getApiUrlFromConfig(EnvHelper::getServerEnvironmentFromParams($params)),
            'clientInformation' => self::getClientInformationFrom($params),
        ];
    }

    /**
     * @param array  $params
     * @param string $env
     *
     * @return array
     */
    public static function getServerConfigurationFromParams($params, $env = self::ENV_PRODUCTION)
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
            'apiUrl' => self::getApiUrlFromConfig(EnvHelper::getAddonEnvironmentFromParams($params)),
            'clientInformation' => self::getClientInformationFrom($params),
        ];
    }

    /**
     * @param array  $params
     * @param string $env
     *
     * @return array
     */
    public static function getAddonConfigurationFromParams($params, $env = self::ENV_PRODUCTION)
    {
        return self::getConfigurationFromParamsAndMap($params, ArrayHelper::getValue(self::$addonParamsMap, $env));
    }

    /**
     * @param $params
     * @param array $additionalParameterNames
     *
     * @return array
     */
    public static function getParametersToMaskInLogs($params, $additionalParameterNames = [])
    {
        $result = [];
        $paramNames = array_merge(self::$parametersToMaskInLogs, $additionalParameterNames);

        foreach ($paramNames as $paramName) {
            $result[] = $params[$paramName];
        }

        return $result;
    }

    /**
     * @param string $env
     *
     * @return string Openprovider api url
     */
    public static function getApiUrlFromConfig($env = self::ENV_PRODUCTION)
    {
        return $env == self::ENV_PRODUCTION ?
            opConfig::$apiUrl :
            opConfig::$apiCteUrl;
    }

    /**
     * @param string $env
     *
     * @return string Ssl panel url
     */
    public static function getSslPanelUrlFromConfig($env = self::ENV_PRODUCTION)
    {
        return $env == self::ENV_PRODUCTION ?
            opConfig::$sslPanelUrl :
            opConfig::$sslPanelCteUrl;
    }

    /**
     * @param string $env
     *
     * @return string Openprovider reseller control panel url
     */
    public static function getRcpUrlFromConfig($env = self::ENV_PRODUCTION)
    {
        return $env == self::ENV_PRODUCTION ?
            opConfig::$rcpUrl :
            opConfig::$rcpCteUrl;
    }
}
