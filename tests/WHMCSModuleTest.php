<?php

class WHMCSModuleTest extends PHPUnit_Framework_TestCase
{
    /** @var string $moduleName */
    protected $moduleName = 'openprovidersslnew';

    /**
     * Asserts the required config options function is defined.
     */
    public function testRequiredConfigOptionsFunctionExists()
    {
        $this->assertTrue(function_exists($this->moduleName . '_ConfigOptions'));
    }

    /**
     * Data provider of module function return data types.
     *
     * Used in verifying module functions return data of the correct type.
     *
     * @return array
     */
    public function providerFunctionReturnTypes()
    {
        return array(
            'Config Options' => array('ConfigOptions', 'array'),
            'Meta Data' => array('MetaData', 'array'),
            'Create' => array('CreateAccount', 'string'),
            'Suspend' => array('SuspendAccount', 'string'),
            'Unsuspend' => array('UnsuspendAccount', 'string'),
            'Terminate' => array('TerminateAccount', 'string'),
            'Change Password' => array('ChangePassword', 'string'),
            'Change Package' => array('ChangePackage', 'string'),
            'Test Connection' => array('TestConnection', 'array'),
            'Admin Area Custom Button Array' => array('AdminCustomButtonArray', 'array'),
            'Client Area Custom Button Array' => array('ClientAreaCustomButtonArray', 'array'),
            'Admin Services Tab Fields' => array('AdminServicesTabFields', 'array'),
            'Admin Services Tab Fields Save' => array('AdminServicesTabFieldsSave', 'null'),
            'Service Single Sign-On' => array('ServiceSingleSignOn', 'array'),
            'Admin Single Sign-On' => array('AdminSingleSignOn', 'array'),
            'Client Area Output' => array('ClientArea', 'array'),
        );
    }

    /**
     * Test module functions return appropriate data types.
     *
     * @param string $function
     * @param string $returnType
     *
     * @dataProvider providerFunctionReturnTypes
     */
    public function testFunctionsReturnAppropriateDataType($function, $returnType)
    {
        if (function_exists($this->moduleName . '_' . $function)) {
            $result = call_user_func($this->moduleName . '_' . $function, array());
            if ($returnType == 'array') {
                $this->assertTrue(is_array($result));
            } elseif ($returnType == 'null') {
                $this->assertTrue(is_null($result));
            } else {
                $this->assertTrue(is_string($result));
            }
        }
    }

    public function testAPI()
    {
        // The fully qualified URL to your WHMCS installation root directory
        $whmcsUrl = "http://whmcs.fgershunov.openprovider.nl/";

        // Admin username and password
        $username = "root";
        $password = "masterkey";

        // Set post values
        $postfields = array(
            'username' => $username,
            'password' => md5($password),
            'action' => 'GetOrders',
            'responsetype' => 'json',
            'id' => 403,
        );

        // Call the API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $whmcsUrl . 'includes/api.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
        $response = curl_exec($ch);
        if (curl_error($ch)) {
            die('Unable to connect: ' . curl_errno($ch) . ' - ' . curl_error($ch));
        }
        curl_close($ch);

        // Attempt to decode response as json
        $jsonData = json_decode($response, true);

        // Dump array structure for inspection
        var_dump($jsonData);

        $this->assertNotNull($jsonData);
    }

    public function testOpApi()
    {
        $params = [
            'OpenproviderAPI' => 'https://api.cte.openprovider.eu/',
            'Username' => 'opdrs4',
            'Password' => 'opdrs4',
        ];

        $products = opApiWrapper::processRequest('searchProductSslCertRequest', $params, []);

        error_log(var_export($products, true));

        $this->assertNotNull($products);
    }
}
