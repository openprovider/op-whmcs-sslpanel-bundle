<?php

class opConfig
{
    /* $trusteeAvailableFor defines TLDs for which Openprovider's
       trustee service will be used. Uncomment those TLDs for which
       you need to use trustee. Please note that additional costs can apply.
    */
    static public $trusteeAvailableFor = [
        //'ba',
        //'cn',
        //'co.id',
        //'co.rs',
        //'com.ar',
        //'com.cn',
        //'com.my',
        //'de',
        //'fr',
        //'my',
        //'net.cn',
        //'net.my',
        //'org.cn',
        //'org.my',
        //'rs',
        //'web.id',
    ];


    /* This parameter defines the behaviour of the "Do Not Renew" checkbox.

       A checked "Do Not Renew" checkbox will always set the domain's auto-renew
       status to "off", letting the domain expire at the end of the period.

       An unchecked "Do Not Renew" checkbox will trigger the auto-renew status
       for the domain as set in the parameter below. Accepted values are
       "on" and "default" to use the Openprovider account's default setting.
    */
    static public $renewBehaviour = 'on';
}
