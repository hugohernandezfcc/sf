<?php

namespace doitcloudconsulting\polls\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use doitcloudconsulting\polls\Controllers\sfdc\SforcePartnerClient;
use doitcloudconsulting\polls\Controllers\sfdc\SObject;




class MainController2 extends Controller
{

    public function index(Request $request)
    {
    	$mySforceConnection = new SforcePartnerClient();
        $mySoapClient = $mySforceConnection->createConnection( $this->wsdl('partner.wsdl.xml') );
        $mylogin = $mySforceConnection->login('mayax@doitcloud.consulting', 'trayecta85IU2JyLDkiairgKI9G4Pap7a8');

        echo "<pre>";
        	print_r($mylogin);
        echo "</pre>";
        echo "<br/>===================<br/>";
        
        $query = 'SELECT Id,Name,BillingStreet,BillingCity,BillingState,Phone,Fax from Account limit 5';
	  	$response = $mySforceConnection->query(($query));

	  	print_r($response);
	  	echo "<br/>===================<br/>";

	  	foreach ($response->records as $record) {
	  		echo "<pre>";
	  			$registro = new SObject($record);
	    		print_r($registro);
	  		echo "</pre>";
	  	}
	  	echo "<br/>===================<br/>";
		echo "<pre>";
	  	print_r($mySforceConnection->getLastRequest());
    }

    public function wsdl($xml)
    {
    	$route = __DIR__ . '/' . $xml;
    	return str_replace('/Controllers/', '/', $route);
    }
}

?>