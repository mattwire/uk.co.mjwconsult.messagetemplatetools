<?php

class CRM_Messagetemplatetools_Utils {

  public static function getRecipients($mailingID) {

    if (!$mailingID) {
      return FALSE;
    }

    try {
      $recipientsCount = civicrm_api3('MailingRecipients', 'getcount', [
        'mailing_id'        => $mailingID,
        'check_permissions' => 0,
      ]);

      if ($recipientsCount == 0) {
        return FALSE;
      }

      $params = [
        'sequential'        => 1,
        'return'            => ["contact_id"],
        'mailing_id'        => $mailingID,
        'options'           => ['sort' => "contact_id", 'limit' => 0],
        'check_permissions' => 0,
      ];

      $recipients = civicrm_api3('MailingRecipients', 'get', $params);
    } catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }

    $recs = [];
    foreach ($recipients['values'] as $rec) {
      $recs[] = $rec['contact_id'];
    }

    if ($recipientsCount > 5000) {
      shuffle($recs);
      $recs = array_slice($recs, 0, 5000);
      sort($recs);
    }

    $recs = implode(",", $recs);

    $sql = "select cc.id as id, contact_type, first_name, last_name, organization_name, cvlc.life_cycle_status_406 as status, cvpa.regional_authority_307 as region from civicrm_contact cc " .
           "left join civicrm_value_life_cycle_15 cvlc on cvlc.entity_id = cc.id " .
           "left join civicrm_value_political_areas_36 cvpa on cvpa.entity_id = cc.id " .
           "where cc.id in ($recs)";

    $dao = CRM_Core_DAO::executeQuery($sql);

    $contacts = [];

    while ($dao->fetch()) {
      if ($dao->contact_type == 'Individual') {
        $contacts[$dao->id] = ['status' => $dao->status, 'region' => $dao->region, 'name' => $dao->first_name . ' ' . $dao->last_name];
      }

      else {
        $contacts[$dao->id] = ['status' => $dao->status, 'region' => $dao->region, 'name' => $dao->organization_name];
      }
    }

    $recToReturn = [];
    $lc          = [1 => 'Donor', 2 => 'Supporter', 3 => 'Member'];
    foreach ($contacts as $contactID => $details) {
      $name = $details['name'] . "</br><span style='font-size:0.7rem;'><em>" . $lc[$details['status']];
      if ($details['region']) {
        $name .= ", " . $details ['region'];
      }
      $name                    .= "</em></span>";
      $recToReturn[$contactID] = $name;
    }

    return $recToReturn;

  }

  public static function getMailingHTML($mailingID, $contactID) {
    try {
      $result = civicrm_api3('Mailing', 'preview', [
        'id'                => $mailingID,
        'contact_id'        => $contactID,
        'check_permissions' => 0,
      ]);
    } catch (\Exception $e) {
      return 'error';
    }

    $html = $result['values']['body_html'];

    return $html;
  }

  //no longer used
  public static function smartyTokensCheck($string, $tokens) {

    $matches = [];

    preg_match_all('/(?<!\{|\\\\)\{[^}]*\$(\w+\.\w+)[^}]+\}(?!\})/',
      $string,
      $matches,
      PREG_PATTERN_ORDER
    );

    if ($matches[1]) {
      foreach ($matches[1] as $token) {
        list($type, $name) = preg_split('/\./', $token, 2);
        if ($name && $type) {
          if (!isset($tokens[$type])) {
            $tokens[$type] = [];
          }
          $tokens[$type][] = $name;
        }
      }
    }

    return $tokens;

  }

}
