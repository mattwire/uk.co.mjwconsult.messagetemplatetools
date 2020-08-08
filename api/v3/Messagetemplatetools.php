<?php

function civicrm_api3_messagetemplatetools_getrecipients($params) {
  $id = $params['mailing_id'];
  $recipients = CRM_Messagetemplatetools_Utils::getRecipients($id);
  return civicrm_api3_create_success( $recipients );
}

function civicrm_api3_messagetemplatetools_getmailing($params) {
  $mid = $params['mailing_id'];
  $cid = $params['contact_id'] ?? CRM_Core_Session::getLoggedInContactID();
  $html = CRM_Messagetemplatetools_Utils::getMailingHTML($mid, $cid);
  return civicrm_api3_create_success( $html );
}
