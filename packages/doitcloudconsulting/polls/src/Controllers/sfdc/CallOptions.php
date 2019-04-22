<?php
namespace doitcloudconsulting\polls\Controllers\sfdc;

/**
 * This file contains three classes.
 * @package SalesforceSoapClient
 */

class CallOptions {
	public $client;
	public $defaultNamespace;

	public function __construct($client, $defaultNamespace=NULL) {
		$this->client = $client;
		$this->defaultNamespace = $defaultNamespace;
	}
}
?>