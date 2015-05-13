<?php

/**
 * Class CRM_CondProfiles_RegisterFormHandler
 * Handles the event registration form at civicrm/event/register.
 */
class CRM_CondProfiles_RegisterFormHandler {

	/**
	 * Look up which profiles should be shown/removed and change the form accordingly.
	 * @param mixed $form Form
	 * @return mixed Form
	 */
	public static function buildForm(&$form) {

		$eventId = $form->get('id');
		$userId = CRM_Core_Session::singleton()->get('userID');

		// Get the profiles that should be removed and the fields that belong to these profiles
		$profilesToRemove = self::findProfilesToRemove($eventId, $userId);
		$fieldsToRemove = self::findFieldsToRemove($profilesToRemove);

		// echo '<pre>Removing profiles: ' . implode(',', $profilesToRemove) . '<br />Removing fields: ' . print_r($fieldsToRemove, true) . '</pre>';

		/** @var $form CRM_UF_Form_Group */
		// Get field names from the form for all elements - there may be a function for this but I couldn't find it
		$fieldNames = array();
		foreach ($form->_fields as $f) {
			$fieldNames[$f['field_id']] = $f['name'];
		}

		// Remove fields from the form
		// TODO doesn't seem to work completely, field is still passed to template (in $fields in UF/Form/Block.tpl)
		foreach ($fieldsToRemove as $field) {
			if (array_key_exists($field['id'], $fieldNames) && in_array($field['uf_group_id'], $profilesToRemove)) {
				$fieldName = $fieldNames[$field['id']];
				$form->removeElement($fieldName);
			}
		}

		return $form;
	}

	/**
	 * Decide what profiles should be removed from the form.
	 * @param int $eventId Event ID
	 * @param int $userId User ID
	 * @return array Array with IDs of profiles that should be removed
	 */
	private static function findProfilesToRemove($eventId, $userId) {

		// Find profiles
		$profiles = civicrm_api3('UFJoin', 'get', array(
			'entity_table' => 'civicrm_event',
			'entity_id'    => $eventId,
		));
		if($profiles['count'] == 0)
			return array();

		$profileIds = array();
		foreach($profiles['values'] as $profile)
			$profileIds[] = $profile['uf_group_id'];

		// Find restrictions that apply to these profiles, and group them by profile
		$restrict = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_uf_group_conditional WHERE uf_group_id IN (" . implode(',', $profileIds) . ")");
		$restrictionsByProfile = array();
		while($restrict->fetch()) {
			if(!array_key_exists($restrict->uf_group_id, $restrictionsByProfile)) {
				$restrictionsByProfile[$restrict->uf_group_id] = array(
					'uf_group_id' => $restrict->uf_group_id,
					'group_ids' => array(),
					'positive' => null,
				);
			}
			$restrictionsByProfile[$restrict->uf_group_id]['group_ids'][] = $restrict->group_id;
			$restrictionsByProfile[$restrict->uf_group_id]['positive'] = $restrict->positive;
		}

		// Find user groups
		$userGroups = self::findGroupsForUser($userId);

		// Walk restrictions and decide which profiles to remove
		$profilesToRemove = array();

		foreach($restrictionsByProfile as $restriction) {

			$isMember = false;
			foreach($userGroups as $userGroup) {
				if(in_array($userGroup, $restriction['group_ids']))
					$isMember = true;
			}

			if(($restriction['positive'] == 1 && $isMember == false) ||
			($restriction['positive'] == 0 && $isMember == true)) {
				$profilesToRemove[] = $restriction['uf_group_id'];
			}
		}

		// Return IDs of profiles that should be removed
		return $profilesToRemove;
	}

	/**
	 * Returns an array of fields that are part of the specified profiles
	 * @param array $profileIds Profile IDs
	 * @return array Array of fields
	 */
	private static function findFieldsToRemove($profileIds = array()) {

		if(count($profileIds) == 0)
			return array();

		$fields = civicrm_api3('UFField', 'get', array(
			'uf_group_id' => array('IN' => $profileIds),
		));
		return $fields['values'];
	}

	/**
	 * Finds all groups (including smart groups) a user belongs to
	 * Borrowed from pricesets_civicrm_fetch_user_groups().
	 * @param int $userId User ID
	 * @return array IDs of Groups a user belongs to
	 */
	private static function findGroupsForUser($userId) {

			// If user identifier is null, return no groups (otherwise all groups will be returned -KL)
			if($userId == null)
				return array();

			// Define return and temporarily arrays
			$_userGroups 		= array();
			$_regularGroups 	= array();
			$_smartGroups 		= array();
			// Fetch all regular groups
			$_fetchRegularGroups = civicrm_api3("GroupContact", "get", array("contact_id" => $userId));
			if (!$_fetchRegularGroups['is_error'] && !empty($_fetchRegularGroups['values'])) {
				foreach($_fetchRegularGroups['values'] as $_group) {
					// Push every group to the user groups array
					$_regularGroups[$_group['group_id']] = $_group['group_id'];
				}
			}
			// Fetch all smart groups
			$_fetchSmartGroups = civicrm_api3("Group","get", array("options" => array("sort" => "title", "limit" => 0)));
			// Check if the groups aint empty
			if(empty($_fetchSmartGroups['is_error']) && !empty($_fetchSmartGroups['values'])) {
				// Loop trough all the results
				foreach($_fetchSmartGroups['values'] as $_smartGroup) {
					// Check if current group is a smart group
					if($_smartGroup['saved_search_id'] > 0) {
						// Attempt to fetch contact with smart group id and user identifier
						$_attemptToFetchContact = civicrm_api3("Contact","get",array("contact_id" => $userId, "group" => $_smartGroup['id']));
						// Check if we did find the contact
						if(empty($_attemptToFetchContact['is_error']) && !empty($_attemptToFetchContact['values'])) {
							$_smartGroups[$_smartGroup['id']] = $_smartGroup['id'];
						}
					}
				}
			}
			// Merge the two arrays
			$_userGroups = $_regularGroups + $_smartGroups;
			// Return the user groups
			return $_userGroups;
	}
}