<?php
use CRM_Messagetemplatetools_ExtensionUtil as E;

class CRM_Messagetemplatetools_Page_PreviewMailing extends CRM_Core_Page {

  public $_loggedInUser;
  public $_mID;

  public function run() {
    CRM_Utils_System::setTitle(E::ts('Mailings Live Preview'));

    $this->_loggedInUser = CRM_Core_Session::singleton()->getLoggedInContactID();
    $this->_mID = CRM_Utils_Request::retrieve('mid', 'Positive');

    $result = civicrm_api3('Mailing', 'getsingle', [
      'return' => ['name'],
      'id' => $this->_mID,
    ]);

    $this->assign('mid', $this->_mID);
    $this->assign('mailingTitle', $result['name'] ?? 'No title');

    CRM_Core_Resources::singleton()->addVars('messagetemplatetools', array('mid' => $this->_mID));

    parent::run();
  }

}
