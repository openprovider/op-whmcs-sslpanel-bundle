<?php

require_once(dirname(__FILE__) . '/API.php');
require_once(dirname(__FILE__) . '/opApiException.php');

class opApiWrapper
{
    static public function infoCustomer($params, $handle)
    {
        return self::processRequest('searchCustomerRequest', $params, array('handlePattern' => $handle));
    }

    static public function processRequest($requestName, $params, $args)
    {
        if (!isset($params['username']) || !isset($params['password']) || !isset($params['apiUrl'])) {
            throw new opApiException(opApiException::ERR_OP_API_EXCEPTION, 'Username, password or apiUrl are invalid', 399);
        }

        $api = new OP_API($params["apiUrl"]);
        $request = new OP_Request;
        $request->setCommand($requestName)
            ->setAuth(array('username' => $params["username"], 'password' => $params["password"]))
            ->setArgs($args);
        $reply = $api->setDebug(1)->process($request);
        $returnValue = $reply->getValue();
        $faultCode = $reply->getFaultCode();
        if ($faultCode != 0) {
            $exceptionCode = opApiException::ERR_OP_API_EXCEPTION;
            if (empty($returnValue)) {
                $exceptionMessage = $reply->getFaultString();
            } elseif ($faultCode == 399) {
                $exceptionCode = opApiException::ERR_REGISTRY_EXCEPTION;
                $exceptionMessage = "'$returnValue'";
            } else {
                $exceptionMessage = $reply->getFaultString();
                if (is_array($returnValue)) {
                    if (!empty($returnValue['error'])) {
                        $exceptionMessage .= ' (' . $returnValue['error'] . ')';
                    }
                } elseif ($exceptionMessage != $returnValue) {
                    $exceptionMessage .= ": '$returnValue'";
                }
            }
            throw new opApiException($exceptionCode, $exceptionMessage, $faultCode);
        }
        return $returnValue;
    }

    static public function modifyCustomer($params, $modifyParams, $admin = false, $registrantEqAdmin = false)
    {
        if ($admin) {
            $args = array('lastNamePattern' => $modifyParams['name']['lastName'],
                'firstNamePattern' => $modifyParams['name']['firstName'],
                'companyNamePattern' => $modifyParams['companyName'],
                'emailPattern' => $modifyParams['email']);
            $result = self::processRequest('searchCustomerRequest', $params, $args);
            if ($result['total'] > 0) {
                $handle = $result['results'][0]['handle'];
                if ($handle !== $modifyParams['handle']) {
                    self::modifyDomain($params, array('adminHandle' => $handle, 'techHandle' => $handle));
                    $modifyParams['handle'] = $handle;
                }
            } else {
                $createHandleArray = array(
                    'name' => array(
                        'initials' => substr($modifyParams['name']['firstName'], 0, 1),
                        'firstName' => $modifyParams['name']['firstName'],
                        'lastName' => $modifyParams['name']['lastName'],
                    ),
                    'companyName' => $modifyParams['companyName'],
                    'gender' => 'M',
                    'phone' => $modifyParams['phone'],
                    'address' => array(
                        'street' => $modifyParams['address']['street'],
                        'number' => $modifyParams['address']['number'],
                        'suffix' => $modifyParams['address']['suffix'],
                        'zipcode' => $modifyParams['address']['zipcode'],
                        'city' => $modifyParams['address']['city'],
                        'state' => $modifyParams['address']['state'],
                        'country' => $modifyParams['address']['country'],
                    ),
                    'email' => $modifyParams['email'],
                );
                $result = self::processRequest('createCustomerRequest', $params, $createHandleArray);
                $handle = $result['handle'];
                self::modifyDomain($params, array('adminHandle' => $handle, 'techHandle' => $handle));
                return;
            }
        }
        if (!$admin || !$registrantEqAdmin) {
            self::processRequest('modifyCustomerRequest', $params, $modifyParams);
        }
    }

    static public function modifyDomain($params, $modifyParams)
    {
        self::domainOperation('modifyDomainRequest', $params, $modifyParams);
    }

    static private function domainOperation($operation, $params, $domainParams)
    {
        $domainParams['domain'] = [
            'name' => $params["sld"],
            'extension' => $params["tld"]
        ];

        return self::processRequest($operation, $params, $domainParams);
    }

    static public function infoDomain($params)
    {
        return self::domainOperation('retrieveDomainRequest', $params, array());
    }

    static public function registerDomain($params, $registerParams)
    {
        self::domainOperation('createDomainRequest', $params, $registerParams);
    }

    static public function transferDomain($params, $transferParams)
    {
        self::domainOperation('transferDomainRequest', $params, $transferParams);
    }

    static public function renewDomain($params, $renewParams)
    {
        self::domainOperation('renewDomainRequest', $params, $renewParams);
    }

    static public function deleteDomain($params)
    {
        self::domainOperation('deleteDomainRequest', $params, array());
    }

    static public function infoDNSZone($params)
    {
        $zonePattern = array('namePattern' => $params["sld"] . '.' . $params["tld"],
            'withHistory' => 0);
        $result = self::processRequest('searchZoneDnsRequest', $params, $zonePattern);
        if ($result['total'] > 0) {
            $zoneName = array('name' => $params["sld"] . '.' . $params["tld"],
                'withHistory' => 0);
            return self::processRequest('retrieveZoneDnsRequest', $params, $zoneName);
        } else {
            return null;
        }
    }

    static public function modifyDNSZone($params, $zoneParams)
    {
        $zonePattern = array('namePattern' => $params["sld"] . '.' . $params["tld"],
            'withHistory' => 0);
        $result = self::processRequest('searchZoneDnsRequest', $params, $zonePattern);

        $operation = $result['total'] > 0 ? 'modify' : 'create';
        $zoneParams = array('type' => 'master', 'records' => $zoneParams);
        self::domainOperation($operation . 'ZoneDnsRequest', $params, $zoneParams);
    }

    static public function searchProductSslCert($params)
    {
        return self::processRequest('searchProductSslCertRequest', $params, []);
    }

    static public function createSslCert($params, $productId)
    {
        return self::processRequest(
	    'createSslCertRequest', 
	    [
	        'username' => $params['configoption1'],
	        'password' => $params['configoption2'],
	        'apiUrl' => $params['configoption3'],
            ], 
	    [
                'productId' => $productId,
                'period' => 1, //todo: get rid
                'startProvision' => 0,
            ]
        );
    }
}
