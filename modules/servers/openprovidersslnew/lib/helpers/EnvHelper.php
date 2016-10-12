<?php

/**
 * Class EnvHelper
 */
class EnvHelper
{
    /**
     * @param array $params
     *
     * @return string
     */
    public static function getServerEnvironmentFromParams(array $params)
    {
        return ArrayHelper::getValue($params, 'configoption6') ? 'test' : 'production';
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public static function getAddonEnvironmentFromParams(array $params)
    {
        return ArrayHelper::getValue($params, 'option6') ? 'test' : 'production';
    }
}