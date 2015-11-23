<?php

/**
 * Model for custom object fields.
 */
class ThemeHouse_ObjectFields_Model_ObjectField extends XenForo_Model
{
	const FETCH_OBJECT_CLASS_FIELD = 0x01;
	const FETCH_FIELD_GROUP = 0x02;
	const FETCH_ADDON = 0x04;
	const FETCH_OBJECT_FIELD_VALUE = 0x08;

	/**
	 * Gets a custom object field by ID.
	 *
	 * @param string $fieldId
	 *
	 * @return array|false
	 */
	public function getObjectFieldInAddOnById($fieldId, $addOnId)
	{
		if (!$fieldId)
		{
			return array();
		}

		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_object_field
			WHERE field_id = ? AND addon_id = ?
		', array($fieldId, $addOnId));
	}

	/**
	 * Gets custom object fields that match the specified criteria.
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array [field id] => info
	 */
	public function getObjectFields(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareObjectFieldConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareObjectFieldOrderOptions($fetchOptions, 'field.materialized_order');
		$joinOptions = $this->prepareObjectFieldFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchAll($this->limitQueryResults('
			SELECT field.*
			' . $joinOptions['selectFields'] . '
			FROM xf_object_field AS field
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereConditions . '
			' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		));
	}

	/**
	 * Gets custom object fields that match the specified criteria.
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array [field id] => info
	 */
	public function getObjectFieldsInAddOn($addOnId, array $conditions = array(), array $fetchOptions = array())
	{
		$conditions['addon_id'] = $addOnId;

		$fields = $this->getObjectFields($conditions, $fetchOptions);

		$objectFields = array();
		foreach ($fields as $field)
		{
			$objectFields[$field['field_id']] = $field;
		}

		return $objectFields;
	}

	/**
	 * Prepares a set of conditions to select fields against.
	 *
	 * @param array $conditions List of conditions.
	 * @param array $fetchOptions The fetch options that have been provided. May be edited if criteria requires.
	 *
	 * @return string Criteria as SQL for where clause
	 */
	public function prepareObjectFieldConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		if (isset($conditions['field_ids']))
		{
			$sqlConditions[] = 'field.field_id IN(' . $db->quote($conditions['field_ids']) . ')';
		}

		if (!empty($conditions['field_group_id']))
		{
			$sqlConditions[] = 'field.field_group_id = ' . $db->quote($conditions['field_group_id']);
		}

		if (!empty($conditions['field_choices_class_id']))
		{
			$sqlConditions[] = 'field.field_choices_class_id = ' . $db->quote($conditions['field_choices_class_id']);
		}

		if (isset($conditions['addon_id']))
		{
			$sqlConditions[] = 'field.addon_id = ' . $db->quote($conditions['addon_id']);
		}

		if (!empty($conditions['active']))
		{
			$sqlConditions[] = 'addon.active = 1 OR field.addon_id = \'\'';
			$this->addFetchOptionJoin($fetchOptions, self::FETCH_ADDON);
		}

		if (!empty($conditions['adminQuickSearch']))
		{
			$searchStringSql = 'field.field_id LIKE ' . XenForo_Db::quoteLike($conditions['adminQuickSearch']['searchText'], 'lr');

			if (!empty($conditions['adminQuickSearch']['phraseMatches']))
			{
				$sqlConditions[] = '(' . $searchStringSql . ' OR field.field_id IN (' . $db->quote($conditions['adminQuickSearch']['phraseMatches']) . '))';
			}
			else
			{
				$sqlConditions[] = $searchStringSql;
			}
		}

		if (isset($conditions['object_class_id']))
		{
			if (is_array($conditions['object_class_id']))
			{
				$sqlConditions[] = 'ocf.object_class_id IN(' . $db->quote($conditions['object_class_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'ocf.object_class_id = ' . $db->quote($conditions['object_class_id']);
			}
			$this->addFetchOptionJoin($fetchOptions, self::FETCH_OBJECT_CLASS_FIELD);
		}

		if (isset($conditions['object_class_ids']))
		{
			$sqlConditions[] = 'ocf.object_class_id IN(' . $db->quote($conditions['object_class_ids']) . ')';
			$this->addFetchOptionJoin($fetchOptions, self::FETCH_OBJECT_CLASS_FIELD);
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	/**
	 * Prepares join-related fetch options.
	 *
	 * @param array $fetchOptions
	 *
	 * @return array Containing 'selectFields' and 'joinTables' keys.
	 */
	public function prepareObjectFieldFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		$db = $this->_getDb();

		if (!empty($fetchOptions['valueObjectId']))
		{
			$selectFields .= ',
				field_value.field_value';
			$joinTables .= '
				LEFT JOIN xf_object_field_value AS field_value ON
				(field_value.field_id = field.field_id AND field_value.object_id = ' . $db->quote($fetchOptions['valueObjectId']) . ')';
		}

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_OBJECT_CLASS_FIELD)
			{
				$selectFields .= ',
					ocf.field_id, ocf.object_class_id';
				$joinTables .= '
					INNER JOIN xf_object_class_field AS ocf ON
					(ocf.field_id = field.field_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_FIELD_GROUP)
			{
				$selectFields .= ',
					field_group.display_order AS group_display_order';
				$joinTables .= '
					LEFT JOIN xf_object_field_group AS field_group ON
					(field_group.field_group_id = field.field_group_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_ADDON)
			{
				$selectFields .= ',
					addon.title AS addon_title, addon.active';
				$joinTables .= '
					LEFT JOIN xf_addon AS addon ON
					(field.addon_id = addon.addon_id)';
			}
		}

		return array(
				'selectFields' => $selectFields,
				'joinTables'   => $joinTables
		);
	}

	/**
	 * Construct 'ORDER BY' clause
	 *
	 * @param array $fetchOptions (uses 'order' key)
	 * @param string $defaultOrderSql Default order SQL
	 *
	 * @return string
	 */
	public function prepareObjectFieldOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
				'materialized_order' => 'field.materialized_order',
				'canonical_order' => 'field_group.display_order, field.display_order',
		);

		if (!empty($fetchOptions['order']) && $fetchOptions['order'] == 'canonical_order')
		{
			$this->addFetchOptionJoin($fetchOptions, self::FETCH_FIELD_GROUP);
		}

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

	/**
	 * Fetches custom object fields grouped by add-on
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 * @param integer $fieldCount Reference: counts the total number of fields
	 *
	 * @return [add-on ID => [title, fields => field]]
	 */
	public function getObjectFieldsByAddOns(array $conditions = array(), array $fetchOptions = array(), &$fieldCount = 0)
	{
		$this->addFetchOptionJoin($fetchOptions, self::FETCH_ADDON);

		$conditions['active'] = true;

		$fields = $this->getObjectFields($conditions, $fetchOptions);

		$addOns = array();
		foreach ($fields AS $field)
		{
			$addOns[$field['addon_id']][$field['field_id']] = $this->prepareObjectField($field);
		}

		$fieldCount = count($fields);

		return $addOns;
	}

	/**
	 * Fetches all custom object fields available in the specified classes
	 *
	 * @param integer|array $classIds
	 *
	 * @return array
	 */
	public function getObjectFieldsInClasses($objectClassId)
	{
		return $this->getObjectFields(is_array($objectClassId)
				? array('object_class_ids' => $objectClassId)
				: array('object_class_id' => $objectClassId)
		);
	}

	/**
	 * Fetches all custom object fields available in the specified classes
	 *
	 * @param integer $classId
	 *
	 * @return array
	 */
	public function getObjectFieldsInClass($objectClassId, $addOnId)
	{
		$output = array();
		foreach ($this->getObjectFields(array('object_class_id' => $objectClassId, 'addon_id' => $addOnId)) AS $field)
		{
			$output[$field['field_id']] = $field;
		}

		return $output;
	}

	/**
	 * Fetches all object fields usable by the visiting user in the specified class(s)
	 *
	 * @param integer|array $classIds
	 * @param array|null $viewingUser
	 *
	 * @return array
	 */
	public function getUsableObjectFieldsInClasses($classIds, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$fields = $this->getObjectFieldsInClasses($classIds);

		$fieldGroups = array();
		foreach ($fields AS $field)
		{
			if ($this->_verifyObjectFieldIsUsableInternal($field, $viewingUser))
			{
				$fieldId = $field['field_id'];
				$fieldGroupId = $field['field_group_id'];

				if (!isset($fieldGroups[$fieldGroupId]))
				{
					$fieldGroups[$fieldGroupId] = array();

					if ($fieldGroupId)
					{
						$fieldGroups[$fieldGroupId]['title'] = new XenForo_Phrase(
							$this->getObjectFieldGroupTitlePhraseName($fieldGroupId));
					}

				}

				$fieldGroups[$fieldGroupId]['fields'][$fieldId] = $field;
			}
		}

		return $fieldGroups;
	}

	public function getObjectFieldIfInClass($fieldId, $objectClassId)
	{
		return $this->_getDb()->fetchRow('
				SELECT field.*
				FROM xf_object_field AS field
				INNER JOIN xf_object_class_field AS ocf ON (ocf.field_id = field.field_id AND ocf.object_class_id = ?)
				WHERE field.field_id = ?
				', array($objectClassId, $fieldId));
	}

	public function getClassAssociationsByObjectField($fieldId, $addOnId, $fetchAll = false)
	{
		$query = '
			SELECT object_class.class_id
			'. ($fetchAll ? ', object_class.*' : '') . '
			FROM xf_object_class_field AS ocf
			LEFT JOIN xf_object_class AS object_class ON (ocf.object_class_id = object_class.object_class_id)
			WHERE ocf.field_id = '.$this->_getDb()->quote($fieldId).'
				AND object_class.addon_id = '.$this->_getDb()->quote($addOnId).'
		';

		return ($fetchAll ? $this->fetchAllKeyed($query, 'object_class_id') : $this->_getDb()->fetchCol($query));
	}

	/**
	 * Groups object fields by their field group.
	 *
	 * @param array $fields
	 *
	 * @return array [field group id][key] => info
	 */
	public function groupObjectFields(array $fields)
	{
		$return = array();

		foreach ($fields AS $fieldId => $field)
		{
			$return[$field['field_group_id']][$fieldId] = $field;
		}

		return $return;
	}

	/**
	 * Prepares a object field for display.
	 *
	 * @param array $field
	 * @param boolean $getFieldChoices If true, gets the choice options for this field (as phrases)
	 * @param mixed $fieldValue If not null, the value for the field; if null, pulled from field_value
	 * @param boolean $valueSaved If true, considers the value passed to be saved; should be false on registration
	 *
	 * @return array Prepared field
	 */
	public function prepareObjectField(array $field, $getFieldChoices = false, $fieldValue = null, $valueSaved = true, $required = false)
	{
		$field['isMultiChoice'] = ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect');

		if ($fieldValue === null && isset($field['field_value']))
		{
			$fieldValue = $field['field_value'];
		}
		if ($field['isMultiChoice'])
		{
			if (is_string($fieldValue))
			{
				$fieldValue = @unserialize($fieldValue);
			}
			else if (!is_array($fieldValue))
			{
				$fieldValue = array();
			}
		}
		$field['field_value'] = $fieldValue;

		$field['title'] = new XenForo_Phrase($this->getObjectFieldTitlePhraseName($field['field_id']));
		$field['description'] = new XenForo_Phrase($this->getObjectFieldDescriptionPhraseName($field['field_id']));

		$field['hasValue'] = $valueSaved && ((is_string($fieldValue) && $fieldValue !== '') || (!is_string($fieldValue) && $fieldValue));

		if ($getFieldChoices)
		{
			if ((isset($field['field_choices_callback_class']) && $field['field_choices_callback_class'])
				&& (isset($field['field_choices_callback_method']) && $field['field_choices_callback_method']))
			{
				$field['fieldChoices'] = call_user_func(array($field['field_choices_callback_class'], $field['field_choices_callback_method']));
			}
			else
			{
				$field['fieldChoices'] = $this->getObjectFieldChoices($field['field_id'], $field['field_choices']);
			}
		}

		$field['isEditable'] = true;

		$field['required'] = $required;

		return $field;
	}

	/**
	 * Prepares a list of object fields for display.
	 *
	 * @param array $fields
	 * @param boolean $getFieldChoices If true, gets the choice options for these fields (as phrases)
	 * @param array $fieldValues List of values for the specified fields; if skipped, pulled from field_value in array
	 * @param boolean $valueSaved If true, considers the value passed to be saved; should be false on registration
	 *
	 * @return array
	 */
	public function prepareObjectFields(array $fields, $getFieldChoices = false, array $fieldValues = array(), $valueSaved = true, array $classRequiredFields = array())
	{
		foreach ($fields AS &$field)
		{
			$value = isset($fieldValues[$field['field_id']]) ? $fieldValues[$field['field_id']] : null;
			$required = in_array($field['field_id'], $classRequiredFields);
			$field = $this->prepareObjectField($field, $getFieldChoices, $value, $valueSaved, $required);
		}

		return $fields;
	}

	/**
	 * Prepares a list of grouped object fields for display.
	 *
	 * @param array $fieldGroups
	 * @param boolean $getFieldChoices If true, gets the choice options for these fields (as phrases)
	 * @param array $fieldValues List of values for the specified fields; if skipped, pulled from field_value in array
	 * @param boolean $valueSaved If true, considers the value passed to be saved; should be false on registration
	 *
	 * @return array
	 */
	public function prepareGroupedObjectFields(array $fieldGroups, $getFieldChoices = false, array $fieldValues = array(), $valueSaved = true, array $classRequiredFields = array())
	{
		foreach ($fieldGroups AS &$fieldGroup)
		{
			$fieldGroup['fields'] = $this->prepareObjectFields($fieldGroup['fields'], $getFieldChoices, $fieldValues, $valueSaved, $classRequiredFields);
		}

		return $fieldGroups;
	}

	public function getObjectFieldTitlePhraseName($fieldId)
	{
		return 'object_field_' . $fieldId;
	}

	/**
	 * Gets the field choices for the given field.
	 *
	 * @param string $fieldId
	 * @param string|array $choices Serialized string or array of choices; key is choide ID
	 * @param boolean $master If true, gets the master phrase values; otherwise, phrases
	 *
	 * @return array Choices
	 */
	public function getObjectFieldChoices($fieldId, $choices, $master = false)
	{
		if (!is_array($choices))
		{
			$choices = ($choices ? @unserialize($choices) : array());
		}

		if (!$master)
		{
			foreach ($choices AS $value => &$text)
			{
				$text = new XenForo_Phrase($this->getObjectFieldChoicePhraseName($fieldId, $value));
			}
		}

		return $choices;
	}

	/**
	 * Verifies that the value for the specified field is valid.
	 *
	 * @param array $field
	 * @param mixed $value
	 * @param mixed $error Returned error message
	 *
	 * @return boolean
	 */
	public function verifyObjectFieldValue(array $field, &$value, &$error = '')
	{
		if (($field['field_type'] == 'radio' || $field['field_type'] == 'select' || $field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect')
			&& (isset($field['field_choices_callback_class']) && $field['field_choices_callback_class'])
			&& (isset($field['field_choices_callback_method']) && $field['field_choices_callback_method']))
		{
			$field['field_choices'] = serialize(call_user_func(array($field['field_choices_callback_class'], $field['field_choices_callback_method'])));
		}
		$error = false;

		switch ($field['field_type'])
		{
			case 'textbox':
				$value = preg_replace('/\r?\n/', ' ', strval($value));
				// break missing intentionally

			case 'textarea':
				$value = trim(strval($value));

				if ($field['max_length'] && utf8_strlen($value) > $field['max_length'])
				{
					$error = new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', array('count' => $field['max_length']));
					return false;
				}

				$matched = true;

				if ($value !== '')
				{
					switch ($field['match_type'])
					{
						case 'number':
							$matched = preg_match('/^[0-9]+(\.[0-9]+)?$/', $value);
							break;

						case 'alphanumeric':
							$matched = preg_match('/^[a-z0-9_]+$/i', $value);
							break;

						case 'email':
							$matched = Zend_Validate::is($value, 'EmailAddress');
							break;

						case 'url':
							if ($value === 'http://')
							{
								$value = '';
								break;
							}
							if (substr(strtolower($value), 0, 4) == 'www.')
							{
								$value = 'http://' . $value;
							}
							$matched = Zend_Uri::check($value);
							break;

						case 'regex':
							$matched = preg_match('#' . str_replace('#', '\#', $field['match_regex']) . '#sU', $value);
							break;

						case 'callback':
							$matched = call_user_func_array(
								array($field['match_callback_class'], $field['match_callback_method']),
								array($field, &$value, &$error)
							);

						default:
							// no matching
					}
				}

				if (!$matched)
				{
					if (!$error)
					{
						$error = new XenForo_Phrase('please_enter_value_that_matches_required_format');
					}
					return false;
				}
				break;

			case 'radio':
			case 'select':
				$choices = unserialize($field['field_choices']);
				$value = strval($value);

				if (!isset($choices[$value]))
				{
					$value = '';
				}
				break;

			case 'checkbox':
			case 'multiselect':
				$choices = unserialize($field['field_choices']);
				if (!is_array($value))
				{
					$value = array();
				}

				$newValue = array();

				foreach ($value AS $key => $choice)
				{
					$choice = strval($choice);
					if (isset($choices[$choice]))
					{
						$newValue[$choice] = $choice;
					}
				}

				$value = $newValue;
				break;
		}

		return true;
	}

	public function updateObjectFieldClassAssociationByObjectField($fieldId, $addOnId, array $objectClassIds)
	{
		$emptyClassKey = array_search("0", $objectClassIds, true);
		if ($emptyClassKey !== false)
		{
			unset($objectClassIds[$emptyClassKey]);
		}

		$objectClassIds = array_unique($objectClassIds);

		$existingObjectClassIds = array_keys($this->getClassAssociationsByObjectField($fieldId, $addOnId, true));
		if (!$objectClassIds && !$existingObjectClassIds)
		{
			return; // nothing to do
		}

		$db = $this->_getDb();
		XenForo_Db::beginTransaction($db);

		if (!empty($existingObjectClassIds))
		{
			$db->delete(
				'xf_object_class_field',
				'field_id = ' . $db->quote($fieldId) . ' AND object_class_id IN (' . $db->quote($existingObjectClassIds) . ')'
			);
		}

		foreach ($objectClassIds AS $objectClassId)
		{
			$db->insert('xf_object_class_field', array(
				'object_class_id' => $objectClassId,
				'field_id' => $fieldId,
				'field_value' => ''
			));
		}

		$rebuildObjectClassIds = array_unique(array_merge($objectClassIds, $existingObjectClassIds));
		$this->rebuildObjectFieldClassAssociationCache($rebuildObjectClassIds);

		XenForo_Db::commit($db);
	}

	public function updateObjectFieldClassAssociationByClass($objectClassId, array $fieldIds)
	{
		$emptyFieldKey = array_search("0", $fieldIds, true);
		if ($emptyFieldKey !== false)
		{
			unset($fieldIds[$emptyFieldKey]);
		}

		$fieldIds = array_unique($fieldIds);

		$db = $this->_getDb();

		XenForo_Db::beginTransaction($db);

		$db->delete('xf_object_class_field', 'object_class_id = ' . $db->quote($objectClassId));

		foreach ($fieldIds AS $fieldId)
		{
			$db->insert('xf_object_class_field', array(
				'object_class_id' => $objectClassId,
				'field_id' => $fieldId,
				'field_value' => ''
			));
		}

		$this->rebuildObjectFieldClassAssociationCache($objectClassId);

		XenForo_Db::commit($db);
	}

	public function rebuildObjectFieldClassAssociationCache($objectClassIds)
	{
		if (!is_array($objectClassIds))
		{
			$objectClassIds = array($objectClassIds);
		}
		if (!$objectClassIds)
		{
			return;
		}

		$classes = $this->_getClassModel()->getAllClasses();

		$db = $this->_getDb();

		$newCache = array();

		foreach ($this->getObjectFieldsInClasses($objectClassIds) AS $field)
		{
			$fieldGroupId = $field['field_group_id'];
			$newCache[$field['object_class_id']][$fieldGroupId][$field['field_id']] = $field['field_id'];
		}

		XenForo_Db::beginTransaction($db);

		foreach ($objectClassIds AS $objectClassId)
		{
			$update = (isset($newCache[$objectClassId]) ? serialize($newCache[$objectClassId]) : '');
			if (isset($classes[$objectClassId]))
			{
				$db->update('xf_object_class', array(
						'field_cache' => $update
					), 'object_class_id = ' . $db->quote($objectClassId));
			}
		}

		XenForo_Db::commit($db);
	}

	/**
	 * Fetches an array of custom object fields including display group info, for use in <xen:options source />
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array
	 */
	public function getObjectFieldOptions(array $conditions = array(), array $fetchOptions = array())
	{
		$fieldGroups = $this->getObjectFieldsByAddOns($conditions, $fetchOptions);

		$options = array();

		foreach ($fieldGroups AS $fieldGroupId => $fields)
		{
			if ($fields)
			{
				if ($fieldGroupId)
				{
					$groupTitle = new XenForo_Phrase($this->getObjectFieldGroupTitlePhraseName($fieldGroupId));
					$groupTitle = (string)$groupTitle;
				}
				else
				{
					$groupTitle = new XenForo_Phrase('ungrouped');
					$groupTitle = '(' . $groupTitle . ')';
				}

				foreach ($fields AS $fieldId => $field)
				{
					$options[$groupTitle][$fieldId] = array(
							'value' => $fieldId,
							'label' => (string)$field['title'],
							'_data' => array()
					);
				}
			}
		}

		return $options;
	}

	/**
	 * Gets the possible object field types.
	 *
	 * @return array [type] => keys: value, label, hint (optional)
	 */
	public function getObjectFieldTypes()
	{
		return array(
			'textbox' => array(
				'value' => 'textbox',
				'label' => new XenForo_Phrase('single_line_text_box')
			),
			'textarea' => array(
				'value' => 'textarea',
				'label' => new XenForo_Phrase('multi_line_text_box')
			),
			'select' => array(
				'value' => 'select',
				'label' => new XenForo_Phrase('drop_down_selection')
			),
			'radio' => array(
				'value' => 'radio',
				'label' => new XenForo_Phrase('radio_buttons')
			),
			'checkbox' => array(
				'value' => 'checkbox',
				'label' => new XenForo_Phrase('check_boxes')
			),
			'multiselect' => array(
				'value' => 'multiselect',
				'label' => new XenForo_Phrase('multiple_choice_drop_down_selection')
			),
			'callback' => array(
				'value' => 'callback',
				'label' => new XenForo_Phrase('php_callback')
			)
		);
	}

	/**
	 * Maps object fields to their high level type "group". Field types can be changed only
	 * within the group.
	 *
	 * @return array [field type] => type group
	 */
	public function getObjectFieldTypeMap()
	{
		return array(
			'textbox' => 'text',
			'textarea' => 'text',
			'radio' => 'single',
			'select' => 'single',
			'checkbox' => 'multiple',
			'multiselect' => 'multiple',
			'callback' => 'text'
		);
	}

	/**
	 * Gets the field's description phrase name.
	 *
	 * @param string $fieldId
	 *
	 * @return string
	 */
	public function getObjectFieldDescriptionPhraseName($fieldId)
	{
		return 'object_field_' . $fieldId . '_desc';
	}

	/**
	 * Gets a field choices's phrase name.
	 *
	 * @param string $fieldId
	 * @param string $choice
	 *
	 * @return string
	 */
	public function getObjectFieldChoicePhraseName($fieldId, $choice)
	{
		return 'object_field_' . $fieldId . '_choice_' . $choice;
	}

	/**
	 * Gets a field's master title phrase text.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getObjectFieldMasterTitlePhraseValue($id)
	{
		$phraseName = $this->getObjectFieldTitlePhraseName($id);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	/**
	 * Gets a field's master description phrase text.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getObjectFieldMasterDescriptionPhraseValue($id)
	{
		$phraseName = $this->getObjectFieldDescriptionPhraseName($id);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	protected function _prepareFieldValues(array $fields = array())
	{
		$values = array();
		foreach ($fields AS $field)
		{
			if ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect')
			{
				$values[$field['field_id']] = @unserialize($field['field_value']);
			}
			else
			{
				$values[$field['field_id']] = $field['field_value'];
			}
		}

		return $values;
	}

	/**
	 * Gets the object field values for the given object.
	 *
	 * @param integer $objectId
	 *
	 * @return array [field id] => value (may be string or array)
	 */
	public function getObjectFieldValues($objectId)
	{
		$fields = $this->_getDb()->fetchAll('
			SELECT value.*, field.field_type
			FROM xf_object_field_value AS value
			INNER JOIN xf_object_field AS field ON (field.field_id = value.field_id)
			WHERE value.object_id = ?
		', $objectId);

		return $this->_prepareFieldValues($fields);
	}

	/**
	 * Gets the object field values for the given object.
	 *
	 * @param integer $articleId
	 *
	 * @return array [field id] => value (may be string or array)
	 */
	public function getArticleFieldValues($articleId)
	{
		$fields = $this->_getDb()->fetchAll('
				SELECT value.*, field.field_type
				FROM xf_article_field_value AS value
				INNER JOIN xf_object_field AS field ON (field.field_id = value.field_id)
				WHERE value.article_id = ?
				', $articleId);

		return $this->_prepareFieldValues($fields);
	}

	/**
	 * Gets the default object field values for the given class.
	 *
	 * @param integer $classId
	 *
	 * @return array [field id] => value (may be string or array)
	 */
	public function getDefaultObjectFieldValues($classId = null)
	{
		if ($classId)
		{
			$fields = $this->_getDb()->fetchAll('
					SELECT ocf.*, field.field_type
					FROM xf_object_class_field AS ocf
					INNER JOIN xf_object_field AS field ON (field.field_id = ocf.field_id)
					WHERE ocf.object_class_id = ?
				', $classId);

			return $this->_prepareFieldValues($fields);
		}
		else
		{
			return array(
				'field_id' => null,
				'field_group_id' => '0',
				'display_order' => 1,
				'field_type' => 'textbox',
				'match_type' => 'none',
				'max_length' => 0,
				'field_choices' => '',
			);
		}
	}

	/**
	 * Rebuilds the cache of object field info for front-end display
	 *
	 * @return array
	 */
	public function rebuildObjectFieldCache()
	{
		$cache = array();
		foreach ($this->getObjectFields() as $field)
		{
			$cache[$field['field_id']] = XenForo_Application::arrayFilterKeys($field, array(
				'field_id',
				'field_type',
				'field_group_id',
			));
		}

		$this->_getDataRegistryModel()->set('objectFieldsInfo', $cache);
		return $cache;
	}

	/**
	 * Rebuilds the 'materialized_order' field in the field table,
	 * based on the canonical display_order data in the field and field_group tables.
	 */
	public function rebuildObjectFieldMaterializedOrder($addOnId = '')
	{
		$fields = $this->getObjectFieldsInAddOn($addOnId, array(), array('order' => 'canonical_order'));

		$db = $this->_getDb();
		$ungroupedFields = array();
		$updates = array();
		$i = 0;

		foreach ($fields AS $fieldId => $field)
		{
			if ($field['field_group_id'])
			{
				if (++$i != $field['materialized_order'])
				{
					$updates[$fieldId] = 'WHEN ' . $db->quote($fieldId) . ' THEN ' . $db->quote($i);
				}
			}
			else
			{
				$ungroupedFields[$fieldId] = $field;
			}
		}

		foreach ($ungroupedFields AS $fieldId => $field)
		{
			if (++$i != $field['materialized_order'])
			{
				$updates[$fieldId] = 'WHEN ' . $db->quote($fieldId) . ' THEN ' . $db->quote($i);
			}
		}

		if (!empty($updates))
		{
			$db->query('
					UPDATE xf_object_field SET materialized_order = CASE field_id
					' . implode(' ', $updates) . '
					END
					WHERE field_id IN(' . $db->quote(array_keys($updates)) . ')
						AND addon_id = ' . $db->quote($addOnId) . '
					');
		}
	}

	public function verifyObjectFieldIsUsable($fieldId, $classId, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$fieldId)
		{
			return true; // not picking one, always ok
		}

		$field = $this->getObjectFieldIfInClass($fieldId, $classId);
		if (!$field)
		{
			return false; // bad field or bad class
		}

		return $this->_verifyObjectFieldIsUsableInternal($field, $viewingUser);
	}

	protected function _verifyObjectFieldIsUsableInternal(array $field, array $viewingUser)
	{
		$userGroups = explode(',', $field['allowed_user_group_ids']);
		if (in_array(-1, $userGroups) || in_array($viewingUser['user_group_id'], $userGroups))
		{
			return true; // available to all groups or the primary group
		}

		if ($viewingUser['secondary_group_ids'])
		{
			foreach (explode(',', $viewingUser['secondary_group_ids']) AS $userGroupId)
			{
				if (in_array($userGroupId, $userGroups))
				{
					return true; // available to one secondary group
				}
			}
		}

		return false; // not available to any groups
	}

	// field groups ---------------------------------------------------------

	/**
	 * Fetches a single field group, as defined by its unique field group ID
	 *
	 * @param integer $fieldGroupId
	 *
	 * @return array
	 */
	public function getObjectFieldGroupById($fieldGroupId)
	{
		if (!$fieldGroupId)
		{
			return array();
		}

		return $this->_getDb()->fetchRow('
				SELECT *
				FROM xf_object_field_group
				WHERE field_group_id = ?
				', $fieldGroupId);
	}

	public function getAllObjectFieldGroups()
	{
		return $this->fetchAllKeyed('
				SELECT *
				FROM xf_object_field_group
				ORDER BY display_order
				', 'field_group_id');
	}

	public function getObjectFieldGroupOptions($selectedGroupId = '')
	{
		$fieldGroups = $this->getAllObjectFieldGroups();
		$fieldGroups = $this->prepareObjectFieldGroups($fieldGroups);

		$options = array();

		foreach ($fieldGroups AS $fieldGroupId => $fieldGroup)
		{
			$options[$fieldGroupId] = $fieldGroup['title'];
		}

		return $options;
	}

	public function getObjectFieldGroupTitlePhraseName($fieldGroupId)
	{
		return 'object_field_group_' . $fieldGroupId;
	}

	public function prepareObjectFieldGroups(array $fieldGroups)
	{
		return array_map(array($this, 'prepareObjectFieldGroup'), $fieldGroups);
	}

	public function prepareObjectFieldGroup(array $fieldGroup)
	{
		$fieldGroup['title'] = new XenForo_Phrase($this->getObjectFieldGroupTitlePhraseName($fieldGroup['field_group_id']));

		return $fieldGroup;
	}

	/**
	 * Gets the XML representation of a field, including customized templates.
	 *
	 * @param array $field
	 *
	 * @return DOMDocument
	 */
	public function getFieldXml(array $field)
	{
		$document = new DOMDocument('1.0', 'utf-8');
		$document->formatOutput = true;

		$rootNode = $document->createElement('field');
		$this->_appendFieldXml($rootNode, $field);
		$document->appendChild($rootNode);

		$templatesNode = $document->createElement('templates');
		$rootNode->appendChild($templatesNode);
		$this->getModelFromCache('ThemeHouse_ObjectFields_Model_Template')->appendTemplatesFieldXml($templatesNode, $field);

		$adminTemplatesNode = $document->createElement('admin_templates');
		$rootNode->appendChild($adminTemplatesNode);
		$this->getModelFromCache('ThemeHouse_ObjectFields_Model_AdminTemplate')->appendAdminTemplatesFieldXml($adminTemplatesNode, $field);

		$phrasesNode = $document->createElement('phrases');
		$rootNode->appendChild($phrasesNode);
		$this->getModelFromCache('XenForo_Model_Phrase')->appendPhrasesFieldXml($phrasesNode, $field);

		return $document;
	}

	/**
	 * Appends the add-on field XML to a given DOM object.
	 *
	 * @param DOMElement $rootNode Node to append all objects to
	 * @param string $addOnId Add-on ID to be exported
	 */
	public function appendFieldsAddOnXml(DOMElement $rootNode, $addOnId)
	{
		$document = $rootNode->ownerDocument;

		$fields = $this->getObjectFields(array('addon_id' => $addOnId));
		foreach ($fields as $field)
		{
			$fieldNode = $document->createElement('field');
			$this->_appendFieldXml($fieldNode, $field);
			$rootNode->appendChild($fieldNode);
		}
	}

	/**
	 * @param DOMElement $rootNode
	 * @param array $field
	 */
	protected function _appendFieldXml(DOMElement $rootNode, $field)
	{
		$document = $rootNode->ownerDocument;

		$rootNode->setAttribute('export_callback_method', $field['export_callback_method']);
		$rootNode->setAttribute('export_callback_class', $field['export_callback_class']);
		$rootNode->setAttribute('post_save_callback_method', $field['post_save_callback_method']);
		$rootNode->setAttribute('post_save_callback_class', $field['post_save_callback_class']);
		$rootNode->setAttribute('pre_save_callback_method', $field['pre_save_callback_method']);
		$rootNode->setAttribute('pre_save_callback_class', $field['pre_save_callback_class']);
		$rootNode->setAttribute('field_callback_method', $field['field_callback_method']);
		$rootNode->setAttribute('field_callback_class', $field['field_callback_class']);
		$rootNode->setAttribute('field_choices_callback_class', $field['field_choices_callback_class']);
		$rootNode->setAttribute('field_choices_callback_method', $field['field_choices_callback_method']);
		$rootNode->setAttribute('max_length', $field['max_length']);
		$rootNode->setAttribute('match_callback_method', $field['match_callback_method']);
		$rootNode->setAttribute('match_callback_class', $field['match_callback_class']);
		$rootNode->setAttribute('match_regex', $field['match_regex']);
		$rootNode->setAttribute('match_type', $field['match_type']);
		$rootNode->setAttribute('field_type', $field['field_type']);
		$rootNode->setAttribute('display_order', $field['display_order']);
		$rootNode->setAttribute('field_id', $field['field_id']);
		$rootNode->setAttribute('addon_id', $field['addon_id']);

		$titleNode = $document->createElement('title');
		$rootNode->appendChild($titleNode);
		$titleNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, new XenForo_Phrase('object_field_'.$field['field_id'])));

		$descriptionNode = $document->createElement('description');
		$rootNode->appendChild($descriptionNode);
		$descriptionNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, new XenForo_Phrase('object_field_'.$field['field_id'].'_desc')));

		$fieldChoicesNode = $document->createElement('field_choices');
		$rootNode->appendChild($fieldChoicesNode);
		if ($field['field_choices'])
		{
			$fieldChoices = unserialize($field['field_choices']);
			foreach ($fieldChoices as $fieldChoiceValue => $fieldChoiceText)
			{
				$fieldChoiceNode = $document->createElement('field_choice');
				$fieldChoiceNode->setAttribute('value', $fieldChoiceValue);
				$fieldChoiceNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $fieldChoiceText));
				$fieldChoicesNode->appendChild($fieldChoiceNode);
			}
		}
	}

	/**
	 * Imports a field XML file.
	 *
	 * @param SimpleXMLElement $document
	 * @param string $fieldGroupId
	 * @param integer $overwriteFieldId
	 *
	 * @return array List of cache rebuilders to run
	 */
	public function importFieldXml(SimpleXMLElement $document, $fieldGroupId = 0, $overwriteFieldId = 0, $overwriteAddOnId = '')
	{
		if ($document->getName() != 'field')
		{
			throw new XenForo_Exception(new XenForo_Phrase('provided_file_is_not_valid_field_xml'), true);
		}

		$fieldId = (string)$document['field_id'];
		if ($fieldId === '')
		{
			throw new XenForo_Exception(new XenForo_Phrase('provided_file_is_not_valid_field_xml'), true);
		}

		$phraseModel = $this->_getPhraseModel();

		$overwriteField = array();
		if ($overwriteFieldId)
		{
			$overwriteField = $this->getObjectFieldInAddOnById($overwriteFieldId, $overwriteAddOnId);
		}

		$db = $this->_getDb();
		XenForo_Db::beginTransaction($db);

		$dw = XenForo_DataWriter::create('ThemeHouse_ObjectFields_DataWriter_ObjectField');
		if (isset($overwriteField['field_id']))
		{
			$dw->setExistingData(array($overwriteFieldId, $overwriteAddOnId));
		}
		else
		{
			$dw->set('field_id', $fieldId);
			$dw->set('field_group_id', $fieldGroupId);
		}

		$dw->bulkSet(array(
			'display_order' => $document['display_order'],
			'field_type' => $document['field_type'],
			'match_type' => $document['match_type'],
			'match_regex' => $document['match_regex'],
			'match_callback_class' => $document['match_callback_class'],
			'match_callback_method' => $document['match_callback_method'],
			'max_length' => $document['max_length'],
			'field_choices_callback_class' => $document['field_choices_callback_class'],
			'field_choices_callback_method' => $document['field_choices_callback_method'],
			'field_callback_class' => $document['field_callback_class'],
			'field_callback_method' => $document['field_callback_method'],
			'pre_save_callback_class' => $document['pre_save_callback_class'],
			'pre_save_callback_method' => $document['pre_save_callback_method'],
			'post_save_callback_class' => $document['post_save_callback_class'],
			'post_save_callback_method' => $document['post_save_callback_method'],
			'export_callback_class' => $document['export_callback_class'],
			'export_callback_method' => $document['export_callback_method'],
		));

		/* @var $addOnModel XenForo_Model_AddOn */
		$addOnModel = XenForo_Model::create('XenForo_Model_AddOn');
		$addOn = $addOnModel->getAddOnById($document['addon_id']);
		if (!empty($addOn))
		{
			$dw->set('addon_id', $addOn['addon_id']);
		}

		$dw->setExtraData(
				ThemeHouse_ObjectFields_DataWriter_ObjectField::DATA_TITLE,
				XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($document->title)
		);
		$dw->setExtraData(
				ThemeHouse_ObjectFields_DataWriter_ObjectField::DATA_DESCRIPTION,
				XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($document->description)
		);

		$fieldChoices = XenForo_Helper_DevelopmentXml::fixPhpBug50670($document->field_choices->field_choice);

		foreach ($fieldChoices as $fieldChoice)
		{
			if ($fieldChoice && $fieldChoice['value'])
			{
				$fieldChoicesCombined[(string)$fieldChoice['value']] = XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($fieldChoice);
			}
		}

		if (isset($fieldChoicesCombined)) $dw->setFieldChoices($fieldChoicesCombined);

		$dw->save();

		$this->getModelFromCache('ThemeHouse_ObjectFields_Model_Template')->importTemplatesFieldXml($document->templates);
		$this->getModelFromCache('ThemeHouse_ObjectFields_Model_AdminTemplate')->importAdminTemplatesFieldXml($document->admin_templates);
		$phraseModel->importPhrasesXml($document->phrases, 0);

		XenForo_Db::commit($db);

		return array('Template', 'Phrase');
	}

	/**
	 * Imports the add-on fields XML.
	 *
	 * @param SimpleXMLElement $xml XML object pointing to the root of the data
	 * @param string $addOnId Add-on to import for
	 * @param integer $maxExecution Maximum run time in seconds
	 * @param integer $offset Number of objects to skip
	 *
	 * @return boolean|integer True on completion; false if the XML isn't correct; integer otherwise with new offset value
	 */
	public function importFieldsAddOnXml(SimpleXMLElement $xml, $addOnId, $maxExecution = 0, $offset = 0)
	{
		$db = $this->_getDb();

		XenForo_Db::beginTransaction($db);

		$startTime = microtime(true);

		$fields = XenForo_Helper_DevelopmentXml::fixPhpBug50670($xml->field);

		$current = 0;
		$restartOffset = false;
		foreach ($fields AS $field)
		{
			$current++;
			if ($current <= $offset)
			{
				continue;
			}

			$fieldId = (string)$field['field_id'];

			if (!$field['addon_id'])
			{
				$field->addAttribute('addon_id', $addOnId);
			}

			$this->importFieldXml($field, 0, $fieldId, $addOnId);

			if ($maxExecution && (microtime(true) - $startTime) > $maxExecution)
			{
				$restartOffset = $current;
				break;
			}
		}

		XenForo_Db::commit($db);

		return ($restartOffset ? $restartOffset : true);
	}

	public function getAllObjectFields()
	{
		try {
			$objectFields = XenForo_Application::get('objectFieldsInfo');
		} catch (Exception $e) {
			$objectFields = XenForo_Model::create('XenForo_Model_DataRegistry')->get('objectFieldsInfo');
		}
		if (!is_array($objectFields))
		{
			$objectFields = XenForo_Model::create('ThemeHouse_ObjectFields_Model_ObjectField')->rebuildObjectFieldCache();
		}
		XenForo_Application::set('objectFieldsInfo', $objectFields);

		return $objectFields;
	}

	/**
	 * @return ThemeHouse_Objects_Model_Class
	 */
	protected function _getClassModel()
	{
		return $this->getModelFromCache('ThemeHouse_Objects_Model_Class');
	}

	/**
	 * @return XenForo_Model_Phrase
	 */
	protected function _getPhraseModel()
	{
		return $this->getModelFromCache('XenForo_Model_Phrase');
	}
}