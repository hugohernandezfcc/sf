<?php

namespace doitcloudconsulting\polls\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use doitcloudconsulting\polls\Controllers\sfdc\SforcePartnerClient;
use doitcloudconsulting\polls\Controllers\sfdc\SObject;
use Response;
use doitcloudconsulting\polls\Controllers\sfdc\SforceValidationData;


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
     * @param  [type] $query      [Query to execute with the set of fields and name object and filters etc.]
     * @param  [type] $mode       [the structure type that will be returned when the execution finished]
     * @param  [type] $returnJust [is possible defined the result that the user want catch, the options are bewteen original, data and manageable ]
     * @return [type]             [list of records]
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
                $var = explode('services', $this->loginInfo->serverUrl);
                //dd($var[0]);
                $recordToArray->link_record = $var[0] . $recordToArray->Id;
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

        
        return $this->modeReturn($response, ((is_null($mode)) ? 'object' : $mode) );
    }

    /**
     * Help to define if an array is bidimensional or unidimensional couting the key and values by the type. 
     *
     * @param      <type>  $toBeEvaluated  To be evaluated
     *
     * @return     string  ( description_of_the_return_value )
     */
    public function arrayType($toBeEvaluated)
    {
      $size = count($toBeEvaluated);
      $sizeFinalRight = 0;
      foreach ($toBeEvaluated as $key => $value) 
        if (is_array($value) || is_int($key)) 
          $sizeFinalRight++;
        else
          $sizeFinalRight = $sizeFinalRight-1;
        

      if ($size == $sizeFinalRight) 
        return 'is_bidimensional';
      else
        return 'is_unidimensional';
      

    }


    

    public function insert($information, $object)
    {
      $records = array();
      $applyValidation = array();

      if ( $this->arrayType($information) == 'is_bidimensional') {
        for ($i=0; $i < count($information); $i++) { 

          $sp = new SforceValidationData( $information[$i] );
          if(count($sp->result()) > 0 )
            array_push($applyValidation, $sp->result());
            

          if(config('SalesforceConfig.Mode') == 'partner'){

            $sObject = new SObject();
            $sObject->fields = $information[$i];
            $sObject->type = $object;
            array_push($records, $sObject);   

          }
        }

        if(!count($applyValidation) > 0)
          return $this->modeReturn($this->mySforceConnection->create($records), 'object');
        else
          return $applyValidation;
      }else{

        $sp = new SforceValidationData( $information );
        if(count($sp->result()) > 0 )
          return $sp->result();

        if(config('SalesforceConfig.Mode') == 'partner'){

          $sObject = new SObject();
          $sObject->fields = $information;
          $sObject->type = $object;
          array_push($records, $sObject);   

        }
        return $this->modeReturn($this->mySforceConnection->create($records), 'json');
      }
    }

    public function update($information, $object)
    {
      $records = array();
      $applyValidation = array();

      if ( $this->arrayType($information) == 'is_bidimensional') {
        for ($i=0; $i < count($information); $i++) { 

          $sp = new SforceValidationData( $information[$i] );
          if(count($sp->result()) > 0 )
            array_push($applyValidation, $sp->result());

          
          $validationT = SforceValidationData::readyToUpdate($information[$i]);

          if(!is_string($validationT) && is_bool($validationT)){
            if(config('SalesforceConfig.Mode') == 'partner'){

              $sObject = new SObject();
              $sObject->fields = $information[$i];
              $sObject->type = $object;
              $sObject->id = (isset($information[$i]['id'])) ? $information[$i]['id'] : $information[$i]['Id'];

              array_push($records, $sObject);   

            }
          }
          
        }


        if(!count($applyValidation) > 0)
          return $this->modeReturn($this->mySforceConnection->update($records), 'object');
        else
          return $applyValidation;
      }else{

        // $sp = new SforceValidationData( $information );
        // if(count($sp->result()) > 0 )
        //   return $sp->result();

        // if(config('SalesforceConfig.Mode') == 'partner'){

        //   $sObject = new SObject();
        //   $sObject->fields = $information;
        //   $sObject->type = $object;
        //   array_push($records, $sObject);   

        // }
        // return $this->modeReturn($this->mySforceConnection->create($records), 'json');

      }
    }

    public function index(Request $request)
    {
    	
        // $mySoapClient = $mySforceConnection->
        // $mylogin = $mySforceConnection->login('mayax@doitcloud.consulting', 'trayecta85IU2JyLDkiairgKI9G4Pap7a8');

        // echo "<pre>";
          print_r($this->update( array(
                            array (
                                'Type' => 'Electrical2',
                                'id'  => '500f400000DwWZVAA3'
                          ),array (
                                'Type' => 'Electrical3',
                                'Id'  => '500f400000DwWZVAA3'
                          )), 'Case') );


        
  
	 //  	echo "<br/>===================<br/>";
		// echo "<pre>";
	 //  	print_r($mySforceConnection->getLastRequest());


 
  //       print_r($createResponse);

  //         $result = $mySforceConnection->describeSObject("Account");
  //         print_r($result);
    }


    public function modeReturn($response, $mode = null){

      $mode = strtolower($mode);

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

    public function wsdl($xml)
    {
    	$route = __DIR__ . '/' . $xml;
    	return str_replace('/Controllers/', '/', $route);
    }
}

?>