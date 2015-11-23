<?php

/**
* Data writer for custom object fields.
*/
class ThemeHouse_ObjectFields_DataWriter_ObjectField extends XenForo_DataWriter
{
	/**
	 * Constant for extra data that holds the value for the phrase
	 * that is the title of this field.
	 *
	 * This value is required on inserts.
	 *
	 * @var string
	 */
	const DATA_TITLE = 'phraseTitle';
	
	const OPTION_MASS_UPDATE = 'massUpdate';
	
	/**
	 * Constant for extra data that holds the value for the phrase
	 * that is the description of this field.
	 *
	 * @var string
	 */
	const DATA_DESCRIPTION = 'phraseDescription';

	/**
	 * Title of the phrase that will be created when a call to set the
	 * existing data fails (when the data doesn't exist).
	 *
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'requested_field_not_found';

	/**
	 * List of choices, if this is a choice field. Interface to set field_choices properly.
	 *
	 * @var null|array
	 */
	protected $_fieldChoices = null;

	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'xf_object_field' => array(
				'field_id'              => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 25,
						'verification' => array('$this', '_verifyFieldId'), 'requiredError' => 'please_enter_valid_field_id'
				),
				'field_group_id'        => array('type' => self::TYPE_UINT, 'default' => 0),
				'display_order'         => array('type' => self::TYPE_UINT_FORCED, 'default' => 0),
				'materialized_order'    => array('typpe' => self::TYPE_UINT_FORCED, 'default' => 0),
				'field_type'            => array('type' => self::TYPE_STRING, 'default' => 'textbox',
					'allowedValues' => array('textbox', 'textarea', 'select', 'radio', 'checkbox', 'multiselect', 'callback')
				),
				'field_choices'         => array('type' => self::TYPE_SERIALIZED, 'default' => ''),
				'match_type'            => array('type' => self::TYPE_STRING, 'default' => 'none',
					'allowedValues' => array('none', 'number', 'alphanumeric', 'email', 'url', 'regex', 'callback')
				),
				'match_regex'           => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 250),
				'match_callback_class'  => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'match_callback_method' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'max_length'            => array('type' => self::TYPE_UINT, 'default' => 0),
				'allowed_user_group_ids' => array('type' => self::TYPE_UNKNOWN, 'default' => '',
					'verification' => array('$this', '_verifyAllowedUserGroupIds')
				),
				'addon_id'				=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 25),
				'field_choices_callback_class'  => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'field_choices_callback_method' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'field_callback_class'  => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'field_callback_method' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'pre_save_callback_class'  => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'pre_save_callback_method' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'post_save_callback_class' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'post_save_callback_method'=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'export_callback_class'  => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'export_callback_method' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
			)
		);
	}

	/**
	* Gets the actual existing data out of data that was passed in. See parent for explanation.
	*
	* @param mixed
	*
	* @return array|false
	*/
	protected function _getExistingData($data)
	{
		if (!is_array($data))
		{
			return false;
		}
		else if (isset($data['field_id'], $data['addon_id']))
		{
			$fieldId = $data['field_id'];
			$addOnId = $data['addon_id'];
		}
		else if (isset($data[0], $data[1]))
		{
			$fieldId = $data[0];
			$addOnId = $data[1];
		}
		else
		{
			return false;
		}

		return array('xf_object_field' => $this->_getFieldModel()->getObjectFieldInAddOnById($fieldId, $addOnId));
	}

	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'field_id = ' . $this->_db->quote($this->getExisting('field_id')) . ' AND addon_id = ' . $this->_db->quote($this->getExisting('addon_id'));
	}

	/**
	 * Gets the default options for this data writer.
	 */
	protected function _getDefaultOptions()
	{
		return array(
				self::OPTION_MASS_UPDATE => false
		);
	}
	
	/**
	 * Verifies that the ID contains valid characters and does not already exist.
	 *
	 * @param $id
	 *
	 * @return boolean
	 */
	protected function _verifyFieldId(&$id)
	{
		if (preg_match('/[^a-zA-Z0-9_]/', $id))
		{
			$this->error(new XenForo_Phrase('please_enter_an_id_using_only_alphanumeric'), 'field_id');
			return false;
		}

		if (($id !== $this->getExisting('field_id') || $this->isChanged('addon_id')) 
			&& $this->_getFieldModel()->getObjectFieldInAddOnById($id, $this->get('addon_id')))
		{
			$this->error(new XenForo_Phrase('field_ids_must_be_unique'), 'field_id');
			return false;
		}

		return true;
	}
	
	/**
	 * Verifies the allowed user group IDs.
	 *
	 * @param array|string $userGroupIds Array or comma-delimited list
	 *
	 * @return boolean
	 */
	protected function _verifyAllowedUserGroupIds(&$userGroupIds)
	{
		if (!is_array($userGroupIds))
		{
			$userGroupIds = preg_split('#,\s*#', $userGroupIds);
		}
	
		$userGroupIds = array_map('intval', $userGroupIds);
		$userGroupIds = array_unique($userGroupIds);
		sort($userGroupIds, SORT_NUMERIC);
		$userGroupIds = implode(',', $userGroupIds);
	
		return true;
	}
	
	/**
	 * Sets the choices for this field.
	 *
	 * @param array $choices [choice key] => text
	 */
	public function setFieldChoices(array $choices)
	{
		foreach ($choices AS $value => &$text)
		{
			if ($value === '')
			{
				unset($choices[$value]);
				continue;
			}

			$text = strval($text);

			if ($text === '')
			{
				$this->error(new XenForo_Phrase('please_enter_text_for_each_choice'), 'field_choices');
				return false;
			}

			if (preg_match('#[^a-z0-9_]#i', $value))
			{
				$this->error(new XenForo_Phrase('please_enter_an_id_using_only_alphanumeric'), 'field_choices');
				return false;
			}

			if (strlen($value) > 25)
			{
				$this->error(new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', array('count' => 25)));
				return false;
			}
		}

		$this->_fieldChoices = $choices;
		$this->set('field_choices', $choices);

		return true;
	}

	/**
	 * Pre-save behaviors.
	 */
	protected function _preSave()
	{
		if ($this->isChanged('match_callback_class') || $this->isChanged('match_callback_method'))
		{
			$class = $this->get('match_callback_class');
			$method = $this->get('match_callback_method');

			if (!$class || !$method)
			{
				$this->set('match_callback_class', '');
				$this->set('match_callback_method', '');
			}
			else if (!XenForo_Application::autoload($class) || !method_exists($class, $method))
			{
				$this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
			}
		}

		if ($this->isUpdate() && $this->isChanged('field_type'))
		{
			$typeMap = $this->_getFieldModel()->getObjectFieldTypeMap();
			if ($typeMap[$this->get('field_type')] != $typeMap[$this->getExisting('field_type')])
			{
				$this->error(new XenForo_Phrase('you_may_not_change_field_to_different_type_after_it_has_been_created'), 'field_type');
			}
		}
		
		if (!$this->get('field_choices_callback_class') && !$this->get('field_choices_callback_method') && in_array($this->get('field_type'), array('select', 'radio', 'checkbox', 'multiselect')))
		{
			if (($this->isInsert() && !$this->_fieldChoices) || (is_array($this->_fieldChoices) && !$this->_fieldChoices))
			{
				$this->error(new XenForo_Phrase('please_enter_at_least_one_choice'), 'field_choices', false);
			}
		}
		else
		{
			$this->setFieldChoices(array());
		}

		if (!$this->getOption(self::OPTION_MASS_UPDATE))
		{
			$titlePhrase = $this->getExtraData(self::DATA_TITLE);
			if ($titlePhrase !== null && strlen($titlePhrase) == 0)
			{
				$this->error(new XenForo_Phrase('please_enter_valid_title'), 'title');
			}
		}
	}

	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		if (!$this->getOption(self::OPTION_MASS_UPDATE))
		{
			$fieldId = $this->get('field_id');
	
			if ($this->isUpdate() && $this->isChanged('field_id'))
			{
				$this->_renameMasterPhrase(
					$this->_getTitlePhraseName($this->getExisting('field_id')),
					$this->_getTitlePhraseName($fieldId)
				);
	
				$this->_renameMasterPhrase(
					$this->_getDescriptionPhraseName($this->getExisting('field_id')),
					$this->_getDescriptionPhraseName($fieldId)
				);
			}

			$titlePhrase = $this->getExtraData(self::DATA_TITLE);
			if ($titlePhrase !== null)
			{
				$this->_insertOrUpdateMasterPhrase(
					$this->_getTitlePhraseName($fieldId), $titlePhrase,
					'', array('global_cache' => 1)
				);
			}
	
			$descriptionPhrase = $this->getExtraData(self::DATA_DESCRIPTION);
			if ($descriptionPhrase !== null)
			{
				$this->_insertOrUpdateMasterPhrase(
					$this->_getDescriptionPhraseName($fieldId), $descriptionPhrase
				);
			}

			if (is_array($this->_fieldChoices))
			{
				$this->_deleteExistingChoicePhrases();
	
				foreach ($this->_fieldChoices AS $choice => $text)
				{
					$this->_insertOrUpdateMasterPhrase(
						$this->_getChoicePhraseName($fieldId, $choice), $text,
						'', array('global_cache' => 1)
					);
				}
			}
			
			if ($this->isChanged('display_order') || $this->isChanged('field_group_id'))
			{
				$this->_getFieldModel()->rebuildObjectFieldMaterializedOrder($this->get('addon_id'));
			}				

			$this->_rebuildObjectFieldCache();
		}
	}

	/**
	 * Post-delete behaviors.
	 */
	protected function _postDelete()
	{
		$fieldId = $this->get('field_id');

		$this->_deleteMasterPhrase($this->_getTitlePhraseName($fieldId));
		$this->_deleteMasterPhrase($this->_getDescriptionPhraseName($fieldId));
		$this->_deleteExistingChoicePhrases();

		$this->_db->delete('xf_object_field_value', 'field_id = ' . $this->_db->quote($fieldId));
		// note the object caches aren't rebuilt here; this shouldn't be an issue as we don't enumerate them
	}

	/**
	 * Deletes all phrases for existing choices.
	 */
	protected function _deleteExistingChoicePhrases()
	{
		$fieldId = $this->get('field_id');

		$existingChoices = $this->getExisting('field_choices');
		if ($existingChoices && $existingChoices = @unserialize($existingChoices))
		{
			foreach ($existingChoices AS $choice => $text)
			{
				$this->_deleteMasterPhrase($this->_getChoicePhraseName($fieldId, $choice));
			}
		}
	}

	/**
	 * Gets the name of the title phrase for this field.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function _getTitlePhraseName($id)
	{
		return $this->_getFieldModel()->getObjectFieldTitlePhraseName($id);
	}

	/**
	 * Gets the name of the description phrase for this field.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function _getDescriptionPhraseName($id)
	{
		return $this->_getFieldModel()->getObjectFieldDescriptionPhraseName($id);
	}

	/**
	 * Gets the name of the choice phrase for a value in this field.
	 *
	 * @param string $fieldId
	 * @param string $choice
	 *
	 * @return string
	 */
	protected function _getChoicePhraseName($fieldId, $choice)
	{
		return $this->_getFieldModel()->getObjectFieldChoicePhraseName($fieldId, $choice);
	}

	protected function _rebuildObjectFieldCache()
	{
		return $this->_getFieldModel()->rebuildObjectFieldCache();
	}

	/**
	 * @return ThemeHouse_ObjectFields_Model_ObjectField
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('ThemeHouse_ObjectFields_Model_ObjectField');
	}
}