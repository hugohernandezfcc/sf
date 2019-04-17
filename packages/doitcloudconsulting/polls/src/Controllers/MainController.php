<?php

namespace doitcloudconsulting\polls\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MainController extends Controller
{

    public function index(Request $request)
    {
    	$pruebita = new pruebita();

        return $pruebita->hgdm('Hugo Hernández Meneses');
    }
}

/**
 * de prueba
 */
class pruebita 
{
	
	public function hgdm($value)
	{
		return $value;
	}
}

?>