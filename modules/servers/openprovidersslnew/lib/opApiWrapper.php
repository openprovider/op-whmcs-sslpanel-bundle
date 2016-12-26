<?php

class opApiWrapper
{
    static public function infoCustomer($params, $handle)
    {
        return self::processRequest('searchCustomerRequest', self::buildParams($params), ['handlePattern' => $handle]);
    }

    static public function processRequest($requestName, $params, $args)
    {
        if (!isset($params['username']) || !isset($params['password']) || !isset($params['apiUrl'])) {
            throw new opApiException(opApiException::ERR_OP_API_EXCEPTION, 'Username, password or apiUrl are invalid',
                399);
        }

        $api = new OP_API($params["apiUrl"]);
        $request = new OP_Request;
        $request->setCommand($requestName)
            ->setAuth([
                'username' => $params['username'],
                'password' => $params['password'],
                'client' => $params['client'],
                'clientVersion' => $params['clientVersion'],
                'clientAdditionalData' => $params['clientAdditionalData'],
            ])
            ->setArgs($args);

        logModuleCall(
            'openprovidersslnew',
            'OP_Request',
            $params,
            $request->getRaw(),
            $request->getRaw()
        );

        $reply = $api->setDebug(1)->process($request);

        logModuleCall(
            'openprovidersslnew',
            'OP_Reply',
            $params,
            $reply->getRaw(),
            $reply->getRaw()
        );

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

    /**
     * @param array $params
     *
     * @return array
     */
    public static function buildParams(array $params)
    {
        return [
            'username' => $params['username'],
            'password' => $params['password'],
            'apiUrl' => $params['apiUrl'],
            'client' => ArrayHelper::getValue($params, 'clientInformation.name'),
            'clientVersion' => ArrayHelper::getValue($params, 'clientInformation.version'),
            'clientAdditionalData' => ArrayHelper::getValue($params, 'clientInformation.additionalData'),
        ];
    }

    static public function modifyCustomer($params, $modifyParams, $admin = false, $registrantEqAdmin = false)
    {
        if ($admin) {
            $args = [
                'lastNamePattern' => $modifyParams['name']['lastName'],
                'firstNamePattern' => $modifyParams['name']['firstName'],
                'companyNamePattern' => $modifyParams['companyName'],
                'emailPattern' => $modifyParams['email'],
            ];
            $result = self::processRequest('searchCustomerRequest', self::buildParams($params), $args);
            if ($result['total'] > 0) {
                $handle = $result['results'][0]['handle'];
                if ($handle !== $modifyParams['handle']) {
                    self::modifyDomain($params, ['adminHandle' => $handle, 'techHandle' => $handle]);
                    $modifyParams['handle'] = $handle;
                }
            } else {
                $createHandleArray = [
                    'name' => [
                        'initials' => substr($modifyParams['name']['firstName'], 0, 1),
                        'firstName' => $modifyParams['name']['firstName'],
                        'lastName' => $modifyParams['name']['lastName'],
                    ],
                    'companyName' => $modifyParams['companyName'],
                    'gender' => 'M',
                    'phone' => $modifyParams['phone'],
                    'address' => [
                        'street' => $modifyParams['address']['street'],
                        'number' => $modifyParams['address']['number'],
                        'suffix' => $modifyParams['address']['suffix'],
                        'zipcode' => $modifyParams['address']['zipcode'],
                        'city' => $modifyParams['address']['city'],
                        'state' => $modifyParams['address']['state'],
                        'country' => $modifyParams['address']['country'],
                    ],
                    'email' => $modifyParams['email'],
                ];
                $result = self::processRequest('createCustomerRequest', self::buildParams($params), $createHandleArray);
                $handle = $result['handle'];
                self::modifyDomain($params, ['adminHandle' => $handle, 'techHandle' => $handle]);

                return;
            }
        }
        if (!$admin || !$registrantEqAdmin) {
            self::processRequest('modifyCustomerRequest', self::buildParams($params), $modifyParams);
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
            'extension' => $params["tld"],
        ];

        return self::processRequest($operation, self::buildParams($params), $domainParams);
    }

    static public function infoDomain($params)
    {
        return self::domainOperation('retrieveDomainRequest', $params, []);
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
        self::domainOperation('deleteDomainRequest', $params, []);
    }

    static public function infoDNSZone($params)
    {
        $zonePattern = [
            'namePattern' => $params["sld"] . '.' . $params["tld"],
            'withHistory' => 0,
        ];
        $result = self::processRequest('searchZoneDnsRequest', self::buildParams($params), $zonePattern);
        if ($result['total'] > 0) {
            $zoneName = [
                'name' => $params["sld"] . '.' . $params["tld"],
                'withHistory' => 0,
            ];

            return self::processRequest('retrieveZoneDnsRequest', self::buildParams($params), $zoneName);
        } else {
            return null;
        }
    }

    static public function modifyDNSZone($params, $zoneParams)
    {
        $zonePattern = [
            'namePattern' => $params["sld"] . '.' . $params["tld"],
            'withHistory' => 0,
        ];
        $result = self::processRequest('searchZoneDnsRequest', self::buildParams($params), $zonePattern);

        $operation = $result['total'] > 0 ? 'modify' : 'create';
        $zoneParams = ['type' => 'master', 'records' => $zoneParams];
        self::domainOperation($operation . 'ZoneDnsRequest', $params, $zoneParams);
    }

    static public function searchProductSslCert($params)
    {
        return self::processRequest('searchProductSslCertRequest', self::buildParams($params), ['withPrice' => '1']);
    }

    static public function createSslCert($params)
    {
        return self::processRequest(
            'createSslCertRequest',
            self::buildParams($params),
            [
                'productId' => $params['productId'],
                'period' => $params['period'],
                'startProvision' => 0,
                'domainAmount' => $params['domainAmount'],
                'organizationHandle' => isset($params['organizationHandle']) ? $params['organizationHandle'] : null,
                'technicalHandle' => isset($params['technicalHandle']) ? $params['technicalHandle'] : null,
            ]
        );
    }

    static public function cancelSslCert($params)
    {
        return self::processRequest(
            'cancelSslCertRequest',
            self::buildParams($params),
            [
                'id' => $params['id'],
            ]
        );
    }

    static public function renewSslCert($params)
    {
        return self::processRequest(
            'renewSslCertRequest',
            self::buildParams($params),
            [
                'id' => $params['id'],
            ]
        );
    }

    static public function generateOtpToken($params)
    {
        return self::processRequest(
            'generateOtpTokenSslCertRequest',
            self::buildParams($params),
            [
                'id' => $params['id'],
            ]
        );
    }

    static public function retrieveOrder($params)
    {
        return self::processRequest(
            'retrieveOrderSslCertRequest',
            self::buildParams($params),
            [
                'id' => $params['id'],
            ]
        );
    }
}
