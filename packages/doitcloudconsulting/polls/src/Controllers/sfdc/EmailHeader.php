<?php
namespace doitcloudconsulting\polls\Controllers\sfdc;

class EmailHeader {
	public $triggerAutoResponseEmail;
	public $triggerOtherEmail;
	public $triggerUserEmail;

	public function __construct($triggerAutoResponseEmail = false, $triggerOtherEmail = false, $triggerUserEmail = false) {
		$this->triggerAutoResponseEmail = $triggerAutoResponseEmail;
		$this->triggerOtherEmail = $triggerOtherEmail;
		$this->triggerUserEmail = $triggerUserEmail;
	}
}
?>