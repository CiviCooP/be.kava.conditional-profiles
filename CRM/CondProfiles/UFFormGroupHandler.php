<?php

/**
 * Class CRM_CondProfiles_UFFormGroupHandler
 * Handles the 'Profile Settings' form at civicrm/admin/uf/group/update.
 */
class CRM_CondProfiles_UFFormGroupHandler {

	/**
	 * Adds a group restriction field to the CRM_UF_Form_Group form.
	 * @param mixed $form Form
	 * @return mixed Form
	 */
	public static function buildForm(&$form) {

		/** @var $form CRM_UF_Form_Group */
		/** @var $element HTML_QuickForm_Element */

		$groupId = $form->get('id');
		if($groupId) {

			$form->add("select", "option_group_restriction", "option_group_restriction", self::fetchAllGroups());
			$element = $form->getElement('option_group_restriction');
			$element->setMultiple(true);
			$element->setLabel('Group Restrictions');

			$form->add("select", "option_group_restriction_pos", "option_group_restriction_pos", array(
				1 => 'IS',
				0 => 'IS NOT',
			));

			$defaults = self::fetchRestrictions($form->get('id'));
			$form->setDefaults($defaults);

		}

		return $form;
	}

	/**
	 * Saves group restrictions on post.
	 * @param mixed $form Form
	 * @return mixed Form
	 */
	public static function postProcess(&$form) {

		/** @var $form CRM_UF_Form_Group */

		$groupId = $form->get('id');
		if($groupId) {
			$group = $form->getElementValue('option_group_restriction');

			$pos = $form->getElementValue('option_group_restriction_pos');
			if(is_array($pos))
				$pos = $pos[0];

			self::deleteAllRestrictions($groupId);

			foreach($group as $g) {
				self::addRestriction($groupId, $g, $pos);
			}

		}

		return $form;
	}

	/**
	 * Fetches all groups
	 * @returns array An associative array with id => group
	 */
	private static function fetchAllGroups() {

		$groups = array(0 => "-- no restriction --");
		$result = civicrm_api3("group", "get", array("options" => array("sort" => "title", "limit" => 0)));

		foreach($result['values'] as $group) {
			$groups[$group['id']] = $group['title'];
		}

		return $groups;
	}

	/**
	 * Removes all group restrictions from civicrm_uf_group_conditional.
	 * @param int $uf_group_id UF Group Id
	 */
	private static function deleteAllRestrictions($uf_group_id) {
		if(!empty($uf_group_id)) {
			CRM_Core_DAO::executeQuery("DELETE FROM civicrm_uf_group_conditional WHERE uf_group_id = '" . (int)$uf_group_id . "'");
		}
	}

	/**
	 * Adds group restriction to civicrm_uf_group_conditional.
	 * @param int $uf_group_id UF Group ID
	 * @param int $civicrm_group_id CiviCRM Group ID
	 * @param int $positive Positive restriction (ie user 'is' or 'is not' a member of...)
	 */
	private static function addRestriction($uf_group_id, $civicrm_group_id, $positive) {
		if(!empty($uf_group_id) && !empty($civicrm_group_id)) {
			CRM_Core_DAO::executeQuery("INSERT INTO civicrm_uf_group_conditional (uf_group_id, group_id, positive) VALUES ('" . (int)$uf_group_id . "', '" . (int)$civicrm_group_id . "', '" . (int)$positive . "')");
		}
	}

	/**
	 * Fetch restrictions from civicrm_uf_group_conditional into an array for display on the form.
	 * @param int $uf_group_id UF Group ID
	 * @return array Array with two elements
	 */
	private static function fetchRestrictions($uf_group_id) {

		$q = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_uf_group_conditional WHERE uf_group_id = '" . (int)$uf_group_id . "'");

		$ret = array(
			'option_group_restriction_pos' => null,
			'option_group_restriction'     => array(),
		);

		while($q->fetch()) {
			$ret['option_group_restriction_pos'] = $q->positive;
			$ret['option_group_restriction'][] = $q->group_id;
		}

		return $ret;
	}

}