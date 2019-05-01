<?php
namespace doitcloudconsulting\polls\Controllers\sfdc;

/**
 * require_once ('SforceEmail.php');
 * require_once ('SforceProcessRequest.php');
 * require_once ('ProxySettings.php');
 * require_once ('SforceHeaderOptions.php');
 */


/**
 * { SforceEmail }
 */
use doitcloudconsulting\polls\Controllers\sfdc\SingleEmailMessage;
use doitcloudconsulting\polls\Controllers\sfdc\MassEmailMessage;

/**
 * { SforceProcessRequest }
 */
use doitcloudconsulting\polls\Controllers\sfdc\ProcessSubmitRequest;
use doitcloudconsulting\polls\Controllers\sfdc\ProcessWorkitemRequest;

/**
 * { ProxySettings }
 */
use doitcloudconsulting\polls\Controllers\sfdc\ProxySettings;


/**
 * { SforceHeaderOptions }
 */
use doitcloudconsulting\polls\Controllers\sfdc\CallOptions;
use doitcloudconsulting\polls\Controllers\sfdc\AssignmentRuleHeader;
use doitcloudconsulting\polls\Controllers\sfdc\MruHeader;
use doitcloudconsulting\polls\Controllers\sfdc\LoginScopeHeader;
use doitcloudconsulting\polls\Controllers\sfdc\QueryOptions;
use doitcloudconsulting\polls\Controllers\sfdc\EmailHeader;
use doitcloudconsulting\polls\Controllers\sfdc\UserTerritoryDeleteHeader;
use doitcloudconsulting\polls\Controllers\sfdc\AllowFieldTruncationHeader;
use doitcloudconsulting\polls\Controllers\sfdc\LocaleOptions;
use doitcloudconsulting\polls\Controllers\sfdc\PackageVersionHeader;
use doitcloudconsulting\polls\Controllers\sfdc\PackageVersion;

/**
 * { Internal Classes }
 */

use doitcloudconsulting\polls\Controllers\sfdc\SforceSearchResult;
use doitcloudconsulting\polls\Controllers\sfdc\QueryResult;
use doitcloudconsulting\polls\Controllers\sfdc\SObject; /** evaluar si es necesario que sea cargado aquí*/

use SoapClient;

class SforceBaseClient {
	protected $sforce;
	protected $sessionId;
	protected $location;
	protected $version = '27.0';

	protected $namespace;

	// Header Options
	protected $callOptions;
	protected $assignmentRuleHeader;
	protected $emailHeader;
	protected $loginScopeHeader;
	protected $mruHeader;
	protected $queryHeader;
	protected $userTerritoryDeleteHeader;
	protected $sessionHeader;
	
	// new headers
	protected $allowFieldTruncationHeader;
	protected $localeOptions;
	protected $packageVersionHeader;
	
  protected function getSoapClient($wsdl, $options) {
		return new SoapClient($wsdl, $options);      
  }
	
	public function getNamespace() {
		return $this->namespace;
	}


	// clientId specifies which application or toolkit is accessing the
	// salesforce.com API. For applications that are certified salesforce.com
	// solutions, replace this with the value provided by salesforce.com.
	// Otherwise, leave this value as 'phpClient/1.0'.
	protected $client_id;

	public function printDebugInfo() {
		echo "PHP Toolkit Version: $this->version\r\n";
		echo 'Current PHP version: ' . phpversion();
		echo "\r\n";
		echo 'SOAP enabled: ';
		if (extension_loaded('soap')) {
			echo 'True';
		} else {
			echo 'False';
		}
		echo "\r\n";
		echo 'OpenSSL enabled: ';
		if (extension_loaded('openssl')) {
			echo 'True';
		} else {
			echo 'False';
		}
	}
	
	/**
	 * Connect method to www.salesforce.com
	 *
	 * @param string $wsdl   Salesforce.com Partner WSDL
   * @param object $proxy  (optional) proxy settings with properties host, port,
   *                       login and password
   * @param array $soap_options (optional) Additional options to send to the
   *                       SoapClient constructor. @see
   *                       http://php.net/manual/en/soapclient.soapclient.php
	 */
	public function createConnection($wsdl, $proxy=null, $soap_options=array()) {
		$phpversion = substr(phpversion(), 0, strpos(phpversion(), '-'));
		
		$soapClientArray = array_merge(array (
			'user_agent' => 'salesforce-toolkit-php/'.$this->version,
			'encoding' => 'utf-8',
			'trace' => 1,
			'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
			'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
		), $soap_options);

		// We don't need to parse out any subversion suffix - e.g. "-01" since
		// PHP type conversion will ignore it
		if (phpversion() < 5.2) {
			die("PHP versions older than 5.2 are no longer supported. Please upgrade!");
		}

		if ($proxy != null) {
            $proxySettings = array();
            $proxySettings['proxy_host'] = $proxy->host;
            $proxySettings['proxy_port'] = $proxy->port; // Use an integer, not a string
            $proxySettings['proxy_login'] = $proxy->login; 
            $proxySettings['proxy_password'] = $proxy->password;
            $soapClientArray = array_merge($soapClientArray, $proxySettings);
		}

  		$this->sforce = $this->getSoapClient($wsdl, $soapClientArray);

		return $this->sforce;
	}

	public function setCallOptions($header) {
		if ($header != NULL) {
			$this->callOptions = new \SoapHeader($this->namespace, 'CallOptions', array (
		  'client' => $header->client,
		  'defaultNamespace' => $header->defaultNamespace
			));
		} else {
			$this->callOptions = NULL;
		}
	}

	/**
	 * Login to Salesforce.com and starts a client session.
	 *
	 * @param string $username   Username
	 * @param string $password   Password
	 *
	 * @return LoginResult
	 */
	public function login($username, $password) {
		$this->sforce->__setSoapHeaders(NULL);
		if ($this->callOptions != NULL) {
			$this->sforce->__setSoapHeaders(array($this->callOptions));
		}
		if ($this->loginScopeHeader != NULL) {
			$this->sforce->__setSoapHeaders(array($this->loginScopeHeader));
		}
		$result = $this->sforce->login(array (
		 'username' => $username,
		 'password' => $password
		));
		$result = $result->result;
		$this->_setLoginHeader($result);
		
		return $result;
	}

	/**
	 * log outs from the salseforce system`
	 *
	 * @return LogoutResult
	 */
	public function logout() {
        $this->setHeaders("logout");
		$arg = new \stdClass();
		return $this->sforce->logout();
	}
 
	/**
	 *invalidate Sessions from the salseforce system`
	 *
	 * @return invalidateSessionsResult
	 */
	public function invalidateSessions() {
        $this->setHeaders("invalidateSessions");
		$arg = new \stdClass();
        $this->logout();
		return $this->sforce->invalidateSessions();
	} 
 
	/**
	 * Specifies the session ID returned from the login server after a successful
	 * login.
	 */
	protected function _setLoginHeader($loginResult) {
		$this->sessionId = $loginResult->sessionId;
		$this->setSessionHeader($this->sessionId);
		$serverURL = $loginResult->serverUrl;
		$this->setEndPoint($serverURL);
	}

	/**
	 * Set the endpoint.
	 *
	 * @param string $location   Location
	 */
	public function setEndpoint($location) {
		$this->location = $location;
		$this->sforce->__setLocation($location);
	}

	private function setHeaders($call=NULL) {
		$this->sforce->__setSoapHeaders(NULL);
		
		$header_array = array (
			$this->sessionHeader
		);

		$header = $this->callOptions;
		if ($header != NULL) {
			array_push($header_array, $header);
		}

		if ($call == "create" ||
		$call == "merge" ||
		$call == "update" ||
		$call == "upsert"
		) {
			$header = $this->assignmentRuleHeader;
			if ($header != NULL) {
				array_push($header_array, $header);
			}
		}

		if ($call == "login") {
			$header = $this->loginScopeHeader;
			if ($header != NULL) {
				array_push($header_array, $header);
			}
		}

		if ($call == "create" ||
		$call == "resetPassword" ||
		$call == "update" ||
		$call == "upsert"
		) {
			$header = $this->emailHeader;
			if ($header != NULL) {
				array_push($header_array, $header);
			}
		}

		if ($call == "create" ||
		$call == "merge" ||
		$call == "query" ||
		$call == "retrieve" ||
		$call == "update" ||
		$call == "upsert"
		) {
			$header = $this->mruHeader;
			if ($header != NULL) {
				array_push($header_array, $header);
			}
		}

		if ($call == "delete") {
			$header = $this->userTerritoryDeleteHeader;
			if ($header != NULL) {
				array_push($header_array, $header);
			}
		}

		if ($call == "query" ||
		$call == "queryMore" ||
		$call == "retrieve") {
			$header = $this->queryHeader;
			if ($header != NULL) {
				array_push($header_array, $header);
			}
		}
		
		// try to add allowFieldTruncationHeader
		$allowFieldTruncationHeaderCalls = array(
			'convertLead', 'create', 'merge',
			'process', 'undelete', 'update',
			'upsert',
		);
		if (in_array($call, $allowFieldTruncationHeaderCalls)) {
			$header = $this->allowFieldTruncationHeader;
			if ($header != NULL) {
				array_push($header_array, $header);
			}
		}
		
		// try to add localeOptions
		if ($call == 'describeSObject' || $call == 'describeSObjects') {
			$header = $this->localeOptions;
			if ($header != NULL) {
				array_push($header_array, $header);
			}
		}
		
		// try to add PackageVersionHeader
		$packageVersionHeaderCalls = array(
			'convertLead', 'create', 'delete', 'describeGlobal',
			'describeLayout', 'describeSObject', 'describeSObjects',
			'describeSoftphoneLayout', 'describeTabs', 'merge',
			'process', 'query', 'retrieve', 'search', 'undelete',
			'update', 'upsert',
		);
		if(in_array($call, $packageVersionHeaderCalls)) {
			$header = $this->packageVersionHeader;
			if ($header != NULL) {
				array_push($header_array, $header);
			}
		}
		
		
		$this->sforce->__setSoapHeaders($header_array);
	}

	public function setAssignmentRuleHeader($header) {
		if ($header != NULL) {
			$this->assignmentRuleHeader = new \SoapHeader($this->namespace, 'AssignmentRuleHeader', array (
			 'assignmentRuleId' => $header->assignmentRuleId,
			 'useDefaultRule' => $header->useDefaultRuleFlag
			));
		} else {
			$this->assignmentRuleHeader = NULL;
		}
	}

	public function setEmailHeader($header) {
		if ($header != NULL) {
			$this->emailHeader = new \SoapHeader($this->namespace, 'EmailHeader', array (
			 'triggerAutoResponseEmail' => $header->triggerAutoResponseEmail,
			 'triggerOtherEmail' => $header->triggerOtherEmail,
			 'triggerUserEmail' => $header->triggerUserEmail
			));
		} else {
			$this->emailHeader = NULL;
		}
	}

	public function setLoginScopeHeader($header) {
		if ($header != NULL) {
			$this->loginScopeHeader = new \SoapHeader($this->namespace, 'LoginScopeHeader', array (
		'organizationId' => $header->organizationId,
		'portalId' => $header->portalId
			));
		} else {
			$this->loginScopeHeader = NULL;
		}
		//$this->setHeaders('login');
	}

	public function setMruHeader($header) {
		if ($header != NULL) {
			$this->mruHeader = new \SoapHeader($this->namespace, 'MruHeader', array (
			 'updateMru' => $header->updateMruFlag
			));
		} else {
			$this->mruHeader = NULL;
		}
	}

	public function setSessionHeader($id) {
		if ($id != NULL) {
			$this->sessionHeader = new \SoapHeader($this->namespace, 'SessionHeader', array (
			 'sessionId' => $id
			));
			$this->sessionId = $id;
		} else {
			$this->sessionHeader = NULL;
			$this->sessionId = NULL;
		}
	}

	public function setUserTerritoryDeleteHeader($header) {
		if ($header != NULL) {
			$this->userTerritoryDeleteHeader = new \SoapHeader($this->namespace, 'UserTerritoryDeleteHeader  ', array (
			 'transferToUserId' => $header->transferToUserId
			));
		} else {
			$this->userTerritoryDeleteHeader = NULL;
		}
	}

	public function setQueryOptions($header) {
		if ($header != NULL) {
			$this->queryHeader = new \SoapHeader($this->namespace, 'QueryOptions', array (
			 'batchSize' => $header->batchSize
			));
		} else {
			$this->queryHeader = NULL;
		}
	}
	
	public function setAllowFieldTruncationHeader($header) {
		if ($header != NULL) {
			$this->allowFieldTruncationHeader = new \SoapHeader($this->namespace, 'AllowFieldTruncationHeader', array (
					'allowFieldTruncation' => $header->allowFieldTruncation
				)
			);
		} else {
			$this->allowFieldTruncationHeader = NULL;
		}
	}
	
	public function setLocaleOptions($header) {
		if ($header != NULL) {
			$this->localeOptions = new \SoapHeader($this->namespace, 'LocaleOptions',
				array (
					'language' => $header->language
				)
			);
		} else {
			$this->localeOptions = NULL;
		}
	}
	
	/**
	 * @param $header
	 */
	public function setPackageVersionHeader($header) {
		if ($header != NULL) {
			$headerData = array('packageVersions' => array());
			
			foreach ($header->packageVersions as $key => $hdrElem) {
				$headerData['packageVersions'][] = array(
					'majorNumber' => $hdrElem->majorNumber,
					'minorNumber' => $hdrElem->minorNumber,
					'namespace' => $hdrElem->namespace,
				);
			}
			
			$this->packageVersionHeader = new \SoapHeader($this->namespace,
				'PackageVersionHeader',
				$headerData
			);
		} else {
			$this->packageVersionHeader = NULL;
		}
	}

	public function getSessionId() {
		return $this->sessionId;
	}

	public function getLocation() {
		return $this->location;
	}

	public function getConnection() {
		return $this->sforce;
	}

	public function getFunctions() {
		return $this->sforce->__getFunctions();
	}

	public function getTypes() {
		return $this->sforce->__getTypes();
	}

	public function getLastRequest() {
		return $this->sforce->__getLastRequest();
	}

	public function getLastRequestHeaders() {
		return $this->sforce->__getLastRequestHeaders();
	}

	public function getLastResponse() {
		return $this->sforce->__getLastResponse();
	}

	public function getLastResponseHeaders() {
		return $this->sforce->__getLastResponseHeaders();
	}

	protected function _convertToAny($fields) {
		$anyString = '';
		foreach ($fields as $key => $value) {
			$anyString = $anyString . '<' . $key . '>' . $value . '</' . $key . '>';
		}
		return $anyString;
	}

	protected function _create($arg) {
		$this->setHeaders("create");
		return $this->sforce->create($arg)->result;
	}

	protected function _merge($arg) {
		$this->setHeaders("merge");
		return $this->sforce->merge($arg)->result;
	}

	protected function _process($arg) {
		$this->setHeaders();
		return $this->sforce->process($arg)->result;
	}

	protected function _update($arg) {
		$this->setHeaders("update");
		return $this->sforce->update($arg)->result;
	}

	protected function _upsert($arg) {
		$this->setHeaders("upsert");
		return $this->sforce->upsert($arg)->result;
	}

  public function sendSingleEmail($request) {
	if (is_array($request)) {
	  $messages = array();
	  foreach ($request as $r) {
		$email = new \SoapVar($r, SOAP_ENC_OBJECT, 'SingleEmailMessage', $this->namespace);
		array_push($messages, $email);
	  }
	  $arg = new \stdClass();
	  $arg->messages = $messages;
	  return $this->_sendEmail($arg);
	} else {
	  $backtrace = debug_backtrace();
	  die('Please pass in array to this function:  '.$backtrace[0]['function']);
	}
  }

  public function sendMassEmail($request) {
	if (is_array($request)) {
	  $messages = array();
	  foreach ($request as $r) {
		$email = new \SoapVar($r, SOAP_ENC_OBJECT, 'MassEmailMessage', $this->namespace);
		array_push($messages, $email);
	  }
	  $arg = new \stdClass();
	  $arg->messages = $messages;
	  return $this->_sendEmail($arg);
	} else {
	  $backtrace = debug_backtrace();
	  die('Please pass in array to this function:  '.$backtrace[0]['function']);
	}
  } 
	
	protected function _sendEmail($arg) {
		$this->setHeaders();
		return $this->sforce->sendEmail($arg)->result;
	}

	/**
	 * Converts a Lead into an Account, Contact, or (optionally) an Opportunity.
	 *
	 * @param array $leadConverts    Array of LeadConvert
	 *
	 * @return LeadConvertResult
	 */
	public function convertLead($leadConverts) {
		$this->setHeaders("convertLead");
		$arg = new \stdClass();
		$arg->leadConverts = $leadConverts;
		return $this->sforce->convertLead($arg);
	}

	/**
	 * Deletes one or more new individual objects to your organization's data.
	 *
	 * @param array $ids    Array of fields
	 * @return DeleteResult
	 */
	public function delete($ids) {
		$this->setHeaders("delete");
		if(count($ids) > 200) {
			$result = array();
			$chunked_ids = array_chunk($ids, 200);
			foreach($chunked_ids as $cids) {
				$arg = new \stdClass;
				$arg->ids = $cids;
				$result = array_merge($result, $this->sforce->delete($arg)->result);
			}
		} else {
			$arg = new \stdClass;
			$arg->ids = $ids;
			$result = $this->sforce->delete($arg)->result;
		}
		return $result;
	}

	/**
	 * Deletes one or more new individual objects to your organization's data.
	 *
	 * @param array $ids    Array of fields
	 * @return DeleteResult
	 */
	public function undelete($ids) {
		$this->setHeaders("undelete");
		$arg = new \stdClass();
		$arg->ids = $ids;
		return $this->sforce->undelete($arg)->result;
	}

	/**
	 * Deletes one or more new individual objects to your organization's data.
	 *
	 * @param array $ids    Array of fields
	 * @return DeleteResult
	 */
	public function emptyRecycleBin($ids) {
		$this->setHeaders();
		$arg = new \stdClass();
		$arg->ids = $ids;
		return $this->sforce->emptyRecycleBin($arg)->result;
	}

	/**
	 * Process Submit Request for Approval
	 *
	 * @param array $processRequestArray
	 * @return ProcessResult
	 */
	public function processSubmitRequest($processRequestArray) {
		if (is_array($processRequestArray)) {
			foreach ($processRequestArray as &$process) {
				$process = new \SoapVar($process, SOAP_ENC_OBJECT, 'ProcessSubmitRequest', $this->namespace);
			}
			$arg = new \stdClass();
			$arg->actions = $processRequestArray;
			return $this->_process($arg);
		} else {
			$backtrace = debug_backtrace();
			die('Please pass in array to this function:  '.$backtrace[0]['function']);
		}
	}

	/**
	 * Process Work Item Request for Approval
	 *
	 * @param array $processRequestArray
	 * @return ProcessResult
	 */
	public function processWorkitemRequest($processRequestArray) {
		if (is_array($processRequestArray)) {
			foreach ($processRequestArray as &$process) {
				$process = new \SoapVar($process, SOAP_ENC_OBJECT, 'ProcessWorkitemRequest', $this->namespace);
			}
			$arg = new \stdClass();
			$arg->actions = $processRequestArray;
			return $this->_process($arg);
		} else {
			$backtrace = debug_backtrace();
			die('Please pass in array to this function:  '.$backtrace[0]['function']);
		}
	}

	/**
	 * Retrieves a list of available objects for your organization's data.
	 *
	 * @return DescribeGlobalResult
	 */
	public function describeGlobal() {
		$this->setHeaders("describeGlobal");
		return $this->sforce->describeGlobal()->result;
	}

	/**
	 * Use describeLayout to retrieve information about the layout (presentation
	 * of data to users) for a given object type. The describeLayout call returns
	 * metadata about a given page layout, including layouts for edit and
	 * display-only views and record type mappings. Note that field-level security
	 * and layout editability affects which fields appear in a layout.
	 *
	 * @param string Type   Object Type
	 * @return DescribeLayoutResult
	 */
	public function describeLayout($type, array $recordTypeIds=null) {
		$this->setHeaders("describeLayout");
		$arg = new \stdClass();
		$arg->sObjectType = new \SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
		if (isset($recordTypeIds) && count($recordTypeIds)) 
			$arg->recordTypeIds = $recordTypeIds;
		return $this->sforce->describeLayout($arg)->result;
	}

	/**
	 * Describes metadata (field list and object properties) for the specified
	 * object.
	 *
	 * @param string $type    Object type
	 * @return DescribsSObjectResult
	 */
	public function describeSObject($type) {
		$this->setHeaders("describeSObject");
		$arg = new \stdClass();
		$arg->sObjectType = new \SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
		return $this->sforce->describeSObject($arg)->result;
	}

	/**
	 * An array-based version of describeSObject; describes metadata (field list
	 * and object properties) for the specified object or array of objects.
	 *
	 * @param array $arrayOfTypes    Array of object types.
	 * @return DescribsSObjectResult
	 */
	public function describeSObjects($arrayOfTypes) {
		$this->setHeaders("describeSObjects");
		return $this->sforce->describeSObjects($arrayOfTypes)->result;
	}

	/**
	 * The describeTabs call returns information about the standard apps and
	 * custom apps, if any, available for the user who sends the call, including
	 * the list of tabs defined for each app.
	 *
	 * @return DescribeTabSetResult
	 */
	public function describeTabs() {
		$this->setHeaders("describeTabs");
		return $this->sforce->describeTabs()->result;
	}

	/**
	 * To enable data categories groups you must enable Answers or Knowledge Articles module in
	 * admin panel, after adding category group and assign it to Answers or Knowledge Articles
	 *
	 * @param string $sObjectType   sObject Type
	 * @return DescribeDataCategoryGroupResult
	 */
	public function describeDataCategoryGroups($sObjectType) {
		$this->setHeaders('describeDataCategoryGroups');
		$arg = new \stdClass();
		$arg->sObjectType = new \SoapVar($sObjectType, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
		return $this->sforce->describeDataCategoryGroups($arg)->result;
	}

	/**
	 * Retrieves available category groups along with their data category structure for objects specified in the request.
	 *
	 * @param DataCategoryGroupSobjectTypePair $pairs 
	 * @param bool $topCategoriesOnly   Object Type
	 * @return DescribeLayoutResult
	 */
	public function describeDataCategoryGroupStructures(array $pairs, $topCategoriesOnly) {
		$this->setHeaders('describeDataCategoryGroupStructures');
		$arg = new \stdClass();
		$arg->pairs = $pairs;
		$arg->topCategoriesOnly = new \SoapVar($topCategoriesOnly, XSD_BOOLEAN, 'boolean', 'http://www.w3.org/2001/XMLSchema');

		return $this->sforce->describeDataCategoryGroupStructures($arg)->result;
	}

	/**
	 * Retrieves the list of individual objects that have been deleted within the
	 * given timespan for the specified object.
	 *
	 * @param string $type    Ojbect type
	 * @param date $startDate  Start date
	 * @param date $endDate   End Date
	 * @return GetDeletedResult
	 */
	public function getDeleted($type, $startDate, $endDate) {
		$this->setHeaders("getDeleted");
		$arg = new \stdClass();
		$arg->sObjectType = new \SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
		$arg->startDate = $startDate;
		$arg->endDate = $endDate;


		return $this->sforce->getDeleted($arg)->result;
	}

	/**
	 * Retrieves the list of individual objects that have been updated (added or
	 * changed) within the given timespan for the specified object.
	 *
	 * @param string $type    Ojbect type
	 * @param date $startDate  Start date
	 * @param date $endDate   End Date
	 * @return GetUpdatedResult
	 */
	public function getUpdated($type, $startDate, $endDate) {
		$this->setHeaders("getUpdated");
		$arg = new \stdClass();
		$arg->sObjectType = new \SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
		$arg->startDate = $startDate;
		$arg->endDate = $endDate;
		return $this->sforce->getUpdated($arg)->result;
	}

	/**
	 * Executes a query against the specified object and returns data that matches
	 * the specified criteria.
	 *
	 * @param String $query Query String
	 * @param QueryOptions $queryOptions  Batch size limit.  OPTIONAL
	 * @return QueryResult
	 */
	public function query($query) {
		$this->setHeaders("query");
		$raw = $this->sforce->query(array (
					  'queryString' => $query
		))->result;
		$QueryResult = new QueryResult($raw);
		$QueryResult->setSf($this); // Dependency Injection
		return $QueryResult;
	}

	/**
	 * Retrieves the next batch of objects from a query.
	 *
	 * @param QueryLocator $queryLocator Represents the server-side cursor that tracks the current processing location in the query result set.
	 * @param QueryOptions $queryOptions  Batch size limit.  OPTIONAL
	 * @return QueryResult
	 */
	public function queryMore($queryLocator) {
		$this->setHeaders("queryMore");
		$arg = new \stdClass();
		$arg->queryLocator = $queryLocator;
		$raw = $this->sforce->queryMore($arg)->result;
		$QueryResult = new QueryResult($raw);
		$QueryResult->setSf($this); // Dependency Injection
		return $QueryResult;
	}

	/**
	 * Retrieves data from specified objects, whether or not they have been deleted.
	 *
	 * @param String $query Query String
	 * @param QueryOptions $queryOptions  Batch size limit.  OPTIONAL
	 * @return QueryResult
	 */
	public function queryAll($query, $queryOptions = NULL) {
		$this->setHeaders("queryAll");
		$raw = $this->sforce->queryAll(array (
						'queryString' => $query
		))->result;
		$QueryResult = new QueryResult($raw);
		$QueryResult->setSf($this); // Dependency Injection
		return $QueryResult;
	}


	/**
	 * Retrieves one or more objects based on the specified object IDs.
	 *
	 * @param string $fieldList      One or more fields separated by commas.
	 * @param string $sObjectType    Object from which to retrieve data.
	 * @param array $ids            Array of one or more IDs of the objects to retrieve.
	 * @return sObject[]
	 */
	public function retrieve($fieldList, $sObjectType, $ids) {
		$this->setHeaders("retrieve");
		$arg = new \stdClass();
		$arg->fieldList = $fieldList;
		$arg->sObjectType = new \SoapVar($sObjectType, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
		$arg->ids = $ids;
		return $this->sforce->retrieve($arg)->result;
	}

	/**
	 * Executes a text search in your organization's data.
	 *
	 * @param string $searchString   Search string that specifies the text expression to search for.
	 * @return SearchResult
	 */
	public function search($searchString) {
		$this->setHeaders("search");
		$arg = new \stdClass();
		$arg->searchString = new \SoapVar($searchString, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
		return new SforceSearchResult($this->sforce->search($arg)->result);
	}

	/**
	 * Retrieves the current system timestamp (GMT) from the Web service.
	 *
	 * @return timestamp
	 */
	public function getServerTimestamp() {
		$this->setHeaders("getServerTimestamp");
		return $this->sforce->getServerTimestamp()->result;
	}

	public function getUserInfo() {
		$this->setHeaders("getUserInfo");
		return $this->sforce->getUserInfo()->result;
	}

	/**
	 * Sets the specified user's password to the specified value.
	 *
	 * @param string $userId    ID of the User.
	 * @param string $password  New password
	 */
	public function setPassword($userId, $password) {
		$this->setHeaders("setPassword");
		$arg = new \stdClass();
		$arg->userId = new \SoapVar($userId, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
		$arg->password = $password;
		return $this->sforce->setPassword($arg);
	}

	/**
	 * Changes a user's password to a system-generated value.
	 *
	 * @param string $userId    Id of the User
	 * @return password
	 */
	public function resetPassword($userId) {
		$this->setHeaders("resetPassword");
		$arg = new \stdClass();
		$arg->userId = new \SoapVar($userId, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
		return $this->sforce->resetPassword($arg)->result;
	}
}
?>