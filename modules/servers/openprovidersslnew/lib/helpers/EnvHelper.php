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
    public static function getServerEnvironmentFromParams($params)
    {
        $params = (array)$params;

        return ArrayHelper::getValue($params, 'configoption5') ? 'test' : 'production';
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public static function getAddonEnvironmentFromParams($params)
    {
        $params = (array)$params;

        return ArrayHelper::getValue($params, 'option6') ? 'test' : 'production';
    }
}
