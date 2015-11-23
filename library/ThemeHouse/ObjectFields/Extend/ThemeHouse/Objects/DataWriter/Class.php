<?php

class ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Class extends XFCP_ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Class
{
	const DATA_OBJECT_FIELD_DEFINITIONS = 'objectFields';

	const ThemeHouse_ObjectFields_CONTROLLERADMIN_CLASS = 'ThemeHouse_ObjectFields_ControllerAdmin_Class';
	const ThemeHouse_ObjectFields_MODEL_CLASS = 'ThemeHouse_ObjectFields_Model_Class';

	/**
	 * The custom fields to be updated. Use setCustomFields to manage these.
	 *
	 * @var array
	 */
	protected $_updateCustomFields = array();

	/**
	 * @return array
	 */
	protected function _getDefaultOptions()
	{
		if (isset($GLOBALS['ThemeHouse_ObjectFields_ControllerAdmin_Class']))
		{
			$this->setExtraData(self::ThemeHouse_ObjectFields_CONTROLLERADMIN_CLASS,
					$GLOBALS['ThemeHouse_ObjectFields_ControllerAdmin_Class']);
			unset($GLOBALS['ThemeHouse_ObjectFields_ControllerAdmin_Class']);
		}

		if (isset($GLOBALS['ThemeHouse_ObjectFields_Model_Class']))
		{
			$this->setExtraData(self::ThemeHouse_ObjectFields_MODEL_CLASS,
					$GLOBALS['ThemeHouse_ObjectFields_Model_Class']);
			unset($GLOBALS['ThemeHouse_ObjectFields_Model_Class']);
		}

		return parent::_getDefaultOptions();
	}

	/**
	 * @return array
	 */
	protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields['xf_object_class']['custom_fields'] = array('type' => self::TYPE_SERIALIZED, 'default' => '');
		$fields['xf_object_class']['required_fields'] = array('type' => self::TYPE_SERIALIZED, 'default' => '');
		$fields['xf_object_class']['title_field'] = array('type' => self::TYPE_STRING, 'default' => '');
		$fields['xf_object_class']['subtitle_field'] = array('type' => self::TYPE_STRING, 'default' => '');
		$fields['xf_object_class']['keys'] = array('type' => self::TYPE_SERIALIZED, 'default' => '');
		$fields['xf_object_class']['unique_keys'] = array('type' => self::TYPE_SERIALIZED, 'default' => '');
		$fields['xf_object_class']['primary_key'] = array('type' => self::TYPE_STRING, 'default' => '');

		return $fields;
	}

	protected function _preSave()
	{
		if ($this->isExtraDataSet(self::ThemeHouse_ObjectFields_MODEL_CLASS))
		{
			$this->getExtraData(self::ThemeHouse_ObjectFields_MODEL_CLASS)->processCustomFieldValues($this);
		}

		if ($this->isExtraDataSet(self::ThemeHouse_ObjectFields_CONTROLLERADMIN_CLASS))
		{
			$this->getExtraData(self::ThemeHouse_ObjectFields_CONTROLLERADMIN_CLASS)->processCustomFieldValues($this);
		}

		parent::_preSave();
	}

	protected function _postSave()
	{
		if ($this->isExtraDataSet(self::ThemeHouse_ObjectFields_MODEL_CLASS))
		{
			$this->getExtraData(self::ThemeHouse_ObjectFields_MODEL_CLASS)->processCustomFields($this);
			$this->_updateCustomFields = unserialize($this->get('custom_fields'));
		}

		if ($this->isExtraDataSet(self::ThemeHouse_ObjectFields_CONTROLLERADMIN_CLASS))
		{
			$this->getExtraData(self::ThemeHouse_ObjectFields_CONTROLLERADMIN_CLASS)->processCustomFields($this);
			$this->_updateCustomFields = unserialize($this->get('custom_fields'));
		}

		$this->updateCustomFields();

		parent::_postSave();

		$this->_rebuildClassCache();
	}

	public function setCustomFields(array $fieldValues, array $fieldsShown = null)
	{
		if ($fieldsShown === null)
		{
			// not passed - assume keys are all there
			$fieldsShown = array_keys($fieldValues);
		}

		$fieldModel = $this->_getObjectFieldModel();
		$fields = $this->_getObjectFieldDefinitions();
		$callbacks = array();

		if ($this->get('class_id') && !$this->_importMode)
		{
			$existingValues = $fieldModel->getDefaultObjectFieldValues($this->get('class_id'));
		}
		else
		{
			$existingValues = array();
		}

		$finalValues = array();

		foreach ($fieldsShown AS $fieldId)
		{
			if (!isset($fields[$fieldId]))
			{
				continue;
			}

			$field = $fields[$fieldId];
			if ($field['field_type'] == 'callback')
			{
				if (isset($fieldValues[$fieldId]))
				{
					if (is_array($fieldValues[$fieldId]))
					{
						$fieldValues[$fieldId] = serialize($fieldValues[$fieldId]);
						$callbacks[] = $fieldId;
					}
				}
				$field['field_type'] = 'textbox';
			}
			$multiChoice = ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect');

			if ($multiChoice)
			{
				// multi selection - array
				$value = (isset($fieldValues[$fieldId]) && is_array($fieldValues[$fieldId]))
				? $fieldValues[$fieldId] : array();
			}
			else
			{
				// single selection - string
				$value = (isset($fieldValues[$fieldId]) ? strval($fieldValues[$fieldId]) : '');
			}

			$existingValue = (isset($existingValues[$fieldId]) ? $existingValues[$fieldId] : null);

			if (!$this->_importMode)
			{
				$error = '';
				$valid = $fieldModel->verifyObjectFieldValue($field, $value, $error);
				if (!$valid)
				{
					$this->error($error, "custom_field_$fieldId");
					continue;
				}
			}

			foreach ($callbacks AS $callbackFieldId)
			{
				if (isset($fieldValues[$callbackFieldId]))
				{
					if (is_array($fieldValues[$callbackFieldId]))
					{
						$value = unserialize($value);
					}
				}
			}

			if ($value !== $existingValue)
			{
				$finalValues[$fieldId] = $value;
			}
		}

		$this->_updateCustomFields = $finalValues + $this->_updateCustomFields;
		$this->set('custom_fields', $finalValues + $existingValues);
	}

	public function updateCustomFields()
	{
		if ($this->_updateCustomFields)
		{
			$classId = $this->get('class_id');

			foreach ($this->_updateCustomFields AS $fieldId => $value)
			{
				if (is_array($value))
				{
					$value = serialize($value);
				}
				$this->_db->update('xf_object_class_field',
					array('field_value' => $value),
					'object_class_id = '.$this->_db->quote($classId).' AND field_id = '.$this->_db->quote($fieldId));
			}
		}
	}

	/**
	 * Fetch (and cache) object field definitions
	 *
	 * @return array
	 */
	protected function _getObjectFieldDefinitions()
	{
		$fields = $this->getExtraData(self::DATA_OBJECT_FIELD_DEFINITIONS);

		if (is_null($fields))
		{
			$fields = $this->_getObjectFieldModel()->getObjectFields();

			$this->setExtraData(self::DATA_OBJECT_FIELD_DEFINITIONS, $fields);
		}

		return $fields;
	}

	protected function _rebuildClassCache()
	{
		return $this->_getClassModel()->rebuildClassCache();
	}

	/**
	 * @return ThemeHouse_Objects_Model_Class
	 */
	protected function _getClassModel()
	{
		return $this->getModelFromCache('ThemeHouse_Objects_Model_Class');
	}

	/**
	 * @return ThemeHouse_ObjectFields_Model_ObjectField
	 */
	protected function _getObjectFieldModel()
	{
		return $this->getModelFromCache('ThemeHouse_ObjectFields_Model_ObjectField');
	}
}