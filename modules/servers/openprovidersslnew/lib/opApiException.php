<?php

class opApiException extends Exception
{
    /*
     * 4xx - wrong requests or wrong Openprovider responses
     */
    const ERR_INTERNAL_ERROR = 400;
    const ERR_OP_API_EXCEPTION = 401;

    const ERR_WHMCS_EXCEPTION = 501;

    const ERR_REGISTRY_EXCEPTION = 601;

    static public $outputMessages = [
        0 => 'No errors',
        /*
         * 4xx - wrong requests or wrong Openprovider responses
         */
        self::ERR_INTERNAL_ERROR => 'Internal plugin error',
        self::ERR_OP_API_EXCEPTION => 'Openprovider API error',

        self::ERR_WHMCS_EXCEPTION => 'Error',

        self::ERR_REGISTRY_EXCEPTION => 'Registry error',
    ];

    protected $additionalInfo, $infoCode;

    public function __construct($code = 0, $additionalInfo = '', $infoCode = 0)
    {
        if (!array_key_exists($code, self::$outputMessages)) {
            $code = self::ERR_INTERNAL_ERROR;
        }
        $this->code = $code;
        $this->message = self::$outputMessages[$code];
        $this->additionalInfo = $additionalInfo;
        $this->infoCode = $infoCode;
    }

    public function getFullMessage()
    {
        $message = $this->getMessage();
        if ($this->additionalInfo != '') {
            $message .= ': ' . $this->additionalInfo;
        }

        return $message;
    }

    public function getInfoCode()
    {
        return $this->infoCode;
    }
}
