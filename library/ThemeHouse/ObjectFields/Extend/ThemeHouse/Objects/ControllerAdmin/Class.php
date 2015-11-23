<?php

class ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_ControllerAdmin_Class extends XFCP_ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_ControllerAdmin_Class
{
	public function actionAdd()
	{
		$response = parent::actionAdd();

		$classId = $this->_input->filterSingle('class_id', XenForo_Input::STRING);
		if ($classId) {
			if ($response instanceof XenForo_ControllerResponse_View) {
				$class = $response->params['class'];

				$classRequiredFields = array();
				if ($class['required_fields']) $classRequiredFields = unserialize($response->params['class']['required_fields']);

				$fieldValues = array();
				if (isset($class['custom_fields']) && $class['custom_fields'])
				{
					$fieldValues = unserialize($class['custom_fields']);
				}

				$response->params['customFields'] = $this->_getFieldModel()->prepareGroupedObjectFields(
					$this->_getFieldModel()->getUsableObjectFieldsInClasses(array($classId)),
					true,
					$fieldValues,
					true,
					$classRequiredFields
				);
			}
		} else {
			$response = $this->_addCustomFieldsToResponse($response);
		}
		return $response;
	}

	public function actionEdit()
	{
		$response = parent::actionEdit();

		return $this->_addCustomFieldsToResponse($response);
	}

	protected function _addCustomFieldsToResponse(XenForo_ControllerResponse_Abstract $response)
	{
		if ($response instanceof XenForo_ControllerResponse_View) {
			$class = array('addon_id' => '');
			if (isset($response->params['class'])) {
				$class =& $response->params['class'];
			}

			$fieldModel = $this->_getFieldModel();

			$requiredFields = array();
			$classFields = array();
			$keys = array();
			if (isset($class['object_class_id'])) {
				$classFields = array_keys($fieldModel->getObjectFieldsInClass(
					$class['object_class_id'], $class['addon_id']
				));
				if ($class['required_fields']) {
				    $requiredFields = unserialize($class['required_fields']);
				}

				if ($class['keys']) {
				    $keys = unserialize($class['keys']);
				}
			}
			$response->params['fieldGroups'] = $fieldModel->getObjectFieldsByAddOns();
			$response->params['fieldOptions'] = $fieldModel->getObjectFieldOptions();
			$response->params['requiredFields'] = ($requiredFields ? $requiredFields : array(0));
			$response->params['keys'] = ($keys ? $keys : array(0));
			$response->params['classFields'] = ($classFields ? $classFields : array(0));
			$response->params['customFields'] = $fieldModel->prepareObjectFields(
				$fieldModel->getObjectFieldsInAddOn($class['addon_id']),
				true,
				(isset($class['custom_fields']) && $class['custom_fields'] ? unserialize($class['custom_fields']) : array()),
				true
			);
		}

		return $response;
	}

	public function actionSave()
	{
		$GLOBALS['ThemeHouse_ObjectFields_ControllerAdmin_Class'] = $this;

		return parent::actionSave();
	}

	public function processCustomFieldValues(ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Class $writer)
	{
		$customFields = $this->_input->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
		$customFieldsShown = $this->_input->filterSingle('custom_fields_shown', XenForo_Input::STRING, array('array' => true));
		$writer->setCustomFields($customFields, $customFieldsShown);

		$requiredFields = $this->_input->filterSingle('required_fields', XenForo_Input::ARRAY_SIMPLE);
		$writer->set('required_fields', serialize($requiredFields));

		$keys = $this->_input->filterSingle('keys', XenForo_Input::ARRAY_SIMPLE);
		$writer->set('keys', serialize($keys));

		$uniqueKeys = $this->_input->filterSingle('unique_keys', XenForo_Input::ARRAY_SIMPLE);
		$writer->set('unique_keys', serialize($uniqueKeys));

		$input = $this->_input->filter(array(
			'primary_key' => XenForo_Input::STRING,
			'title_field' => XenForo_Input::STRING,
			'subtitle_field' => XenForo_Input::STRING,
		));

		$writer->bulkSet($input);

		unset($GLOBALS['ThemeHouse_ObjectFields_ControllerAdmin_Class']);
	}

	public function processCustomFields(ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Class $writer)
	{
		$fieldIds = $this->_input->filterSingle('available_fields', XenForo_Input::STRING, array('array' => true));
		$this->_getFieldModel()->updateObjectFieldClassAssociationByClass($writer->get('object_class_id'), $fieldIds);
		unset($GLOBALS['ThemeHouse_ObjectFields_ControllerAdmin_Class']);
	}

	/**
	 * Validate a single field
	 *
	 * @return XenForo_ControllerResponse_View|XenForo_ControllerResponse_Error
	 */
	public function actionValidateField()
	{
		$this->_assertPostOnly();

		$field = $this->_getFieldValidationInputParams();

		if (preg_match('/^custom_field_([a-zA-Z0-9_]+)$/', $field['name'], $match)) {
			$writer = XenForo_DataWriter::create('ThemeHouse_ObjectFields_DataWriter_Class');

			$writer->setOption(XenForo_DataWriter_User::OPTION_ADMIN_EDIT, true);

			$writer->setCustomFields(array($match[1] => $field['value']));

			$errors = $writer->getErrors();
			if ($errors) {
				return $this->responseError($errors);
			}

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					'',
					new XenForo_Phrase('redirect_field_validated', array('name' => $field['name'], 'value' => $field['value']))
			);
		} else {
			// handle normal fields
			return parent::actionValidateField();
		}
	}

	/**
	 * @return ThemeHouse_ObjectFields_Model_ObjectField
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('ThemeHouse_ObjectFields_Model_ObjectField');
	}
}