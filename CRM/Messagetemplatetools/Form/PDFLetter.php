<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * This class provides the functionality to create PDF letter for a single contact.
 */
class CRM_Messagetemplatetools_Form_PDFLetter extends CRM_Contact_Form_Task {

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;

  public $_single = NULL;

  public $_cid = NULL;

  public $_activityId = NULL;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {

    $this->skipOnHold = $this->skipDeceased = FALSE;
    $messageText = array();
    $messageSubject = array();
    $dao = new CRM_Core_BAO_MessageTemplate();
    $dao->is_active = 1;
    $dao->find();
    while ($dao->fetch()) {
      $messageText[$dao->id] = $dao->msg_text;
      $messageSubject[$dao->id] = $dao->msg_subject;
    }

    $this->assign('message', $messageText);
    $this->assign('messageSubject', $messageSubject);

    // store case id if present
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'Positive', $this, FALSE);

    // retrieve contact ID
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE);

    // retrieve participant ID
    $this->_participantId = CRM_Utils_Request::retrieve('pid', 'Positive', $this, FALSE);

    // retrieve message template workflow Id
    $this->_wfid = CRM_Utils_Request::retrieve('wfid', 'Positive', $this, FALSE);
    // If workflow id not specified, try by message template Id (user templates don't have workflow id)
    if (!empty($this->_wfid)) {
      $template = civicrm_api3('MessageTemplate', 'get', array('workflow_id' => $this->_wfid, 'return' => array('id', 'msg_subject')));
      $this->_mtid=$template['id'];
    }
    else {
      $this->_mtid = CRM_Utils_Request::retrieve('mtid', 'Positive', $this, FALSE);
    }
    try {
      $this->_msgTemplate = civicrm_api3('MessageTemplate', 'getsingle', array(
        'id' => $this->_mtid,
        'return' => array(
          'id',
          'msg_subject',
          'msg_title',
          'msg_html',
          'msg_text'
        )
      ));
    }
    catch (Exception $e) {
      CRM_Core_Error::statusBounce('Could not find a valid messagetemplate with id: ' . $this->_mtid);
    }

    $this->assign('title', $this->_msgTemplate['msg_title']);

    if ($cid) {
      $this->_contactIds = array($cid);
      // put contact display name in title for single contact mode
      CRM_Utils_System::setTitle(ts('Print Letter for %1', array(1 => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'display_name'))));
      $this->_single = TRUE;
      $this->_cid = $cid;
    }
    else {
      CRM_Core_Error::statusBounce('You must specify contact Id to print a letter');
    }
    $this->assign('single', $this->_single);
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = CRM_Core_BAO_PdfFormat::getDefaultValues();
    $defaults['format_id'] = $defaults['id'];
    $defaults['template_id'] = $this->_mtid;
    $defaults['participant_id'] = $this->_participantId;
    $defaults['html_message'] = $this->_msgTemplate['msg_html'];
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);
    // This form outputs a file so should never be submitted via ajax
    $this->preventAjaxSubmit();

    //Added for CRM-12682: Add activity subject and campaign fields
    //CRM_Campaign_BAO_Campaign::addCampaign($this);
    $this->add(
      'text',
      'subject',
      ts('Activity Subject'),
      array('size' => 45, 'maxlength' => 255),
      FALSE
    );

    $this->add('static', 'pdf_format_header', NULL, ts('Page Format: %1', array(1 => '<span class="pdf-format-header-label"></span>')));
    $this->addSelect('format_id', array(
      'label' => ts('Select Format'),
      'placeholder' => ts('Default'),
      'entity' => 'message_template',
      'field' => 'pdf_format_id',
      'option_url' => 'civicrm/admin/pdfFormats',
    ));
    $this->add(
      'select',
      'paper_size',
      ts('Paper Size'),
      array(0 => ts('- default -')) + CRM_Core_BAO_PaperSize::getList(TRUE),
      FALSE,
      array('onChange' => "selectPaper( this.value ); showUpdateFormatChkBox();")
    );
    $this->add('static', 'paper_dimensions', NULL, ts('Width x Height'));
    $this->add(
      'select',
      'orientation',
      ts('Orientation'),
      CRM_Core_BAO_PdfFormat::getPageOrientations(),
      FALSE,
      array('onChange' => "updatePaperDimensions(); showUpdateFormatChkBox();")
    );
    $this->add(
      'select',
      'metric',
      ts('Unit of Measure'),
      CRM_Core_BAO_PdfFormat::getUnits(),
      FALSE,
      array('onChange' => "selectMetric( this.value );")
    );
    $this->add(
      'text',
      'margin_left',
      ts('Left Margin'),
      array('size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"),
      TRUE
    );
    $this->add(
      'text',
      'margin_right',
      ts('Right Margin'),
      array('size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"),
      TRUE
    );
    $this->add(
      'text',
      'margin_top',
      ts('Top Margin'),
      array('size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"),
      TRUE
    );
    $this->add(
      'text',
      'margin_bottom',
      ts('Bottom Margin'),
      array('size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"),
      TRUE
    );

    $this->add('checkbox', 'bind_format', ts('Always use this Page Format with the selected Template'));
    $this->add('checkbox', 'update_format', ts('Update Page Format (this will affect all templates that use this format)'));

    $this->assign('useThisPageFormat', ts('Always use this Page Format with the new template?'));
    $this->assign('useSelectedPageFormat', ts('Should the new template always use the selected Page Format?'));
    $this->assign('totalSelectedContacts', count($this->_contactIds));

    $this->add('select', 'document_type', ts('Document Type'), CRM_Core_SelectValues::documentFormat());

    $documentTypes = implode(',', CRM_Core_SelectValues::documentApplicationType());
    $this->addElement('file', "document_file", 'Upload Document', 'size=30 maxlength=255 accept="' . $documentTypes . '"');
    $this->addUploadElement("document_file");

    $this->add('hidden', 'template_id');
    $this->add('hidden', 'html_message');
    $this->add('hidden', 'participant_id');

    $buttons = array();
    if ($this->get('action') != CRM_Core_Action::VIEW) {
      $buttons[] = array(
        'type' => 'upload',
        'name' => ts('Download Document'),
        'isDefault' => TRUE,
        'icon' => 'fa-download',
      );
      $buttons[] = array(
        'type' => 'submit',
        'name' => ts('Preview'),
        'subName' => 'preview',
        'icon' => 'fa-search',
        'isDefault' => FALSE,
      );
    }
    $buttons[] = array(
      'type' => 'cancel',
      'name' => $this->get('action') == CRM_Core_Action::VIEW ? ts('Done') : ts('Cancel'),
    );
    $this->addButtons($buttons);

    $this->addFormRule(array('CRM_Contact_Form_Task_PDFLetterCommon', 'formRule'), $this);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Contact_Form_Task_PDFLetterCommon::postProcess($this);
  }

  /**
   * Form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   * @param array $self
   *   Additional values form 'this'.
   *
   * @return bool
   *   TRUE if no errors, else array of errors.
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();

    if (!is_numeric($fields['margin_left'])) {
      $errors['margin_left'] = 'Margin must be numeric';
    }
    if (!is_numeric($fields['margin_right'])) {
      $errors['margin_right'] = 'Margin must be numeric';
    }
    if (!is_numeric($fields['margin_top'])) {
      $errors['margin_top'] = 'Margin must be numeric';
    }
    if (!is_numeric($fields['margin_bottom'])) {
      $errors['margin_bottom'] = 'Margin must be numeric';
    }
    return empty($errors) ? TRUE : $errors;
  }
}
