<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use doitcloudconsulting\polls\Controllers\Salesforce;


class SalesforceUsePackage extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $init = new Salesforce();
        echo "<pre>";
        print_r($init->loginInformation());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $init = new Salesforce();
        $resultado = $init->insert(array(
            'Name' => 'Cuenta Generada desde Un paquete open source'
        ), 'Account');



        echo "<pre> Created:\n";
        print_r($resultado);


        $deleted = $init->delete(array($resultado[0]->id));

        print_r($deleted);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
