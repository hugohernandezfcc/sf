<?php

namespace doitcloudconsulting\polls\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MainController2 extends Controller
{

    public function index(Request $request)
    {
        return 'from controller2';
    }
}
?>