<?php

function openproviderssl_ConfigOptions() {
	
    if (!get_query_val("tblemailtemplates","COUNT(*)",array("name"=>"SSL Certificate Configuration Required"))) {
        full_query("INSERT INTO `tblemailtemplates` (`type` ,`name` ,`subject` ,`message` ,`fromname` ,`fromemail` ,`disabled` ,`custom` ,`language` ,`copyto` ,`plaintext` )VALUES ('product', 'SSL Certificate Configuration Required', 'SSL Certificate Configuration Required', '<p>Dear {\$client_name},</p><p>Thank you for your order for an SSL Certificate. Before you can use your certificate, it requires configuration which can be done at the URL below.</p><p>{\$ssl_configuration_link}</p><p>Instructions are provided throughout the process but if you experience any problems or have any questions, please open a ticket for assistance.</p><p>{\$signature}</p>', '', '', '', '', '', '', '0')");
    }
	if (!get_query_val("tblcustomfields","id",array("type"=>"product","fieldname"=>"Domain Name","relid"=>$_GET['id'],"fieldtype"=>"text"))){
		insert_query("tblcustomfields",array("type"=>"product","fieldname"=>"Domain Name","relid"=>$_GET['id'],"fieldtype"=>"text","required"=>"on","showorder"=>"on"));
	}
    $configarray = array(
     "Username" => array( "Type" => "text", "Size" => "25", ),
     "Password" => array( "Type" => "password", "Size" => "25", ),
     "SSL Certificate Type" => array( "Type" => "dropdown", "Options" => "Symantec Secure Site,Symantec Secure Site Pro,Symantec Secure Site with EV,Symantec Secure Site Pro with EV,GeoTrust QuickSSL Premium,GeoTrust True Business ID, GeoTrust True Business ID with EV,GeoTrust True Business ID Multi-Domain,GeoTrust True Business ID with EV Multi-Domain,GeoTrust True Business ID Wildcard,thawte SSL123,thawte Web Server,thawte Web Server with EV,thawte Web Server Wildcard,thawte SGC SuperCert,Comodo EssentialSSL,Comodo InstantSSL,Comodo InstantSSL Pro,Comodo PremiumSSL,Comodo EV SSL,Comodo EV SGC SSL,Comodo Instant SGC SSL,Comodo Unified Communications Certificate,Comodo EVSSL Multi-Domain,Comodo EssentialSSL Wildcard,Comodo PremiumSSL Wildcard,Comodo Instant SGC Wildcard SSL,RapidSSL,RapidSSL Wildcard", ),
     "Validity Period" => array( "Type" => "dropdown", "Options" => "1,2,3,4,5", "Description" => "Years", ),
     "" => array( "Type" => "na", "Description" => "Don't have a OpenProvider SSL account? Visit <a href=\"https://www.openprovider.co.uk/register/\" target=\"_blank\">www.openprovider.co.uk/</a> to signup free.", ),
    );
    return $configarray;
}

function openproviderssl_CreateAccount($params) {

    $result = select_query("tblsslorders","COUNT(*)",array("serviceid"=>$params["serviceid"]));
    $data = mysql_fetch_array($result);
    if ($data[0]) {
        return "An SSL Order already exists for this order";
    }

    $sslorderid = insert_query("tblsslorders",array("userid" => $params["clientsdetails"]["userid"],"serviceid" => $params["serviceid"],"remoteid" => "","module"=>"openproviderssl","certtype" => $params["configoption3"],"status" => "Awaiting Configuration"));

    global $CONFIG;
    $sslconfigurationlink = $CONFIG["SystemURL"]."/configuressl.php?cert=".md5($sslorderid);
    $sslconfigurationlink = "<a href=\"$sslconfigurationlink\">$sslconfigurationlink</a>";
    sendMessage("SSL Certificate Configuration Required",$params["serviceid"],array("ssl_configuration_link"=>$sslconfigurationlink));

    return "success";

}

function openproviderssl_AdminCustomButtonArray() {
	$buttonarray = array(
	 "Cancel" => "cancel",
     "Resend Configuration Email" => "resend",
     "Resend Approver Email" => "resendapprover",
	);
	return $buttonarray;
}

function openproviderssl_cancel($params) {
    $result = select_query("tblsslorders","COUNT(*)",array("serviceid"=>$params['serviceid'],"status"=>"Awaiting Configuration"));
    $data = mysql_fetch_array($result);
    if (!$data[0]) {
        return "No Incomplete SSL Order exists for this order";
    }
    update_query("tblsslorders",array("status"=>"Cancelled"),array("serviceid"=>$params["serviceid"]));
}

function openproviderssl_resend($params) {
    $result = select_query("tblsslorders","id",array("serviceid"=>$params['serviceid']));
    $data = mysql_fetch_array($result);
    $id = $data["id"];
    if (!$id) {
        return "No SSL Order exists for this product";
    }
    global $CONFIG;
    $sslconfigurationlink = $CONFIG["SystemURL"]."/configuressl.php?cert=".md5($id);
    $sslconfigurationlink = "<a href=\"$sslconfigurationlink\">$sslconfigurationlink</a>";
    sendMessage("SSL Certificate Configuration Required",$params["serviceid"],array("ssl_configuration_link"=>$sslconfigurationlink));
}

function openproviderssl_resendapprover($params) {
    $result = select_query("tblsslorders","remoteid",array("serviceid"=>$params['serviceid']));
    $data = mysql_fetch_array($result);
    $remoteid = $data["remoteid"];
    if (!$remoteid) return "No SSL Order exists for this product";

    $user = $params["configoption1"];
    $pass = $params["configoption2"];
    $prodcode = $params["configoption3"];
    $baseoption = $params["configoption4"];
    $validityperiod = $params["configoption5"];

#    if ($errorcode>=0) {
        return "success";
#    } else {
 #       return ":";
#    }

}

function openproviderssl_ClientArea($params) {
    global $_LANG;
    $result = select_query("tblsslorders","",array("serviceid"=>$params['serviceid']));
    $data = mysql_fetch_array($result);
    $id = $data["id"];
    $orderid = $data["orderid"];
    $serviceid = $data["serviceid"];
    $remoteid = $data["remoteid"];
    $module = $data["module"];
    $certtype = $data["certtype"];
    $domain = $data["domain"];
    $provisiondate = $data["provisiondate"];
    $completiondate = $data["completiondate"];
    $status = $data["status"];
    if ($id) {
        $provisiondate = ($provisiondate=="0000-00-00") ? 'Not Yet Configured' : fromMySQLDate($provisiondate);
        $status .= ' - <a href="configuressl.php?cert='.md5($id).'">Configure Now</a>';
        $output = '<div align="left">
<table width="100%" cellspacing="1" cellpadding="0" class="frame"><tr><td>
<table width="100%" border="0" cellpadding="10" cellspacing="2">
<tr><td width="150" class="fieldarea">SSL Provisioning Date:</td><td>'.$provisiondate.'</td></tr>
<tr><td class="fieldarea">'.$_LANG['sslstatus'].':</td><td>'.$status.'</td></tr>
</table>
</td></tr></table>
</div>';
        return $output;
    }
}

function openproviderssl_SSLStepOne($params) {

	$dvproductsarr = array("GeoTrust QuickSSL Premium","thawte SSL123","Comodo EssentialSSL","Comodo EssentialSSL Wildcard","RapidSSL","RapidSSL Wildcard");
	
    $user = $params["configoption1"];
    $pass = $params["configoption2"];
    $prodcode = $params["configoption3"];
    $validityperiod = $params["configoption4"];

    $values = array();
	$values['additionalfields']['Organization Information'] = array(
            "orgname" => array( "FriendlyName" => "Organization Name", "Type" => "text", "Size" => "30", "Description" => "", "Required" => true, ),
            "orgaddress" => array( "FriendlyName" => "Address 1", "Type" => "text", "Size" => "30", "Description" => "", "Required" => true, ),
            "orgcity" => array( "FriendlyName" => "City", "Type" => "text", "Size" => "30", "Description" => "", "Required" => true, ),
            "orgstate" => array( "FriendlyName" => "State", "Type" => "text", "Size" => "30", "Description" => "", "Required" => true, ),
            "orgpostcode" => array( "FriendlyName" => "Postcode", "Type" => "text", "Size" => "30", "Description" => "", "Required" => true, ),
            "orgcountry" => array( "FriendlyName" => "Country", "Type" => "country", "Required" => true, ),
            "orgphone" => array( "FriendlyName" => "Phone Number", "Type" => "text", "Size" => "30", "Description" => "", "Required" => true, ),
        );
		
    if (!in_array($prodcode, $dvproductsarr)) {
		
        $values['additionalfields']['Organization Information']["bizcatcode"] = array("FriendlyName" => "Business Category Code", "Type" => "dropdown", "Options" => "Private Organization,Government Entity,Business Entity", );
        $values['additionalfields']['Organization Information']["bizname"] = array("FriendlyName" => "Business Name", "Type" => "text", "Size" => "30", "Description" => "", "Required" => true, );
        $values['additionalfields']['Organization Information']["orgregnum"] = array("FriendlyName" => "Incorporating Agency Reg Number", "Type" => "text", "Size" => "20", "Description" => "As supplied to you by Companies House, Secretary of State, etc...", "Required" => true, );

    }

    return $values;

}

function openproviderssl_SSLStepTwo($params) {
	
	$productsarr = array("Symantec Secure Site" => '1',"Symantec Secure Site Pro" => '2',"Symantec Secure Site with EV" => '3',"Symantec Secure Site Pro with EV" => '4',"GeoTrust QuickSSL Premium" => '8',"GeoTrust True Business ID" => '9',"GeoTrust True Business ID with EV" => '10',"GeoTrust True Business ID Multi-Domain" => '34',"GeoTrust True Business ID with EV Multi-Domain" => '31',"GeoTrust True Business ID Wildcard" => '11',"thawte SSL123" => '14',"thawte Web Server" => '15',"thawte Web Server with EV" => '16',"thawte Web Server Wildcard" => '17',"thawte SGC SuperCert" => '18',"Comodo EssentialSSL" => '31',"Comodo InstantSSL" => '20',"Comodo InstantSSL Pro" => '21',"Comodo PremiumSSL" => '22',"Comodo EV SSL" => '24',"Comodo EV SGC SSL" => '27',"Comodo Instant SGC SSL" => '25',"Comodo Unified Communications Certificate" => '28',"Comodo EVSSL Multi-Domain" => '33',"Comodo EssentialSSL Wildcard" => '32',"Comodo PremiumSSL Wildcard" => '23',"Comodo Instant SGC Wildcard SSL" => '26',"RapidSSL" => '5',"RapidSSL Wildcard" => '6');
	$prodcode = $productsarr[$params["configoption3"]];
	$validityperiod = $params["configoption4"];
	if ($params["configoptions"]["ValidityPeriod"]) $validityperiod = $params["configoptions"]["ValidityPeriod"];
	if ($params["configoptions"]["Years"]) $validityperiod = $params["configoptions"]["Years"];

	$webservertype = ($params["servertype"] == "1013" || $params["servertype"] == "1014") ? "windows" : "linux";
	$csr = $params["csr"];
	
	#### Create Handle ####
	
	$firstname = $params["firstname"];
	$lastname = $params["lastname"];
	$orgname = $params["orgname"];
	$jobtitle = $params["jobtitle"];
	$emailaddress = $params["email"];
	$address1 = $params["address1"];
	$address2 = $params["address2"];
	$city = $params["city"];
	$state = $params["state"];
	$postcode = $params["postcode"];
	$country = $params["country"];
	$phonenumber = $params["phonenumber"];
	
	#### Create Handle ####

	$domaincfid = get_query_val("tblcustomfields","id",array("type"=>"product","fieldname"=>"Domain Name","relid"=>$params['packageid'],"fieldtype"=>"text"));
	$domainname = get_query_val("tblcustomfieldsvalues","value",array("fieldid"=>$domaincfid,"relid"=>$params['serviceid']));
	update_query("tblhosting",array("domain"=>$domainname),array("id"=>$params['serviceid']));
	
	$api = new OP_API ('https://api.openprovider.eu');
	$request = new OP_Request;
	$request->setCommand('retrieveApproverEmailListSslCertRequest')
	  ->setAuth(array('username' => $params["configoption1"], 'password' => $params["configoption2"]))
	  ->setArgs(array(
		'domain' => $domainname,
		'productId' => $prodcode,
	  ));
	  
	$reply = $api->setDebug(0)->process($request);
  
	if ($reply->getFaultCode() == 0) {
		$values["approveremails"] = $reply->getValue();
		$values["displaydata"]["Domain"] = $domainname;
		$values["displaydata"]["Validity Period"] = $validityperiod." Year(s)";
	}

	return $values;

}

function openproviderssl_SSLStepThree($params) {
		
	require(ROOTDIR."/includes/countriescallingcodes.php");
	$productsarr = array("Symantec Secure Site" => '1',"Symantec Secure Site Pro" => '2',"Symantec Secure Site with EV" => '3',"Symantec Secure Site Pro with EV" => '4',"GeoTrust QuickSSL Premium" => '8',"GeoTrust True Business ID" => '9',"GeoTrust True Business ID with EV" => '10',"GeoTrust True Business ID Multi-Domain" => '34',"GeoTrust True Business ID with EV Multi-Domain" => '31',"GeoTrust True Business ID Wildcard" => '11',"thawte SSL123" => '14',"thawte Web Server" => '15',"thawte Web Server with EV" => '16',"thawte Web Server Wildcard" => '17',"thawte SGC SuperCert" => '18',"Comodo EssentialSSL" => '31',"Comodo InstantSSL" => '20',"Comodo InstantSSL Pro" => '21',"Comodo PremiumSSL" => '22',"Comodo EV SSL" => '24',"Comodo EV SGC SSL" => '27',"Comodo Instant SGC SSL" => '25',"Comodo Unified Communications Certificate" => '28',"Comodo EVSSL Multi-Domain" => '33',"Comodo EssentialSSL Wildcard" => '32',"Comodo PremiumSSL Wildcard" => '23',"Comodo Instant SGC Wildcard SSL" => '26',"RapidSSL" => '5',"RapidSSL Wildcard" => '6');
	
	if(isset($params['fields']['orgname'])){
		$addlcustcreatearr = array(
			'companyRegistrationNumber' => $params['configdata']['fields']['orgregnum'],
			'companyRegistrationCity' => $params['configdata']['fields']['orgcity'],
			'headquartersAddress'=>$params['configdata']['fields']['orgaddress'],
			'headquartersCity' => $params['configdata']['fields']['orgcity'],
			'headquartersState' => $params['configdata']['fields']['orgstate'],
			'headquartersZipcode'=>$params['configdata']['fields']['orgpostcode'],
			'headquartersCountry' => $params['configdata']['fields']['orgcountry'],
		);
	}
	
	if(!$params['clientsdetails']['phonecc']){
		logModuleCall('openproviderssl','orderssl',$params,"WHMCS could not fetch Country Code for Country : " . $params["country"].' Attempting again directly from countriescallingcodes.php','',array($params["configoption1"],$params["configoption2"]));
		$params['clientsdetails']['phonecc'] = $countrycallingcodes[$params["country"]];
	}
		
	$openproviderphone = openproviderssl_getPhoneInOpenproviderFormat("+".$params['clientsdetails']['phonecc'].".".$params['phonenumber']);
	$openprovideraddress = openproviderssl_getAddressInOpenproviderFormat($params['address1'].' '.$params['address2']);
	
	$api = new OP_API ('https://api.openprovider.eu');
	$request = new OP_Request;
	$logrequest = array();
	$logrequest['command'] = 'createCustomerRequest';
	$logrequest['auth'] = array('username' => $params["configoption1"], 'password' => $params["configoption2"]);
	$logrequest['args'] = array(
	   'name' => array(
		 'initials' => strtoupper($params["firstname"][0]).'.'.strtoupper($params["lastname"][0]),
		 'firstName' => $params["firstname"],
		 'lastName' => $params["lastname"],
	   ),
	
	   'gender' => 'M', 
	
	   'phone' => array(
		 'countryCode' => $openproviderphone['countryCode'],
		 'areaCode' => $openproviderphone['areaCode'],
		 'subscriberNumber' => $openproviderphone['subscriberNumber']
	   ),
	
	   'fax' => array(
		 'countryCode' => $openproviderphone['countryCode'],
		 'areaCode' => $openproviderphone['areaCode'],
		 'subscriberNumber' => $openproviderphone['subscriberNumber']
	   ),
	
	   'address' => array(
		  'street' => $openprovideraddress['street'].' '.$openprovideraddress['suffix'],
		  'number' => $openprovideraddress['number'],
		  'zipcode' => $params['postcode'],
		  'city' => $params['city'],
		  'state' => $params['state'],
		  'country' => $params['country'],
	   ),
	
	   'email' => $params['email'],
	
	   'additionalData' => $addlcustcreatearr,
	   
	 );
	 
	$request->setCommand('createCustomerRequest')
	 ->setAuth($logrequest['auth'])
	 ->setArgs($logrequest['args']);

	$reply = $api->setDebug(0)->process($request);
	$response = $reply->getValue();
	
	if ($reply->getFaultCode() == 0) {
	  $handle = $response['handle'];
	}
	
	if(!$handle){
		logModuleCall('openproviderssl','createhandle',$logrequest,"Error Code: " . $reply->getFaultCode() . "Error: " . $reply->getFaultString(),'',array($params["configoption1"],$params["configoption2"]));
		$values['error'] = "Error Code: " . $reply->getFaultCode() . "Error: " . $reply->getFaultString();
		return $values;
	} else {
		logModuleCall('openproviderssl','createhandle',$logrequest,$handle,'',array($params["configoption1"],$params["configoption2"]));
	}
	
	$prodcode = $productsarr[$params["configoption3"]];
	$validityperiod = $params["configoption4"];
	if ($params["configoptions"]["ValidityPeriod"]) $validityperiod = $params["configoptions"]["ValidityPeriod"];
	if ($params["configoptions"]["Years"]) $validityperiod = $params["configoptions"]["Years"];

	$webservertype = ($params["servertype"] == "1013" || $params["servertype"] == "1014") ? "windows" : "linux";
	$csr = $params["csr"];
	$approveremail = $params["approveremail"];

	$api = $request = $reply = $response = null;
	$api = new OP_API ('https://api.openprovider.eu');
	$request = new OP_Request;
	$logrequest = array();
	$logrequest['command'] = 'createSslCertRequest';
	$logrequest['auth'] = array('username' => $params["configoption1"], 'password' => $params["configoption2"]);
	$logrequest['args'] = array(
		'productId' => $prodcode,
		'period' => $validityperiod,
		'csr' => $csr,
		'softwareId' => $webservertype,
		'organizationHandle' => $handle,
		'technicalHandle' => $handle,
		'approverEmail' => $approveremail,
	   );
	$request->setCommand('createSslCertRequest')
	  ->setAuth($logrequest['auth'])
	  ->setArgs($logrequest['args']);
	   
	$reply = $api->setDebug(0)->process($request);
	$response = $reply->getValue();
		
	if ($reply->getFaultCode() == 0) {
  		$orderid = $response['id'];
	}

	if(!$orderid){
		logModuleCall('openproviderssl','orderssl',$logrequest,"Error Code: " . $reply->getFaultCode() . "Error: " . $reply->getFaultString(),'',array($params["configoption1"],$params["configoption2"]));
		$values['error'] = "Error Code: " . $reply->getFaultCode() . "Error: " . $reply->getFaultString();
	} else {
		logModuleCall('openproviderssl','orderssl',$logrequest,$response,'',array($params["configoption1"],$params["configoption2"]));
		update_query("tblsslorders",array("remoteid"=>$orderid),array("serviceid"=>$params["serviceid"]));	
	}

	return $values;

}

### OpenProvider Class ###

class OP_API_Exception extends Exception
{
}

class OP_API
{
protected $url = null;
protected $error = null;
protected $timeout = null;
protected $debug = null;
static public $encoding = 'UTF-8';

public function __construct ($url, $timeout = 1000)
{
  $this->url = $url;
  $this->timeout = $timeout;
}

public function setDebug ($v)
{
  $this->debug = $v;
  return $this;
}

public function process (OP_Request $r)
{
  if ($this->debug) {
	echo $r->getRaw() . "\n";
  }

  $msg = $r->getRaw();
  $str = $this->_send($msg);
  if (!$str) {
	throw new OP_API_Exception ('Bad reply');
  }
  if ($this->debug) {
	echo $str . "\n";
  }
  return new OP_Reply($str);
}

static function encode ($str)
{
  $ret = @htmlentities($str, null, OP_API::$encoding);
  if (strlen($str) && !strlen($ret)) {
	$str = iconv('ISO-8859-1', 'UTF-8', $str);
	$ret = htmlentities($str, null, OP_API::$encoding);
  }
  return $ret;
}

static function decode ($str)
{
  return html_entity_decode($str, null, OP_API::$encoding);
}

static function createRequest ($xmlStr = null)
{
  return new OP_Request ($xmlStr);
}

static function createReply ($xmlStr = null)
{
  return new OP_Reply ($xmlStr);
}

protected function _send ($str)
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $this->url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
  curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
  $ret = curl_exec ($ch);
  $errno = curl_errno($ch);
  $this->error = $error = curl_error($ch);
  curl_close ($ch);

  if ($errno) {
	  error_log("CURL error. Code: $errno, Message: $error");
	  return false;
  } else {
	  return $ret;
  }
}

// convert SimpleXML to PhpObj
public static function convertXmlToPhpObj ($node)
{
  $ret = array();

  if (is_object($node) && $node->hasChildNodes()) {
	foreach ($node->childNodes as $child) {
	  $name = self::decode($child->nodeName);
	  if ($child->nodeType == XML_TEXT_NODE) {
		$ret = self::decode($child->nodeValue);
	  } else {
		if ('array' === $name) {
		  return self::parseArray($child);
		} else {
		  $ret[$name] = self::convertXmlToPhpObj($child);
		}
	  }
	}
  }
  return 0 < count($ret) ? $ret : null;
}

// parse array
protected static function parseArray ($node)
{
  $ret = array();
  foreach ($node->childNodes as $child) {
	$name = self::decode($child->nodeName);
	if ('item' !== $name) {
	  throw new OP_API_Exception('Wrong message format');
	}
	$ret[] = self::convertXmlToPhpObj($child);
  }
  return $ret;
}

/**
 * converts php-structure to DOM-object.
 *
 * @param array $arr php-structure
 * @param SimpleXMLElement $node parent node where new element to attach
 * @param DOMDocument $dom DOMDocument object
 * @return SimpleXMLElement
 */
public static function convertPhpObjToDom ($arr, $node, $dom)
{
  if (is_array($arr)) {
	/**
	 * If arr has integer keys, this php-array must be converted in
	 * xml-array representation (<array><item>..</item>..</array>)
	 */
	$arrayParam = array();
	foreach ($arr as $k => $v) {
	  if (is_integer($k)) {
		$arrayParam[] = $v;
	  }
	}
	if (0 < count($arrayParam)) {
	  $node->appendChild($arrayDom = $dom->createElement("array"));
	  foreach ($arrayParam as $key => $val) {
		$new = $arrayDom->appendChild($dom->createElement('item'));
		self::convertPhpObjToDom($val, $new, $dom);
	  }
	} else {
	  foreach ($arr as $key => $val) {
		$new = $node->appendChild(
		  $dom->createElement(self::encode($key))
		);
		self::convertPhpObjToDom($val, $new, $dom);
	  }
	}
  } else {
	$node->appendChild($dom->createTextNode(self::encode($arr)));
  }
}
}

class OP_Request
{
protected $cmd = null;
protected $args = null;
protected $username = null;
protected $password = null;
protected $token = null;
protected $ip = null;
protected $language = null;
protected $raw = null;

public function __construct ($str = null)
{
  if ($str) {
	$this->raw = $str;
	$this->_parseRequest($str);
  }
}

/*
 * Parse request string to assign object properties with command name and 
 * arguments structure
 *
 * @return void
 *
 * @uses OP_Request::__construct()
 */
protected function _parseRequest ($str = "")
{
  $dom = new DOMDocument;
  $dom->loadXML($str);
  $arr = OP_API::convertXmlToPhpObj($dom->documentElement);
  list($dummy, $credentials) = each($arr);
  list($this->cmd, $this->args) = each($arr);
  $this->username = $credentials['username'];
  $this->password = $credentials['password'];
  $this->token = isset($credentials['token']) ? $credentials['token'] : null;
  $this->ip = isset($credentials['ip']) ? $credentials['ip'] : null;
  if (isset($credentials['language'])) {
	$this->language = $credentials['language'];
  }
}

public function setCommand ($v)
{
  $this->cmd = $v;
  return $this;
}

public function getCommand ()
{
  return $this->cmd;
}

public function setLanguage ($v)
{
  $this->language = $v;
  return $this;
}

public function getLanguage ()
{
  return $this->language;
}

public function setArgs ($v)
{
  $this->args = $v;
  return $this;
}

public function getArgs ()
{
  return $this->args;
}

public function setAuth ($args)
{
  $this->username = isset($args["username"]) ? $args["username"] : null;
  $this->password = isset($args["password"]) ? $args["password"] : null;
  $this->token = isset($args["token"]) ? $args["token"] : null;
  $this->ip = isset($args["ip"]) ? $args["ip"] : null;
  return $this;
}

public function getAuth ()
{
  return array(
	"username" => $this->username,
	"password" => $this->password,
	"token" => $this->token,
	"ip" => $this->ip
  );
}

public function getRaw ()
{
  if (!$this->raw) {
	$this->raw .= $this->_getRequest();
  }
  return $this->raw;
}

public function _getRequest ()
{
  $dom = new DOMDocument('1.0', OP_API::$encoding);

  $credentialsElement = $dom->createElement('credentials');
  $usernameElement = $dom->createElement('username');
  $usernameElement->appendChild(
	$dom->createTextNode(OP_API::encode($this->username))
  );
  $credentialsElement->appendChild($usernameElement);

  $passwordElement = $dom->createElement('password');
  $passwordElement->appendChild(
	$dom->createTextNode(OP_API::encode($this->password))
  );
  $credentialsElement->appendChild($passwordElement);
  
  if (isset($this->language)) {
	$languageElement = $dom->createElement('language');
	$languageElement->appendChild($dom->createTextNode($this->language));
	$credentialsElement->appendChild($languageElement);
  }

  if (isset($this->token)) {
	$tokenElement = $dom->createElement('token');
	$tokenElement->appendChild($dom->createTextNode($this->token));
	$credentialsElement->appendChild($tokenElement);
  }

  if (isset($this->ip)) {
	$ipElement = $dom->createElement('ip');
	$ipElement->appendChild($dom->createTextNode($this->ip));
	$credentialsElement->appendChild($ipElement);
  }

  $rootElement = $dom->createElement('openXML');
  $rootElement->appendChild($credentialsElement);

  $rootNode = $dom->appendChild($rootElement);
  $cmdNode = $rootNode->appendChild(
	$dom->createElement($this->getCommand())
  );
  OP_API::convertPhpObjToDom($this->args, $cmdNode, $dom);

  return $dom->saveXML();    
}
}

class OP_Reply
{
protected $faultCode = 0;
protected $faultString = null;
protected $value = array();
protected $raw = null;
public function __construct ($str = null) {
  if ($str) {
	$this->raw = $str;
	$this->_parseReply($str);
  }
}

protected function _parseReply ($str = "")
{
  $dom = new DOMDocument;
  $dom->loadXML($str);
  $arr = OP_API::convertXmlToPhpObj($dom->documentElement);
  $this->faultCode = (int) $arr['reply']['code'];
  $this->faultString = $arr['reply']['desc'];
  $this->value = $arr['reply']['data'];
}

public function setFaultCode ($v)
{
  $this->faultCode = $v;
  return $this;
}

public function setFaultString ($v)
{
  $this->faultString = $v;
  return $this;
}

public function setValue ($v)
{
  $this->value = $v;
  return $this;
}

public function getValue ()
{
  return $this->value;
}

public function getFaultString ()
{
  return $this->faultString;
}

public function getFaultCode ()
{
  return $this->faultCode;
}

public function getRaw ()
{
  if (!$this->raw) {
	$this->raw .= $this->_getReply ();
  }
  return $this->raw;
}

public function _getReply ()
{
  $dom = new DOMDocument('1.0', OP_API::$encoding);
  $rootNode = $dom->appendChild($dom->createElement('openXML'));
  $replyNode = $rootNode->appendChild($dom->createElement('reply'));
  $codeNode = $replyNode->appendChild($dom->createElement('code'));
  $codeNode->appendChild($dom->createTextNode($this->faultCode));
  $descNode = $replyNode->appendChild($dom->createElement('desc'));
  $descNode->appendChild(
	$dom->createTextNode(OP_API::encode($this->faultString))
  );
  $dataNode = $replyNode->appendChild($dom->createElement('data'));
  OP_API::convertPhpObjToDom($this->value, $dataNode, $dom);
  return $dom->saveXML();    
}
}

function openproviderssl_getPhoneInOpenproviderFormat($fullPhoneNumber) {
	$pos = strpos($fullPhoneNumber, '.');
	$countryCode = substr($fullPhoneNumber, 0, $pos);
	$areaCodeLength = 3;
	$areaCode = substr($fullPhoneNumber, $pos + 1, $areaCodeLength);
	$phoneNumber = substr($fullPhoneNumber, $pos + 1 + $areaCodeLength);
	return array ('countryCode' => $countryCode,'areaCode' => $areaCode,'subscriberNumber' => $phoneNumber);
}

function openproviderssl_getAddressInOpenproviderFormat($fullAddress) {
	$matches = array();
	if (preg_match('/^(\d+),?(.+)$/', $fullAddress, $matches)) {
	$fullAddress = trim($matches[2].' '.$matches[1]);
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
	$street .= ' '.$tmp[$cnt];
	$cnt++;
	}
	if ($cnt < $addressLength) {
	$number = $tmp[$cnt];
	$cnt++;
	
	while ($cnt < $addressLength) {
	$suffix .= $tmp[$cnt].' ';
	$cnt++;
	}
	}
	return array ('street' => $street, 'number' => $number, 'suffix' => trim($suffix));
}
?>