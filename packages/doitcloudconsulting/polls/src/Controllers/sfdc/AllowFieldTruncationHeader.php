<?php
namespace doitcloudconsulting\polls\Controllers\sfdc;

class AllowFieldTruncationHeader {
    public $allowFieldTruncation;
    
    public function __construct($allowFieldTruncation) {
        $this->allowFieldTruncation = $allowFieldTruncation;
    }
}

?>