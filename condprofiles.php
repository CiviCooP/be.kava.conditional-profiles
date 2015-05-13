<?php
// Require CIVIX file
require_once 'condprofiles.civix.php';

/**
 * Implements hook_civicrm_buildForm. Influences creation/display of a form.
 * @param string $formName Form Name
 * @param mixed $form Form Object
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function condprofiles_civicrm_buildForm($formName, &$form) {

    switch ($formName) {

        case 'CRM_Event_Form_Registration_Register':

            // Handle event registration form - remove fields as needed
            $form = CRM_CondProfiles_RegisterFormHandler::buildForm($form);
            break;

        case 'CRM_UF_Form_Group':

            // Handle profile settings form - adds option to restrict to groups
            $form = CRM_CondProfiles_UFFormGroupHandler::buildForm($form);
            break;
    }
}

/**
 * Implements hook_civicrm_postProcess. Process form after it's been submitted.
 * @param string $formName Form Name
 * @param mixed $form Form Object
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postProcess
 */
function condprofiles_civicrm_postProcess($formName, &$form) {

    switch($formName) {

        case 'CRM_UF_Form_Group':

            // Handle profile settings form - saves option to restrict to groups
            $form = CRM_CondProfiles_UFFormGroupHandler::postProcess($form);
            break;
    }
}

/**
 * Implements hook_civicrm_install.
 * Creates database table. (constraints wilden niet, later nog toevoegen)
 * Oh, en dit mag dus niet in een aparte klasse want dan is de namespace nog niet beschikbaar.
 * @return mixed Success
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function condprofiles_civicrm_install() {

    CRM_Core_DAO::executeQuery("CREATE TABLE IF NOT EXISTS `civicrm_uf_group_conditional` (
  			`uf_group_id` int(10) NOT NULL,
  			`group_id` int(10) NOT NULL,
  			`positive` int(1) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_uf_group_conditional`
  			ADD KEY `group_id` (`group_id`),
  			ADD KEY `uf_group_id` (`uf_group_id`)");
    return _condprofiles_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall. Removes database table.
 * @return mixed Success
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function condprofiles_civicrm_uninstall() {

    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `civicrm_uf_group_conditional`");
    return _condprofiles_civix_civicrm_uninstall();
}

/** Default Civix hooks follow **/

function condprofiles_civicrm_config(&$config) {
    _condprofiles_civix_civicrm_config($config);
}

function condprofiles_civicrm_xmlMenu(&$files) {
    _condprofiles_civix_civicrm_xmlMenu($files);
}

function condprofiles_civicrm_enable() {
    return _condprofiles_civix_civicrm_enable();
}

function condprofiles_civicrm_disable() {
    return _condprofiles_civix_civicrm_disable();
}

function condprofiles_civicrm_upgrade($op, CRM_Queue_Queue $queue = null) {
    return _condprofiles_civix_civicrm_upgrade($op, $queue);
}

function condprofiles_civicrm_managed(&$entities) {
    _condprofiles_civix_civicrm_managed($entities);
}

function condprofiles_civicrm_caseTypes(&$caseTypes) {
    _condprofiles_civix_civicrm_caseTypes($caseTypes);
}

function condprofiles_civicrm_alterSettingsFolders(&$metaDataFolders = null) {
    _condprofiles_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
