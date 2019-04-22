<?php
namespace doitcloudconsulting\polls\Controllers\sfdc;

use doitcloudconsulting\polls\Controllers\sfdc\SforceEmail;

class MassEmailMessage extends SforceEmail {
  public function setTemplateId($templateId) {
    $this->templateId = $templateId;
  }

  public function setWhatIds($array) {
    $this->whatIds = $array;
  }

  public function setTargetObjectIds($array) {
    $this->targetObjectIds = $array;
  }
}

?>