<?php

require_once(dirname(__FILE__) . '/API.php');
require_once(dirname(__FILE__) . '/opApiWrapper.php');
require_once(dirname(__FILE__) . '/opApiTools.php');
require_once(dirname(__FILE__) . '/opApiException.php');

function openprovider_getConfigArray()
{
    $configarray = array(
        "OpenproviderAPI" => array("Type" => "text", "Size" => "60", "Description" => "Openprovider API URL",),
        "Username" => array("Type" => "text", "Size" => "20", "Description" => "Openprovider login",),
        "Password" => array("Type" => "password", "Size" => "20", "Description" => "Openprovider password",),
    );
    return $configarray;
}

function openprovider_GetNameservers($params)
{
    try {
        $values = array();
        opApiTools::prepareNameservers($params, $values);
        opApiTools::updateDomainData($params, $domainInfo, true);
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_SaveNameservers($params)
{
    try {
        opApiWrapper::modifyDomain($params, array('nameServers' => opApiTools::createNameserversArray($params)));
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_GetRegistrarLock($params)
{
    try {
        $lockstatus = "unlocked";
        $domainInfo = opApiWrapper::infoDomain($params);
        if ($domainInfo['isLocked'] == "1") {
            $lockstatus = "locked";
        }
    } catch (opApiException $e) {
    }
    return $lockstatus;
}

function openprovider_SaveRegistrarLock($params)
{
    try {
        opApiWrapper::modifyDomain($params, array('isLocked' => $params["lockenabled"] == "locked" ? "1" : "0"));
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_GetDNS($params)
{
    $hostrecords = array();
    try {
        $zoneInfo = opApiWrapper::infoDNSZone($params);
        if (!is_null($zoneInfo)) {
            $whmcsDNStypes = array('A', 'MXE', 'MX', 'CNAME', 'TXT', 'URL', 'FRAME');
            $domainName = $params["sld"] . '.' . $params["tld"];
            foreach ($zoneInfo['records'] as $dnsRecord) {
                if (in_array($dnsRecord['type'], $whmcsDNStypes)) {
                    $hostname = $dnsRecord['name'];
                    if ($hostname == $domainName) {
                        $hostname = '';
                    } else {
                        $pos = stripos($hostname, '.' . $domainName);
                        if ($pos !== false) {
                            $hostname = substr($hostname, 0, $pos);
                        }
                    }
                    $prio = is_numeric($dnsRecord['prio']) ? $dnsRecord['prio'] : '';
                    $hostrecords[] = array("hostname" => $hostname,
                        "type" => $dnsRecord['type'],
                        "address" => $dnsRecord['value'],
                        "priority" => $prio);
                }
            }
        }
    } catch (opApiException $e) {
    }
    return $hostrecords;
}

function openprovider_SaveDNS($params)
{
    foreach ($params["dnsrecords"] as $rec) {
        $hostname = $rec["hostname"];
        $address = $rec["address"];
        if (!(empty($hostname) && empty($address))) {
            $type = $rec["type"];
            $dnsRecord = array('type' => $type,
                'name' => $hostname,
                'value' => $address,
                'ttl' => 86400);
            if ($type == 'MX') {
                $dnsRecord['prio'] = is_numeric($rec["priority"]) ? $rec["priority"] : 10;
            }
            $zoneParams[] = $dnsRecord;
        }
    }
    try {
        opApiWrapper::modifyDNSZone($params, $zoneParams);
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_RegisterDomain($params)
{
    try {
        opApiWrapper::registerDomain($params, opApiTools::prepareRegisterOrTransferParameters($params));
        $domainInfo = opApiWrapper::infoDomain($params);
        if ($domainInfo['status'] != 'ACT') {
            $values["error"] = 'Domain registration is pending (this is not an error, just an information message).';
        }
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_RequestDelete($params)
{
    try {
        opApiWrapper::deleteDomain($params);
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_TransferDomain($params)
{
    try {
        $transferParams = opApiTools::prepareRegisterOrTransferParameters($params);
        $transferParams['authCode'] = $params["transfersecret"];
        opApiWrapper::transferDomain($params, $transferParams);
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_RenewDomain($params)
{
    try {
        opApiWrapper::renewDomain($params, array('period' => $params["regperiod"]));
        opApiTools::updateDomainData($params);
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_GetContactDetails($params)
{
    function setContactDetails($contact, $type, &$values)
    {
        $contact = $contact['results'][0];
        $values[$type]["First Name"] = $contact['name']['firstName'];
        $values[$type]["Last Name"] = $contact['name']['lastName'];
        $values[$type]["Company Name"] = $contact['companyName'];
        $values[$type]["Email Address"] = $contact['email'];
        $values[$type]["Address"] = trim(
            $contact['address']['street']
            . ' ' . $contact['address']['number']
            . ' ' . $contact['address']['suffix']
        );
        $values[$type]["City"] = $contact['address']['city'];
        $values[$type]["State/Region"] = $contact['address']['state'];
        $values[$type]["Zip Code"] = $contact['address']['zipcode'];
        $values[$type]["Country"] = $contact['address']['country'];
        $values[$type]["Phone Number"] = $contact['phone']["countryCode"] . '.' .
            $contact['phone']["areaCode"] .
            $contact['phone']["subscriberNumber"];
    }

    try {
        $domainInfo = opApiWrapper::infoDomain($params);
        setContactDetails(opApiWrapper::infoCustomer($params, $domainInfo['ownerHandle']), "Registrant", $values);
        setContactDetails(opApiWrapper::infoCustomer($params, $domainInfo['adminHandle']), "Admin", $values);
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_SaveContactDetails($params)
{
    function getContactDetails($handle, $type, $params)
    {
        $modifyParams['handle'] = $handle;
        $modifyParams['name']['firstName'] = $params["contactdetails"][$type]["First Name"];
        $modifyParams['name']['lastName'] = $params["contactdetails"][$type]["Last Name"];
        $modifyParams['name']['initials'] = substr($params["contactdetails"][$type]["First Name"], 0, 1);

        $modifyParams['companyName'] = $params["contactdetails"][$type]["Company Name"];
        $modifyParams['email'] = $params["contactdetails"][$type]["Email Address"];
        $modifyParams['address'] = opApiTools::getAddressInOpenproviderFormat($params["contactdetails"][$type]["Address"]);
        $modifyParams['address']['city'] = $params["contactdetails"][$type]["City"];
        $modifyParams['address']['state'] = $params["contactdetails"][$type]["State/Region"];
        $modifyParams['address']['zipcode'] = $params["contactdetails"][$type]["Zip Code"];
        $modifyParams['address']['country'] = $params["contactdetails"][$type]["Country"];
        $modifyParams['phone'] = opApiTools::getPhoneInOpenproviderFormat($params["contactdetails"][$type]["Phone Number"]);
        return $modifyParams;
    }

    try {
        $domainInfo = opApiWrapper::infoDomain($params);
        opApiWrapper::modifyCustomer($params, getContactDetails($domainInfo['ownerHandle'], "Registrant", $params));
        $registrantEqAdmin = ($params["contactdetails"]["Registrant"]["First Name"] == $params["contactdetails"]["Admin"]["First Name"]) &&
            ($params["contactdetails"]["Registrant"]["Last Name"] == $params["contactdetails"]["Admin"]["Last Name"]) &&
            ($params["contactdetails"]["Registrant"]["Company Name"] == $params["contactdetails"]["Admin"]["Company Name"]);
        if (($params["tld"] == 'it')
            && !$registrantEqAdmin
            && empty($params["contactdetails"]["Registrant"]["Company Name"])
        ) {
            // 'Since registrant is a natural person, registrant and admin contacts must be the same (for .it domains)'
            $params["contactdetails"]["Admin"]["First Name"] = $params["contactdetails"]["Registrant"]["First Name"];
            $params["contactdetails"]["Admin"]["Last Name"] = $params["contactdetails"]["Registrant"]["Last Name"];
            $params["contactdetails"]["Admin"]["Company Name"] = $params["contactdetails"]["Registrant"]["Company Name"];
            $registrantEqAdmin = true;
        }
        opApiWrapper::modifyCustomer($params, getContactDetails($domainInfo['adminHandle'], "Admin", $params), true, $registrantEqAdmin);
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_GetEPPCode($params)
{
    try {
        $domainInfo = opApiWrapper::infoDomain($params);
        $values["eppcode"] = isset($domainInfo['authCode']) ? $domainInfo['authCode'] : '';
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_RegisterNameserver($params)
{
    try {
        $ns = array('name' => $params["nameserver"], 'ip' => $params["ipaddress"]);
        opApiWrapper::processRequest('createNsRequest', $params, $ns);
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_ModifyNameserver($params)
{
    try {
        $ns = array('name' => $params["nameserver"], 'ip' => $params["newipaddress"]);
        opApiWrapper::processRequest('modifyNsRequest', $params, $ns);

        opApiTools::prepareNameservers($params, $params);
        opApiWrapper::modifyDomain($params, array('nameServers' => opApiTools::createNameserversArray($params)));
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

function openprovider_DeleteNameserver($params)
{
    try {
        opApiWrapper::processRequest('deleteNsRequest', $params, array('name' => $params["nameserver"]));
    } catch (opApiException $e) {
        $values["error"] = $e->getFullMessage();
    }
    return $values;
}

?>
