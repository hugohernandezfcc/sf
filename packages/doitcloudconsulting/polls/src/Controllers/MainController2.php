<?php

namespace doitcloudconsulting\polls\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use doitcloudconsulting\polls\Controllers\sfdc\SfController;




class MainController2 extends Controller
{

    public function index(Request $request)
    {
    	$toReturn = new SfController();
        return $toReturn->hugo('from controller :D');
    }

    
}
?>