<?php

require_once 'messagetemplatetools.civix.php';
use CRM_Messagetemplatetools_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function messagetemplatetools_civicrm_config(&$config) {
  if (isset(Civi::$statics[__FUNCTION__])) { return; }
  Civi::$statics[__FUNCTION__] = 1;

  Civi::dispatcher()->addListener('civi.smarty.error', 'messagetemplatetools_civicrm_civiSmartyError');

  _messagetemplatetools_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function messagetemplatetools_civicrm_xmlMenu(&$files) {
  _messagetemplatetools_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function messagetemplatetools_civicrm_install() {
  _messagetemplatetools_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function messagetemplatetools_civicrm_postInstall() {
  _messagetemplatetools_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function messagetemplatetools_civicrm_uninstall() {
  _messagetemplatetools_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function messagetemplatetools_civicrm_enable() {
  _messagetemplatetools_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function messagetemplatetools_civicrm_disable() {
  _messagetemplatetools_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function messagetemplatetools_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _messagetemplatetools_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function messagetemplatetools_civicrm_managed(&$entities) {
  _messagetemplatetools_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function messagetemplatetools_civicrm_caseTypes(&$caseTypes) {
  _messagetemplatetools_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function messagetemplatetools_civicrm_angularModules(&$angularModules) {
  _messagetemplatetools_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function messagetemplatetools_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _messagetemplatetools_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function messagetemplatetools_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Admin_Page_MessageTemplates') {
    CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/previewButton.js');
  }
}

/**
 * @param \Civi\Core\Event\SmartyErrorEvent $event
 */
function messagetemplatetools_civicrm_civiSmartyError($event) {
  \Civi::log()->error('Smarty Error: ' . $event->errorMsg);
}

function messagetemplatetools_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  if ($objectName == 'Mailing' && ($op == 'view.mailing.browse.scheduled' || $op == 'view.mailing.browse.unscheduled')) {
    $links[] = [
      'name'  => ts('Live Preview'),
      'url'   => CRM_Utils_System::url('civicrm/mailings/live-preview', "mid=$objectId"),
      'title' => 'Live Preview',
    ];
  }
}

//set API permissions for various API calls (otherwise needs 'administer CiviCRM')
function messagetemplatetools_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  $permissions['mailingspreview']['getrecipients'] = array('access CiviCRM');
  $permissions['mailingspreview']['getmailing'] = array('access CiviCRM');
}
