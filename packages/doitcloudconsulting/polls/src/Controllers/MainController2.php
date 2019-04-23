<?php

namespace doitcloudconsulting\polls\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use doitcloudconsulting\polls\Controllers\sfdc\SforcePartnerClient;




class MainController2 extends Controller
{

    public function index(Request $request)
    {
    	$mySforceConnection = new SforcePartnerClient();
        $mySoapClient = $mySforceConnection->createConnection('/Users/hugohernandez/Desktop/heroku_projects/polls/packages/doitcloudconsulting/polls/src/partner.wsdl.xml');
        $mylogin = $mySforceConnection->login('mayax@doitcloud.consulting', 'trayecta85IU2JyLDkiairgKI9G4Pap7a8');

        echo "<pre>";
        	print_r($mylogin);
        echo "</pre>";
    }

}
?>