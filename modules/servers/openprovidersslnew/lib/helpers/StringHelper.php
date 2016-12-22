<?php
use Behat\Transliterator\Transliterator;

/**
 * Class StringHelper
 */
class StringHelper
{
    /**
     * @param $string
     *
     * @return string
     */
    public static function toAscii($string)
    {
        $string = html_entity_decode($string, ENT_COMPAT, 'UTF-8');

        if (preg_match('/[\x80-\xff]/', $string) && Transliterator::validUtf8($string)) {
            $string = Transliterator::utf8ToAscii($string);
        }

        return $string;
    }
}