<?php

class ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Object extends XFCP_ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Object
{
	protected static $_objectFields;
	const IGNORE_REQUIRED_FIELDS = 'ignoreRequiredFields';

	/**
	 * The custom fields to be updated. Use setCustomFields to manage this.
	 *
	 * @var array
	 */
	protected $_updateCustomFields = array();

	/**
	 * @return array
	 */
	protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields['xf_object']['custom_fields'] = array('type' => self::TYPE_SERIALIZED, 'default' => '');

		$maxKey = $this->_getClassModel()->getMaxKey();

		for ($i=0; $i < $maxKey; $i++) {
			$fields['xf_object']['key_'.$i] = array('type' => self::TYPE_STRING, 'default' => '');
		}

		return $fields;
	}

	protected function _preSave()
	{
	    if (isset($GLOBALS['ThemeHouse_Objects_ControllerPublic_Object'])) {
	        /* @var $controller ThemeHouse_Objects_ControllerPublic_Object */
	        $controller = $GLOBALS['ThemeHouse_Objects_ControllerPublic_Object'];

            $customFields = $controller->getInput()->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
            $customFieldsShown = $controller->getInput()->filterSingle('custom_fields_shown', XenForo_Input::STRING, array('array' => true));
            $this->setCustomFields($customFields, $customFieldsShown);
	    }

	    if (isset($GLOBALS['ThemeHouse_Objects_ControllerAdmin_Object'])) {
	        /* @var $controller ThemeHouse_Objects_ControllerAdmin_Object */
	        $controller = $GLOBALS['ThemeHouse_Objects_ControllerAdmin_Object'];

            $customFields = $controller->getInput()->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
            $customFieldsShown = $controller->getInput()->filterSingle('custom_fields_shown', XenForo_Input::STRING, array('array' => true));
            $this->setCustomFields($customFields, $customFieldsShown);
	    }

		/*
		$fields = $this->_getObjectFieldDefinitions();
		foreach ($fields as $field)
		{
			if ($field['pre_save_callback_class'] && $field['pre_save_callback_method'])
			{
				call_user_func_array(
						array($field['pre_save_callback_class'], $field['pre_save_callback_method']),
						array($this, $field)
				);
			}
		}
		*/
		$class = $this->_getClassModel()->getClassById($this->get('class_id'));
		$classRequiredFields = array();
		if (isset($class['required_fields']) && $class['required_fields']) {
			$classRequiredFields = unserialize($class['required_fields']);
		}

		$customFields = $this->get('custom_fields');
		if ($customFields) $customFields = unserialize($customFields);
		if (!$this->isExtraDataSet(self::IGNORE_REQUIRED_FIELDS)) {
			foreach ($classRequiredFields as $fieldId) {
				if (!isset($customFields[$fieldId]) || ($customFields[$fieldId] === '' || $customFields[$fieldId] === array())) {
					$this->error(new XenForo_Phrase('please_enter_value_for_all_required_fields'), "custom_field_$fieldId");
					continue;
				}
			}
		}

		if ($class['title_field']) {
			$this->set('title', $this->_getObjectModel()->buildObjectTitle($this->getMergedData()));
		}

		if ($class['subtitle_field']) {
			$this->set('subtitle', $this->_getObjectModel()->buildObjectTitle($this->getMergedData(), 'subtitle'));
		}

		if ($class['keys']) {
			$keys = unserialize($class['keys']);
			foreach ($keys as $keyId => $key) {
				if (isset($customFields[$key])) {
					$this->set('key_'.$keyId, $customFields[$key]);
				}
			}
		}

		parent::_preSave();
	}

	protected function _postSave()
	{
		$this->updateCustomFields();

		parent::_postSave();
	}

	public function setCustomFields(array $fieldValues, array $fieldsShown = null)
	{
		if ($fieldsShown === null)
		{
			// not passed - assume keys are all there
			$fieldsShown = array_keys($fieldValues);
		}

		$fieldModel = $this->_getFieldModel();
		$fields = $this->_getObjectFieldDefinitions();
		$callbacks = array();

		if ($this->get('object_id') && !$this->_importMode)
		{
			$existingValues = $fieldModel->getObjectFieldValues($this->get('object_id'));
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
			$objectId = $this->get('object_id');

			// $pairedFields = array();
			foreach ($this->_updateCustomFields AS $fieldId => $value)
			{
				if (is_array($value))
				{
					$value = serialize($value);
				}
				$this->_db->query('
						INSERT INTO xf_object_field_value
						(object_id, field_id, field_value)
						VALUES
						(?, ?, ?)
						ON DUPLICATE KEY UPDATE
						field_value = VALUES(field_value)
						', array($objectId, $fieldId, $value));
			}
		}
	}

	/**
	 * Fetch (and cache) user field definitions
	 *
	 * @return array
	 */
	protected function _getObjectFieldDefinitions()
	{
		$fields = self::$_objectFields;

		if (is_null($fields))
		{
			$fields = $this->_getFieldModel()->getObjectFields();

			self::$_objectFields = $fields;
		}

		return $fields;
	}

	/**
	 * @return ThemeHouse_Objects_Model_Class
	 */
	protected function _getClassModel()
	{
		return $this->getModelFromCache('ThemeHouse_Objects_Model_Class');
	}

	/**
	 * @return ThemeHouse_Objects_Model_Object
	 */
	protected function _getObjectModel()
	{
		return $this->getModelFromCache('ThemeHouse_Objects_Model_Object');
	}

	/**
	 * @return ThemeHouse_ObjectFields_Model_ObjectField
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('ThemeHouse_ObjectFields_Model_ObjectField');
	}
}