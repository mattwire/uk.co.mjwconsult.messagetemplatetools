<?php

use CRM_Messagetemplatetools_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Messagetemplatetools_Form_Preview extends CRM_Core_Form {

  protected $tokenElements = [];
  protected $templateParams = [];

  public function preProcess() {
    parent::preProcess(); // TODO: Change the autogenerated stub

    $this->id = CRM_Utils_Request::retrieve('id', 'Positive');
    $this->cid = CRM_Utils_Request::retrieve('cid', 'Positive');
    $this->eid = CRM_Utils_Request::retrieve('eid', 'Positive');
  }

  public function buildQuickForm() {

    // add form elements
    $elements[] = $this->add(
      'select', // field type
      'message_template', // field name
      'Message Template', // field label
      $this->getMessageTemplates(), // list of options
      TRUE // is required
    );

    $elements[] = $this->addEntityRef('contact_id', ts('Select Contact'));
    // Select events instead of contacts - set minimumInputLength to 0 to display results immediately without waiting for search input
    $elements[] = $this->addEntityRef('eventid', ts('Select Event'), [
      'entity' => 'event',
      'placeholder' => ts('- Select Event -'),
      'select' => ['minimumInputLength' => 0],
    ]);
    $elements[] = $this->addEntityRef('mailing_id', ts('Select Contact'), ['entity' => 'mailing']);

    if (!empty($this->id)) {
      $this->templateParams = [
        'messageTemplateID' => $this->id,
        'abortMailSend' => TRUE,
      ];
      // Get contactId
      if (!empty($this->cid)) {
        try {
          $contactDetails = civicrm_api3('Contact', 'getsingle', ['id' => $this->cid]);
        }
        catch (Exception $e) {
          $contactDetails = [];
        }
        $this->templateParams['tplParams']['contact'] = $contactDetails;
        $this->templateParams['tplParams']['email'] = $contactDetails['email'];
        $this->templateParams['tplParams']['phone'] = $contactDetails['phone'];
        $this->templateParams['tplParams']['street_address'] = $contactDetails['street_address'];
        $this->templateParams['contactId'] = $this->cid;
      }

      // Get event details
      if (!empty($this->eid)) {
        try {
          $eventDetails = civicrm_api3('Event', 'getsingle', ['id' => $this->eid]);
        }
        catch (Exception $e) {
          $eventDetails = [];
        }
        $this->templateParams['tplParams']['event'] = $eventDetails;
      }

      $tokens = self::getTokens($this->templateParams);

      $this->assign('tokens', $tokens);
      foreach ($tokens['smarty']['html'] as $token) {
        $this->tokenElements[] = $this->add('text', substr($token, 1), $token);
      }
      $this->assign('tokenElements', $this->getRenderableElementNames($this->tokenElements));

      $this->setDefaultValues();
      list($sent, $rendered['subject'], $rendered['text'], $rendered['html']) = CRM_Core_BAO_MessageTemplate::sendTemplate($this->templateParams);
      $rendered['text'] = self::renderText($rendered['text']);
      $this->assign('renderedMail', $rendered);
    }
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames($elements));
    CRM_Core_Session::singleton()->set('testvar', 'my name is bob');
    parent::buildQuickForm();
  }

  public static function getTokens($params) {
    $mailContent = civicrm_api3('MessageTemplate', 'getsingle', ['id' => $params['messageTemplateID']]);
    /*
     *'subject' => $dao->subject,
     *'text' => $dao->text,
     *'html' => $dao->html,
     *'format' => $dao->format,
     */
    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->subject = CRM_Utils_Array::value('msg_subject', $mailContent);
    $mailing->body_text = CRM_Utils_Array::value('msg_text', $mailContent);
    $mailing->body_html = CRM_Utils_Array::value('msg_html', $mailContent);
    $civiTokens = $mailing->getTokens();
    $tokens['tokens']['subject'] = CRM_Utils_Array::crmArrayUnique($civiTokens['subject']);
    $tokens['tokens']['text'] = CRM_Utils_Array::crmArrayUnique($civiTokens['text']);
    $tokens['tokens']['html'] = CRM_Utils_Array::crmArrayUnique($civiTokens['html']);
    $tokens['smarty']['subject'] = self::getSmartyTokens(CRM_Utils_Array::value('msg_subject', $mailContent));
    natcasesort($tokens['smarty']['subject']);
    $tokens['smarty']['text'] = self::getSmartyTokens(CRM_Utils_Array::value('msg_text', $mailContent));
    natcasesort($tokens['smarty']['text']);
    $tokens['smarty']['html'] = self::getSmartyTokens(CRM_Utils_Array::value('msg_html', $mailContent));
    natcasesort($tokens['smarty']['html']);
    return $tokens;
  }

  public static function renderText($text) {
    return nl2br($text);
  }

  public function setDefaultValues() {
    $defaults = json_decode(CRM_Core_Session::singleton()->get('smarty_defaults', 'messagetemplate'), TRUE);
    if (!empty($this->id)) {
      $defaults['message_template'] = $this->id;
    }
    if (!empty($this->cid)) {
      $defaults['contact_id'] = $this->cid;
    }
    if (!empty($this->eid)) {
      $defaults['eventid'] = $this->eid;
    }

    // Get values for smarty tokens from templateparams for defaults and vice versa
    if (isset($this->tokenElements)) {
      foreach ($this->tokenElements as $tokenElement) {
        $tokenNames = explode('.', $tokenElement->getName());
        $tokenValue = NULL;
        if (count($tokenNames) > 1) {
          if (isset($this->templateParams['tplParams'][$tokenNames[0]][$tokenNames[1]])) {
            $tokenValue = $this->templateParams['tplParams'][$tokenNames[0]][$tokenNames[1]];
          }
          // FIXME: We don't support user entered values for tokens in contact.name format yet.
        }
        else {
          if (isset($this->templateParams['tplParams'][$tokenNames[0]])) {
            $tokenValue = $this->templateParams['tplParams'][$tokenNames[0]];
          }
          elseif (isset($defaults[$tokenNames[0]])) {
            // If value wasn't set from the template params, set it from the defaults if specified there instead
            $this->templateParams['tplParams'][$tokenNames[0]] = $defaults[$tokenNames[0]];
          }
        }
        if (!empty($tokenValue)) {
          $defaults[$tokenElement->getName()] = $tokenValue;
        }
      }
    }

    if (!empty($defaults)) {
      CRM_Core_Session::singleton()->set('smarty_defaults', json_encode($defaults), 'messagetemplate');
    }

    return $defaults;
  }

  public function postProcess() {
    $values = $this->controller->exportValues();
    $submitValues = $this->_submitValues;
    $defaults = json_decode(CRM_Core_Session::singleton()->get('smarty_defaults', 'messagetemplate'), TRUE);
    $defaults = array_merge($defaults ?? [], $submitValues);
    unset($defaults['qfKey']);
    unset($defaults['entryURL']);
    unset($defaults['_qf_default']);
    unset($defaults['_qf_Preview_submit']);
    CRM_Core_Session::singleton()->set('smarty_defaults', json_encode($defaults), 'messagetemplate');

    if (isset($values['message_template'])) {
      $query = "id={$values['message_template']}";
      !empty($values['contact_id']) ? $query .= "&cid={$values['contact_id']}" : NULL;
      !empty($values['eventid']) ? $query .= "&eid={$values['eventid']}" : NULL;

      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/messageTemplates/preview', $query));
    }
  }

  public function getMessageTemplates() {
    // We need msg_subject to filter out undefined templates (eg. if using default workflow template)
    $result = civicrm_api3('MessageTemplate', 'get', [
      'return' => ["id", "msg_title", "msg_subject", "workflow_id", "is_default"],
      'options' => ['limit' => 0, 'sort' => 'msg_title ASC'],
    ]);
    foreach ($result['values'] as $id => $data) {
      if (CRM_Utils_Array::value('workflow_id', $data) && !$data['is_default']) {
        continue;
      }
      $msgTitle = !empty($data['msg_title']) ? $data['msg_title'] : '- no title -';
      if (!empty($data['workflow_id'])) {
        $msgTitle .= ' (workflow) ';
      }
      if ($data['is_default']) {
        $msgTitle .= ' (default) ';
      }
      $msgTitle .= " [id: {$id}]";
      $options[$id] = $msgTitle;
    }
    return $options;
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames($elements = []) {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = [];
    foreach ($elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Get array of string tokens.
   *
   * @param string $string
   *   The input string to parse for tokens.
   *
   * @return array
   *   array of tokens mentioned in field
   */
  public static function getSmartyTokens($string) {
    $matches = [];
    $tokens = [];
    preg_match_all('/(\$\w+[\.\w+]*)/',
      $string,
      $matches,
      PREG_PATTERN_ORDER
    );

    return array_unique($matches[0]);
  }

}
