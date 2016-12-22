<?php

/**
 * Class AddressHelper
 */
class AddressHelper
{
    /**
     * Format address
     *
     * @param string
     *
     * @return array
     */
    public static function parseAddress($fullAddress)
    {
        $matches = [];

        if (preg_match('/^(\d+),?(.+)$/', $fullAddress, $matches)) {
            $fullAddress = trim($matches[2] . ' ' . $matches[1]);
            // processing for US-styled addresses which start with the number
        }

        $tmp = explode(' ', $fullAddress);

        // Take care of nasty suffixes
        $tmpSuffix = end($tmp);
        $matches = [];

        if (preg_match('/^([\d]+)(\D.*)$/', $tmpSuffix, $matches)) {
            array_pop($tmp);
            $tmp[] = $matches[1];
            $tmp[] = trim($matches[2], " \t\n\r\0-");
        }

        $addressLength = count($tmp);
        $street = $tmp[0];
        $number = '';
        $suffix = '';
        $cnt = 1;

        while (($cnt < $addressLength) && !is_numeric($tmp[$cnt])) {
            $street .= ' ' . $tmp[$cnt];
            $cnt++;
        }

        if ($cnt < $addressLength) {
            $number = $tmp[$cnt];
            $cnt++;

            while ($cnt < $addressLength) {
                $suffix .= $tmp[$cnt] . ' ';
                $cnt++;
            }
        }

        return ['street' => $street, 'number' => $number, 'suffix' => $suffix];
    }
}