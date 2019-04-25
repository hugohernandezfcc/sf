<?php
namespace doitcloudconsulting\polls\Controllers\sfdc;

class SforceValidationData {

	public $answer;
	private $result; 
	public $toReturn;

	function __construct($fields)
   	{
   		$this->answer = array();

   		foreach ($fields as $key => $value) {
   			foreach (config('SalesforceConfig.Validation_Fields') as $prefix_key => $prefix_value) {
   				if(in_array($key, config('SalesforceConfig.Validation_Fields')[$prefix_key])){
   					$method = 'validation' . $prefix_key;
   					if(!empty($value)){
   						array_push($this->answer, $this->$method($key, $value));
   					}
   				}	
   			}
   		}

   		$this->setResult($this->answer);
   	}   

   	public function setResult($result)
   	{
   		$this->result = $result;
   	}

   	/**
   	 * function responsable of validate the id to execute an update transaction before to make an API Call.
   	 *
   	 * @param      <type>  $record  Field sets to update
   	 *
   	 * @return     string  ( description_of_the_return_value )
   	 */
   	public static function readyToUpdate($record)
   	{
   		$counter = 0;
      $toReturn = null;
   		foreach ($record as $key => $value) {
   			
   			if (strtolower($key) == 'id') {

   				if (empty($value)) {
   					return json_encode($record) . ' => The Id field is empty';
   				}

   				if (!strlen($value) > 14 && !strlen($value) < 19) {
   					return ' The Id is not valid';
   				}

   			 	$toReturn = true;
          break;
   			} 
        $counter++;
   		}

   		if (count($record) == $counter) {
   			$toReturn = json_encode($record) . ' => Not contains an id field';
   		}

   		return $toReturn;
   	}

   	public function result()
   	{
   		return $this->result;
   	}

   	public function validationphones($field, $datafield)
    {
    	// Allow +, - and . in phone number
	    $filtered_phone_number = filter_var($datafield, FILTER_SANITIZE_NUMBER_INT);
	     // Remove "-" from number
	    $phone_to_check = str_replace("-", "", $filtered_phone_number);
	     
	     // Check the lenght of number
	     // This can be customized if you want phone number from a specific country
	    if (strlen($phone_to_check) < 10 || strlen($phone_to_check) > 14)
	        return 'The field ' . $field . ' not contains a phone number valid: ' . $datafield;
    }

	public function validationemails($field, $datafield)
    {
    	if (!filter_var($datafield, FILTER_VALIDATE_EMAIL))
    		return 'The field ' . $field . ' not contains an email address valid: ' . $datafield;
    }

    public function validationdates($field, $datafield)
    {
    	//TBD
    }

    public function validationurl($field, $datafield)
    {
    	if (!filter_var($datafield, FILTER_VALIDATE_URL))
    	return 'The field ' . $field . ' not contains an URL address valid: ' . $datafield;
    }

}

?>