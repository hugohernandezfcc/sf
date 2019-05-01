<?php

namespace doitcloudconsulting\polls\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use doitcloudconsulting\polls\Controllers\sfdc\SforcePartnerClient;
use doitcloudconsulting\polls\Controllers\sfdc\SObject;
use Response;
use doitcloudconsulting\polls\Controllers\sfdc\SforceValidationData;
use Carbon\Carbon;

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
        return $this->modeReturn($this->mySforceConnection->create($records), 'object');
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

            $sp = new SforceValidationData( $information );
            if(count($sp->result()) > 0 )
                return $sp->result();

            $validationT = SforceValidationData::readyToUpdate($information);
            if(!is_string($validationT) && is_bool($validationT)){
                if(config('SalesforceConfig.Mode') == 'partner'){

                    $sObject = new SObject();
                    $sObject->fields = $information;
                    $sObject->type = $object;
                    $sObject->id = (isset($information['id'])) ? $information['id'] : $information['Id'];
                    array_push($records, $sObject);   
                }
                return $this->modeReturn($this->mySforceConnection->update($records), 'json');
            }
        }
    }

    public function upsert($information, $object)
    {
        $records = array();
        $applyValidation = array();
        $field = '';
        $validationT = '';

        if ( $this->arrayType($information) == 'is_bidimensional') {
            for ($i=0; $i < count($information); $i++) { 

                $sp = new SforceValidationData( $information[$i] );

                if(count($sp->result()) > 0 )
                    return $sp->result();

                $validationT = SforceValidationData::readyToUpsert($information[$i]);

                if(is_bool($validationT) || strpos($validationT, "*") !== false ){
                    
                    if(!is_bool($validationT))
                        $field = str_replace('*', '', $validationT);
                    
                    if(config('SalesforceConfig.Mode') == 'partner'){

                        if(!is_bool($validationT)){
                            $information[$i][$field] = $information[$i][$validationT];
                            unset($information[$i][$validationT]);     
                        }
                        
                        $sObject = new SObject();
                        $sObject->fields = $information[$i];
                        $sObject->type = $object;

                        array_push($records, $sObject);   
                    }
                }    
            }

            if(!is_bool($validationT))
                return $this->modeReturn($this->mySforceConnection->upsert($field, $records), 'json');
            else
                return $this->modeReturn($this->mySforceConnection->upsert('id', $records), 'json');

        }else{

            $sp = new SforceValidationData( $information );

            if(count($sp->result()) > 0 )
                return $sp->result();


            $validationT = SforceValidationData::readyToUpsert($information);

            if(is_bool($validationT) || strpos($validationT, "*") !== false ){
                
                if(!is_bool($validationT))
                    $field = str_replace('*', '', $validationT);
                
                if(config('SalesforceConfig.Mode') == 'partner'){

                    if(!is_bool($validationT)){
                        $information[$field] = $information[$validationT];
                        unset($information[$validationT]);     
                    }
                    

                    $sObject = new SObject();
                    $sObject->fields = $information;
                    $sObject->type = $object;

                    //$sObject->id = (isset($information['id'])) ? $information['id'] : $information['Id'];
                    
                    array_push($records, $sObject);   
                }

                if(!is_bool($validationT))
                    return $this->modeReturn($this->mySforceConnection->upsert($field, $records), 'json');
                else
                    return $this->modeReturn($this->mySforceConnection->upsert('id', $records), 'json');

            }else{
                return 'External id or Id not found';
            }
        }
    }

    public function delete($ids)
    {        
        if(count($ids) > 0)
            return $this->modeReturn($this->mySforceConnection->delete($ids), 'json');
        else
            return 'Provide at least an Id in a the array()';
    }

    public function undelete($ids)
    {   
        if(count($ids) > 0)
            return $this->modeReturn($this->mySforceConnection->undelete($ids), 'json');
        else
            return 'Provide at least an Id in a the array()';
    }

    /**
     * function to convert a lead created on salesforce
     * @param  [type]  $leadId            [Lead Id created in salesforce ready to be converted]
     * @param  string  $convertedStatus   [Picklist value to close a lead, it is defined on the lead process settings]
     * @param  boolean $createOpportunity [True / False to execute a creation of opportunity by convertion]
     * @param  boolean $sendNotification  [True / False to trigger a notification when the lead will be converted]
     * @return object                     [result]
     */
    public function convertLead($leadId, $convertedStatus='Closed - Converted', $createOpportunity = true, $sendNotification = false)
    {
        if (!strlen($leadId) > 14 && !strlen($leadId) < 19) 
            return $leadId . ' The Id is not valid';
        


        $leadConvert = new \stdClass;
        $leadConvert->convertedStatus=$convertedStatus;
        $leadConvert->doNotCreateOpportunity=strval($createOpportunity);
        $leadConvert->leadId=$leadId;
        $leadConvert->overwriteLeadSource='true';
        $leadConvert->sendNotificationEmail=strval($sendNotification);
        
        return $this->modeReturn($this->mySforceConnection->convertLead(array($leadConvert)), 'object');
    }

    /**
     * Retrieves a list of available objects for your organization's data. 
     * @return [type] [description]
     */
    public function describeGlobal()
    {
        return $this->modeReturn($this->mySforceConnection->describeGlobal(), 'object');
    }

    /**
     * Retrieves metadata about page layouts for the specified object type. 
     * @param  [type] $layout [Layout Name]
     * @return [type]         [description]
     */
    public function describeLayout($layout)
    {
        return $this->modeReturn($this->mySforceConnection->describeLayout($layout), 'object');
    }

    /**
     * Retrieves metadata (field list and object properties) for the specified object type.
     * @param  [type] $object [description]
     * @return [type]         [description]
     */
    public function describeSObject($object)
    {
        return $this->modeReturn($this->mySforceConnection->describeSObject($object), 'object');
    }

    /**
     * Function responsable of catch a commond date and convert it to unix format.
     * @param  [type] $date [The date in commmond format]
     * @return [type]       [Unix format]
     */
    public static function toUnixTime($date)
    {
        if($date != null){
            $dateTime = new \DateTime($date); 
            return $dateTime->format('U');
        }else
            return "IS_EMPTY_VARIABLE";
    }


    /**
     * Function to retrive information about records deleted in a range date.
     * Retrieves the IDs of individual objects of the specified object that have been deleted since the specified time.
     *     The format right to dates passed as parameter is DD-MM-YYYY
     * 
     * @param  [type] $object    [Custom or Standard object]
     * @param  [type] $startDate [Date from where it will be searched]
     * @param  [type] $endDate   [Date from where it end searched]
     * @return [type]            [object]
     */
    public function getMeAllDeleted($object, $startDate, $endDate)
    {
        $date = Carbon::parse($startDate);
        $now = Carbon::parse($endDate);

        $diff = $date->diffInDays($now);


        if($diff >= 30)
            return 'INVALID_REPLICATION_DATE: startDate cannot be more than 30 days ago';
        

        return $this->modeReturn($this->mySforceConnection->getDeleted($object, intval(Self::toUnixTime($startDate)), intval(Self::toUnixTime($endDate))), 'object');

    }


    /**
     * Retrieves the IDs of individual objects of the specified object that have been updated since the specified time.
     * 
     * @param  [type] $object    [Custom or Standard object]
     * @param  [type] $startDate [Date from where it will be searched]
     * @param  [type] $endDate   [Date from where it end searched]
     * @return [type]            [object]
     */
    public function getMeAllUpdated($object, $startDate, $endDate)
    {
        $date = Carbon::parse($startDate);
        $now = Carbon::parse($endDate);

        $diff = $date->diffInDays($now);


        if($diff >= 30)
            return 'INVALID_REPLICATION_DATE: startDate cannot be more than 30 days ago';
        

        return $this->modeReturn($this->mySforceConnection->getUpdated($object, intval(Self::toUnixTime($startDate)), intval(Self::toUnixTime($endDate))), 'object');

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