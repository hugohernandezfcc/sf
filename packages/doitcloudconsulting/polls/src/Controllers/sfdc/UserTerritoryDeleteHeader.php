<?php
namespace doitcloudconsulting\polls\Controllers\sfdc;

class UserTerritoryDeleteHeader {
	public $transferToUserId;

	public function __construct($transferToUserId) {
		$this->transferToUserId = $transferToUserId;
	}
}
?>