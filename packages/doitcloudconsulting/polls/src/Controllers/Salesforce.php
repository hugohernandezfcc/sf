<?php

namespace doitcloudconsulting\polls\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use doitcloudconsulting\polls\Controllers\sfdc\SforcePartnerClient;
use doitcloudconsulting\polls\Controllers\sfdc\SObject;
use Response;



class Salesforce extends Controller
{
    public $mySforceConnection;
    public $loginInfo;


    function __construct()
    {
        $this->mySforceConnection = new SforcePartnerClient();
        $this->mySforceConnection->createConnection( $this->wsdl('partner.wsdl.xml') );
        $this->loginInfo = $this->mySforceConnection->login(config('SalesforceConfig.Username'), config('SalesforceConfig.Password') . config('SalesforceConfig.Token'));
    }

    public function loginInformation()
    {
        return $this->loginInfo;
    }

    /**
     * Provide information about the query string.
     *
     * @param      <type>  $query  The query
     *
     * @return     array   return an array with relevant data 
     */
    public function informationqueries($query)
    {
        $query = strtolower($query);
        return [
            'characters_number'           => strlen($query), 
            'custom_fields_number'        => substr_count($query, '__c') + substr_count($query, '__r'),
            'custom_relationship_number'  => substr_count($query, '__r'),
            'contains_identifier'         => (strpos($query, 'Id') !== false || strpos($query, 'id') !== false) ? true : 0,
            'contains_limit'              => (strpos($query, 'limit') !== false || strpos($query, 'LIMIT') !== false) ? true : 0,
            'is_valid_query'              => ((substr_count($query, 'select') + substr_count($query, 'from')) < 2) ? 'NOT_VALID_QUERY' : 'IS_VALID'
        ];
    }

    /**
     * Method used to execute a query soql with relevant information included on the response
     *
     * @param      <type>  $query  The query
     *
     * @return     <type>  depending the mode, is possible a json or an array or base64.
     */
    public function query($query, $mode = NULL, $returnJust = NULL)
    {

        if($returnJust != 'original' && $returnJust != 'data' && $returnJust != 'manageable' && !is_null($returnJust) )
          return 'ERROR_RETURN_BUNDLED_DATA';
        
        $response['data'] = $this->informationqueries($query);

        if($response['data']['is_valid_query'] == 'NOT_VALID_QUERY')
          return $response['data']['is_valid_query'];

        $response['original'] = $this->mySforceConnection->query(($query));
        $response['manageable'] = array();

        foreach ($response['original']->records as $record) {
                $recordToArray = new SObject($record);
                array_push($response['manageable'], $recordToArray);
        }

        switch ($returnJust) {
          case 'original':
            unset($response['manageable']);
            unset($response['data']);
            break;

          case 'data':
            unset($response['manageable']);
            unset($response['original']);
            break;

          case 'manageable':
            unset($response['data']);
            unset($response['original']);
            break;
        }

        switch ($mode) {
          case 'json':
            return json_encode($response);
            break;

          case 'base64':
            return base64_encode(json_encode($response));
            break;
          
          default:
              return $response;
            break;
        }
        
    }


    public function insert()
    {
        $fields = array (
            'Type' => 'Electrical'
        );

        $sObject = new SObject();
        $sObject->fields = $fields;
        $sObject->type = 'Case';

        $sObject2 = new SObject();
        $sObject2->fields = $fields;
        $sObject2->type = 'Case';
      
        echo "**** Creating the following:\r\n";
        $createResponse = $mySforceConnection->create(array($sObject, $sObject2));
    }

    public function index(Request $request)
    {
    	
        // $mySoapClient = $mySforceConnection->
        // $mylogin = $mySforceConnection->login('mayax@doitcloud.consulting', 'trayecta85IU2JyLDkiairgKI9G4Pap7a8');

        echo "<pre>";
        	print_r($this->query('SELECt Id, Name,BillingStreet,BillingCity,BillingState,Phone,Fax from Account LIMIT 5'));
        echo "</pre>";
  //       echo "<br/>===================<br/>";
        
  
	 //  	echo "<br/>===================<br/>";
		// echo "<pre>";
	 //  	print_r($mySforceConnection->getLastRequest());


 
  //       print_r($createResponse);

  //         $result = $mySforceConnection->describeSObject("Account");
  //         print_r($result);
    }

    public function wsdl($xml)
    {
    	$route = __DIR__ . '/' . $xml;
    	return str_replace('/Controllers/', '/', $route);
    }
}

?>