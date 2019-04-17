<?php

namespace doitcloudconsulting\polls\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProxySettings extends Controller
{

    public function index(Request $request)
    {
    	return 'email';
    }
}

class ProxySettings {
  public $host;
  public $port;
  public $login;
  public $password;
}
?>