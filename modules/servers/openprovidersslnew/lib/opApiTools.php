<?php

require_once(dirname(__FILE__) . '/API.php');
require_once(dirname(__FILE__) . '/opApiWrapper.php');
require_once(dirname(__FILE__) . '/configuration.php');

class opApiTools
{
    static public function debugLog($data)
    {
        if (is_object($data) || is_array($data)) {
            ob_start();
            var_dump($data);
            $data = ob_get_contents();
            ob_end_clean();
        }
        file_put_contents('/var/www/domain_reg_server/log/drs_debug.log', $data . "\n\n", FILE_APPEND);
    }

    static public function prepareNameservers($params, &$values)
    {
        $domainInfo = opApiWrapper::infoDomain($params);
        $nameServers = $domainInfo['nameServers'];
        for ($i = 1; $i <= 5; $i++) {
            if (isset($nameServers[$i - 1])) {
                $ns = $nameServers[$i - 1];
                $values["ns$i"] = $ns['name'] . (empty($ns['ip']) ? '' : '/' . $ns['ip']);
            } else {
                $values["ns$i"] = '';
            }
        }
    }

    static public function prepareRegisterOrTransferParameters($params)
    {
        $trustee = opConfig::$trusteeAvailableFor;
        $adminHandle = self::getOpenproviderHandle($params, 'admin');
        return array(
            'period' => $params["regperiod"],
            'ownerHandle' => self::getOpenproviderHandle($params),
            'adminHandle' => $adminHandle,
            'techHandle' => $adminHandle,
            'useDomicile' => (is_array($trustee) && in_array($params['tld'], $trustee)) ? 1 : 0,
            'promotion' => '',
            'nameServers' => self::createNameserversArray($params),
            'autorenew' => opConfig::$renewBehaviour,
        );
    }

    static public function getOpenproviderHandle($params, $prefix = '')
    {
        if (($params["tld"] == 'it') && ($prefix == 'admin') && empty($params["companyname"])) {
            // 'Since registrant is a natural person, registrant and admin contacts must be the same (for .it domains)'
            $prefix = '';
        }
        $args = array('lastNamePattern' => $params[$prefix . "lastname"],
            'firstNamePattern' => $params[$prefix . "firstname"],
            'companyNamePattern' => $params[$prefix . "companyname"],
            'emailPattern' => $params[$prefix . "email"]);
        $result = opApiWrapper::processRequest('searchCustomerRequest', $params, $args);
        if ($result['total'] > 0) {
            return $result['results'][0]['handle'];
        } else {
            $opAddress = self::getAddressInOpenproviderFormat($params[$prefix . "address1"] . ' ' .
                $params[$prefix . "address2"]);
            $createHandleArray = array(
                'name' => array(
                    'initials' => substr($params[$prefix . "firstname"], 0, 1),
                    'firstName' => $params[$prefix . "firstname"],
                    'lastName' => $params[$prefix . "lastname"],
                ),
                'gender' => 'M',
                'phone' => self::getPhoneInOpenproviderFormat($params[$prefix . "fullphonenumber"]),
                'address' => array(
                    'street' => $opAddress['street'],
                    'number' => $opAddress['number'],
                    'suffix' => $opAddress['suffix'],
                    'zipcode' => $params[$prefix . "postcode"],
                    'city' => $params[$prefix . "city"],
                    'state' => $params[$prefix . "state"],
                    'country' => $params[$prefix . "country"],
                ),
                'email' => $params[$prefix . "email"],
            );
            if (isset($params[$prefix . "companyname"])) {
                $createHandleArray['companyName'] = $params[$prefix . "companyname"];
            }
            $result = opApiWrapper::processRequest('createCustomerRequest', $params, $createHandleArray);
            return $result['handle'];
        }
    }

    static public function getAddressInOpenproviderFormat($fullAddress)
    {
        $fullAddress = trim($fullAddress);
        $matches = array();
        if (preg_match('/^(\d+),?(.+)$/', $fullAddress, $matches)) {
            $fullAddress = trim($matches[2] . ' ' . $matches[1]);
            // processing for US-styled addresses which start with the number
        }
        $tmp = explode(' ', $fullAddress);

        // Take care of nasty suffixes
        $tmpSuffix = end($tmp);
        $matches = array();
        if (preg_match('/^([\d]+)([^\d].*)$/', $tmpSuffix, $matches)) {
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
        return array('street' => $street,
            'number' => $number,
            'suffix' => trim($suffix));
    }

    static public function getPhoneInOpenproviderFormat($fullPhoneNumber)
    {
        $fullPhoneNumber = trim($fullPhoneNumber);
        $pos = strpos($fullPhoneNumber, '.');
        $countryCode = substr($fullPhoneNumber, 0, $pos);
        $areaCodeLength = 3;
        $areaCode = substr($fullPhoneNumber, $pos + 1, $areaCodeLength);
        $phoneNumber = substr($fullPhoneNumber, $pos + 1 + $areaCodeLength);
        return array('countryCode' => $countryCode,
            'areaCode' => $areaCode,
            'subscriberNumber' => $phoneNumber);
    }

    static public function createNameserversArray($params)
    {
        for ($i = 1; ; $i++) {
            if (empty($params["ns$i"])) {
                break;
            }
            $nsParts = explode('/', $params["ns$i"]);
            $nsName = $nsParts[0];
            $nsIp = empty($nsParts[1]) ? '' : trim($nsParts[1]);
            if (empty($nsIp)) {
                $ns = opApiWrapper::processRequest('searchNsRequest', $params, array('name' => $nsName));
                if ($ns['total'] > 0) {
                    $nsIp = $ns['results'][0]['ip'];
                }
            }
            $nameServers[] = array('name' => $nsName, 'ip' => $nsIp);
        }
        return $nameServers;
    }

    static public function updateDomainData(&$params, $domainInfo = null, $throwException = false)
    {
        $echoEnabled = isset($params['runFromCronScript']) && $params['runFromCronScript'];
        $query = 'SELECT id, domain, expirydate, nextduedate, status, donotrenew FROM tbldomains where registrar = "openprovider"';
        if (isset($params["sld"])) {
            $query .= ' and domain = "' . $params["sld"] . '.' . $params["tld"] . '"';
        }
        $result = mysql_query($query);
        if (!$result) {
            $message = 'Invalid query: ' . mysql_error() . "<br>\n";
            $message .= 'Whole query: ' . $query . "<br>\n";
            $params['failed'] = true;
            return $message;
        }
        $returnMessage = '';
        $i = 0;
        while ($row = mysql_fetch_assoc($result)) {
            $domainName = $row['domain'];
            $pos = stripos($domainName, '.');
            $params["sld"] = substr($domainName, 0, $pos);
            $params["tld"] = substr($domainName, $pos + 1);
            if ($echoEnabled) {
                echo ++$i . '  ' . $domainName . '  ' . $row['status'] . '  ' . $row['expirydate'] . "\n";
            }
            $updateResult = true;
            $message = '';
            try {
                if (is_null($domainInfo)) {
                    $domainInfo = opApiWrapper::infoDomain($params);
                }
                $status = 'Fraud';
                switch ($domainInfo['status']) {
                    case 'ACT':
                        $status = 'Active';
                        break;
                    case 'PEN':
                        $status = 'Pending';
                        break;
                    case 'REQ':
                        $status = 'Pending';
                        break;
                    case 'SCH':
                        $status = 'Pending';
                        break;
                    case 'FAI':
                        $status = 'Cancelled';
                        break;
                    case 'REJ':
                        $status = 'Cancelled';
                        break;
                    case 'RRQ':
                        $status = 'Cancelled';
                        break;
                    case 'AEX':
                        $status = 'Cancelled';
                        break;
                    case 'DEL':
                        $status = 'Expired';
                        break;
                    case 'EXP':
                        $status = 'Expired';
                        break;
                }
                $opExDate = substr($domainInfo['expirationDate'], 0, 10);
                $opRenDate = substr($domainInfo['renewalDate'], 0, 10);

                if (($status != $row['status']) ||
                    ($opExDate != $row['expirydate']) ||
                    ($opRenDate != $row['nextduedate'])
                ) {
                    $sql = '';
                    $infoLine = '';
                    if ($status != $row['status']) {
                        $sql .= "`status` = '$status'";
                        $infoLine .= "status to '$status'";
                    }
                    if ($opExDate != $row['expirydate']) {
                        if (!empty($sql)) {
                            $sql .= ', ';
                            $infoLine .= ', ';
                        }
                        $sql .= "`expirydate` = '$opExDate'";
                        $infoLine .= "expiry date to '$opExDate'";
                    }
                    if ($opRenDate != $row['nextduedate']) {
                        if (!empty($sql)) {
                            $sql .= ', ';
                            $infoLine .= ', ';
                        }
                        $sql .= "`nextduedate` = '$opRenDate'";
                        $infoLine .= "next due date to '$opRenDate'";
                    }
                    $updateResult = mysql_query("UPDATE `tbldomains` SET $sql WHERE `tbldomains`.`id` = " . $row['id']);
                    $message = "Updated $infoLine for domain '$domainName'<br>\n";
                }
                if ($updateResult && ($status != 'Expired') && ($status != 'Cancelled')) { // continue with possible updates
                    $newAutoRenew = self::getChangedAutoRenewValue($row['donotrenew'], $domainInfo['autorenew']);
                    if (!empty($newAutoRenew)) {
                        try {
                            opApiWrapper::modifyDomain($params, array('autorenew' => $newAutoRenew));
                            $message .= "Updated auto renew value to '$newAutoRenew' for domain '$domainName'<br>\n";
                        } catch (opApiException $e) {
                            $message .= "Setting auto renew value to '$newAutoRenew' for domain '$domainName' <b>failed</b><br>\n";
                            $params['failed'] = true;
                            if ($throwException) {
                                throw $e;
                            }
                        }
                    }
                }
            } catch (opApiException $e) {
                if (($e->getInfoCode() == 320) && ($row['status'] != 'Expired')) {
                    // Code 320 means "The domain is not in your Openprovider account"
                    $status = 'Expired';
                    $updateResult = mysql_query("UPDATE `tbldomains` SET `status` = '$status' WHERE `tbldomains`.`id` = " . $row['id']);
                    $message = "Updated status to '$status' for domain '$domainName'<br>\n";
                }
                if ($throwException) {
                    throw $e;
                }
            }
            if (!$updateResult) {
                $message = 'Invalid query: ' . mysql_error() . "<br>\n";
                $message .= 'Whole query: ' . $query . "<br>\n";
                $params['failed'] = true;
                return $returnMessage . $message;
            }
            $returnMessage .= $message;
            $domainInfo = null;
        }
        return $returnMessage;
    }

    static public function getChangedAutoRenewValue($doNotRenewWHMCS, $autoRenewOpenprovider)
    {
        $newAutoRenew = '';
        if (($doNotRenewWHMCS != 'on') && ($autoRenewOpenprovider != opConfig::$renewBehaviour)) {
            $newAutoRenew = opConfig::$renewBehaviour;
        } elseif (($doNotRenewWHMCS == 'on') && ($autoRenewOpenprovider != 'off')) {
            $newAutoRenew = 'off';
        }
        return $newAutoRenew;
    }

}
