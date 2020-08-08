<?php

use CRM_Messagetemplatetools_ExtensionUtil as E;

class CRM_Messagetemplatetools_Page_Listoftokens extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('All available tokens'));

    $result = civicrm_api3('Mailing', 'gettokens', [
      'entity' => "Contact",
    ]);
    $result = $result['values'];

    $table = '';

    foreach ($result as $tokenCode => $name) {

      $options = [];

      if (strpos($tokenCode, 'custom_') !== FALSE) {
        $custom = str_replace('{', '', $tokenCode);
        $custom = str_replace('}', '', $tokenCode);
        $custom = explode('_', $custom);
        $custom = $custom[1];

        $result = civicrm_api3('CustomField', 'getsingle', [
          'sequential' => 1,
          'id' => $custom,
        ]);
        $optionGroup = $result['option_group_id'];

        if ($optionGroup) {
          $result = civicrm_api3('OptionGroup', 'get', [
            'sequential' => 1,
            'id' => $optionGroup,
            'api.OptionValue.get' => [],
          ]);

          $result = $result['values'][0]['api.OptionValue.get']['values'];
          if (count($result) > 1) {
            foreach ($result as $option) {
              $val = $option['value'];
              $label = $option['label'];
              $options[$val] = $label;
            }
            ksort($options);
          }
        }
      } else {
        continue;
      }

      $optStr = '';
      if (count($options) > 0) {
        foreach ($options as $val => $label) {
          $optStr .= "<tr><td>$val</td><td>$label</td></tr>";
        }
      }

      $table .= "<tr>";
      $table .= "<td>$tokenCode</td><td>$name</td>";
      if ($optStr) {
        $table .= "<td><table><tr><th>Value for Mosaico</th><th>Description</th><tbody>$optStr</tbody></table></td>";
      }
      else {
        $table .= "<td>None</td>";
      }
      $table .= "</tr>";
    }

    $this->assign('tokensTable', $table);

    parent::run();
  }

}
