<?php

/**
 * Controller for managing custom object fields.
 */
class ThemeHouse_ObjectFields_ControllerAdmin_ObjectField extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('object');
	}

	/**
	 * Displays a list of custom object fields.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionIndex()
	{
		$fieldModel = $this->_getFieldModel();
		$addOnModel = $this->_getAddOnModel();

		$addOns = $addOnModel->getAllAddOns();

		$addOnTitles = array();
		foreach ($addOns as $addOnId => $addOn) {
			$addOnTitles[$addOnId] = $addOn['title'];
		}

		$fieldCount = 0;
		$addOns = $fieldModel->getObjectFieldsByAddOns(array(), array(), $fieldCount);

		$viewParams = array(
			'addOns' => $addOns,
			'fieldCount' => $fieldCount,
			'addOnTitles' => $addOnTitles,
			'fieldTypes' => $fieldModel->getObjectFieldTypes(),
		);

		return $this->responseView('ThemeHouse_ObjectFields_ViewAdmin_ObjectField_List', 'object_field_list', $viewParams);
	}

	/**
	 * Gets the add/edit form response for a field.
	 *
	 * @param array $field
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	protected function _getFieldAddEditResponse(array $field,
		$viewName = 'ThemeHouse_ObjectFields_ViewAdmin_ObjectField_Edit',
		$templateName = 'object_field_edit',
		$viewParams = array()
	) {
		$userGroups = $this->_getUserGroupModel()->getAllUserGroups();

		$fieldModel = $this->_getFieldModel();

		$typeMap = $fieldModel->getObjectFieldTypeMap();
		$validFieldTypes = $fieldModel->getObjectFieldTypes();

		if ((isset($field['field_choices_callback_class']) && $field['field_choices_callback_class'])
			&& (isset($field['field_choices_callback_method']) && $field['field_choices_callback_method'])
		) {
			$field['choice_type'] = "callback";
		} else {
			$field['choice_type'] = "custom";
		}

		if (!empty($field['field_id'])) {
			$selClassIds = $fieldModel->getClassAssociationsByObjectField($field['field_id'], $field['addon_id']);

			$selUserGroupIds = explode(',', $field['allowed_user_group_ids']);
			if (in_array(-1, $selUserGroupIds)) {
				$allUserGroups = true;
				$selUserGroupIds = array_keys($userGroups);
			} else {
				$allUserGroups = false;
			}

			$masterTitle = $fieldModel->getObjectFieldMasterTitlePhraseValue($field['field_id']);
			$masterDescription = $fieldModel->getObjectFieldMasterDescriptionPhraseValue($field['field_id']);

			$existingType = $typeMap[$field['field_type']];
			foreach ($validFieldTypes AS $typeId => $type) {
				if ($typeMap[$typeId] != $existingType) {
					unset($validFieldTypes[$typeId]);
				}
			}
		} else {
			$selClassIds = array();
			$allUserGroups = true;
			$selUserGroupIds = array_keys($userGroups);
			$masterTitle = '';
			$masterDescription = '';
			$existingType = false;
		}

		if (!$selClassIds) {
			$selClassIds = array(0);
		}

		$addOnModel = $this->_getAddOnModel();

		$viewParams = array_merge(array(
			'field' => $field,
			'fieldGroupOptions' => $fieldModel->getObjectFieldGroupOptions($field['field_group_id']),

			'selClassIds' => $selClassIds,
			'allUserGroups' => $allUserGroups,
			'selUserGroupIds' => $selUserGroupIds,
			'masterTitle' => $masterTitle,
			'masterDescription' => $masterDescription,
			'masterFieldChoices' => $fieldModel->getObjectFieldChoices($field['field_id'], $field['field_choices'], true),

			'validFieldTypes' => $validFieldTypes,
			'fieldTypeMap' => $typeMap,
			'existingType' => $existingType,

			'userGroups' => $userGroups,

			'addOnOptions' => $addOnModel->getAddOnOptionsListIfAvailable(),
			'addOnSelected' => (isset($field['addon_id']) ? $field['addon_id'] : $addOnModel->getDefaultAddOnId()),

			'classes' => $this->_getClassModel()->getClasses(isset($field['addon_id']) ? array('addon_id' => $field['addon_id']) : array())
		), $viewParams);

		return $this->responseView($viewName, $templateName, $viewParams);
	}

	/**
	 * Displays form to add a custom object field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionAdd()
	{
		return $this->_getFieldAddEditResponse(array(
			'field_id' => null,
			'field_group_id' => '0',
			'display_order' => 1,
			'field_type' => 'textbox',
			'match_type' => 'none',
			'max_length' => 0,
			'field_choices' => '',
		));
	}

	/**
	 * Displays form to edit a custom object field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEdit()
	{
		$field = $this->_getFieldOrError(
			$this->_input->filterSingle('field_id', XenForo_Input::STRING),
			$this->_input->filterSingle('addon_id', XenForo_Input::STRING)
		);
		return $this->_getFieldAddEditResponse($field);
	}

	/**
	 * Saves a custom object field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionSave()
	{
		$fieldId = $this->_input->filterSingle('field_id', XenForo_Input::STRING);
		$addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);

		$newFieldId = $this->_input->filterSingle('new_field_id', XenForo_Input::STRING);
		$dwInput = $this->_input->filter(array(
			'field_group_id' => XenForo_Input::UINT,
			'display_order' => XenForo_Input::UINT,
			'field_type' => XenForo_Input::STRING,
			'match_type' => XenForo_Input::STRING,
			'match_regex' => XenForo_Input::STRING,
			'match_callback_class' => XenForo_Input::STRING,
			'match_callback_method' => XenForo_Input::STRING,
			'max_length' => XenForo_Input::UINT,
			'field_choices_callback_class' => XenForo_Input::STRING,
			'field_choices_callback_method' => XenForo_Input::STRING,
			'field_callback_class' => XenForo_Input::STRING,
			'field_callback_method' => XenForo_Input::STRING,
			'pre_save_callback_class' => XenForo_Input::STRING,
			'pre_save_callback_method' => XenForo_Input::STRING,
			'post_save_callback_class' => XenForo_Input::STRING,
			'post_save_callback_method' => XenForo_Input::STRING,
			'export_callback_class' => XenForo_Input::STRING,
			'export_callback_method' => XenForo_Input::STRING,
		));

		$input = $this->_input->filter(array(
			'usable_user_group_type' => XenForo_Input::STRING,
			'user_group_ids' => array(XenForo_Input::UINT, 'array' => true),
			'class_ids' => array(XenForo_Input::STRING, 'array' => true),
		));

		if ($input['usable_user_group_type'] == 'all') {
			$allowedGroupIds = array(-1); // -1 is a sentinel for all groups
		} else {
			$allowedGroupIds = $input['user_group_ids'];
		}

		$dw = XenForo_DataWriter::create('ThemeHouse_ObjectFields_DataWriter_ObjectField');
		if ($fieldId) {
			$dw->setExistingData(array($fieldId, $addOnId));
		} else {
			$dw->set('field_id', $newFieldId);
			$dw->set('addon_id', $addOnId);
		}

		$dw->bulkSet($dwInput);

		$dw->set('allowed_user_group_ids', $allowedGroupIds);

		$dw->setExtraData(
			ThemeHouse_ObjectFields_DataWriter_ObjectField::DATA_TITLE,
			$this->_input->filterSingle('title', XenForo_Input::STRING)
		);
		$dw->setExtraData(
			ThemeHouse_ObjectFields_DataWriter_ObjectField::DATA_DESCRIPTION,
			$this->_input->filterSingle('description', XenForo_Input::STRING)
		);

		$fieldChoices = $this->_input->filterSingle('field_choice', XenForo_Input::STRING, array('array' => true));
		$fieldChoicesText = $this->_input->filterSingle('field_choice_text', XenForo_Input::STRING, array('array' => true));
		$fieldChoicesCombined = array();
		foreach ($fieldChoices AS $key => $choice) {
			if (isset($fieldChoicesText[$key])) {
				$fieldChoicesCombined[$choice] = $fieldChoicesText[$key];
			}
		}

		$dw->setFieldChoices($fieldChoicesCombined);

		$dw->save();

		$objectClassIds = array_keys($this->_getClassModel()->getClasses(array('addon_id' => $addOnId, 'class_ids' => $input['class_ids'])));
		$this->_getFieldModel()->updateObjectFieldClassAssociationByObjectField($dw->get('field_id'), $dw->get('addon_id'), $objectClassIds);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('object-fields', array(), array('addon_id' => $addOnId)) . $this->getLastHash($dw->get('field_id'))
		);
	}

	/**
	 * Deletes a custom object field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDelete()
	{
		if ($this->isConfirmedPost()) {
		    $fieldId = $this->_input->filterSingle('field_id', XenForo_Input::STRING);
	        $addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);

		    return $this->_deleteData(
				'ThemeHouse_ObjectFields_DataWriter_ObjectField', array($fieldId, $addOnId),
				XenForo_Link::buildAdminLink('object-fields')
			);
		} else {
			$field = $this->_getFieldOrError(
				$this->_input->filterSingle('field_id', XenForo_Input::STRING),
				$this->_input->filterSingle('addon_id', XenForo_Input::STRING)
			);

			$viewParams = array(
				'field' => $field
			);

			return $this->responseView('ThemeHouse_ObjectFields_ViewAdmin_ObjectField_Delete', 'object_field_delete', $viewParams);
		}
	}

	public function actionExport()
	{
		$fieldId = $this->_input->filterSingle('field_id', XenForo_Input::STRING);
		$addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);
		$field = $this->_getFieldOrError($fieldId, $addOnId);

		$this->_routeMatch->setResponseType('xml');

		$viewParams = array(
			'field' => $field,
			'xml' => $this->_getFieldModel()->getFieldXml($field)
		);

		return $this->responseView('ThemeHouse_ObjectFields_ViewAdmin_ObjectField_Export', '', $viewParams);
	}

	public function actionImport()
	{
		$fieldModel = $this->_getFieldModel();

		if ($this->isConfirmedPost()) {
			$input = $this->_input->filter(array(
				'target' => XenForo_Input::STRING,
				'field_group_id' => XenForo_Input::UINT,
				'overwrite_field_id' => XenForo_Input::STRING
			));

			$upload = XenForo_Upload::getUploadedFile('upload');
			if (!$upload) {
				return $this->responseError(new XenForo_Phrase('please_upload_valid_field_xml_file'));
			}

			if ($input['target'] == 'overwrite') {
				$field = $this->_getFieldOrError($input['overwrite_field_id'], $input['overwrite_addon_id']);
				$input['field_group_id'] = $field['field_group_id'];
			} else {
				$input['overwrite_field_id'] = '';
			}

			$document = $this->getHelper('Xml')->getXmlFromFile($upload);
			$caches = $fieldModel->importFieldXml($document, $input['field_group_id'], $input['overwrite_field_id']);

			return XenForo_CacheRebuilder_Abstract::getRebuilderResponse($this, $caches, XenForo_Link::buildAdminLink('object-fields'));
		} else {
			$fieldModel = $this->_getFieldModel();
			$viewParams = array(
				'fieldGroupOptions' => $fieldModel->getObjectFieldGroupOptions(),
				'fields' => $fieldModel->prepareObjectFields($fieldModel->getObjectFields()),
			);

			return $this->responseView('ThemeHouse_ObjectFields_ViewAdmin_ObjectField_Import', 'object_field_import', $viewParams);
		}
	}

	public function actionQuickSet()
	{
		$this->_assertPostOnly();

		$fieldIds = $this->_input->filterSingle('field_ids', XenForo_Input::STRING, array('array' => true));

		if (empty($fieldIds)) {
			// nothing to do, just head back to the field list
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('object-fields')
			);
		}

		$fieldModel = $this->_getFieldModel();

		if ($this->isConfirmedPost()) {
			$input = $this->_input->filter(array(
				'apply_field_group_id' => XenForo_Input::UINT,
				'field_group_id' => XenForo_Input::UINT,

				'apply_user_group_ids' => XenForo_Input::UINT,
				'usable_user_group_type' => XenForo_Input::STRING,
				'user_group_ids' => array(XenForo_Input::UINT, 'array' => true),

				'apply_class_ids' => XenForo_Input::UINT,
				'class_ids' => array(XenForo_Input::STRING, 'array' => true),

				'field_id' => XenForo_Input::UINT,
			));

			XenForo_Db::beginTransaction();

			$fieldChanged = false;
			$orderChanged = false;
			foreach ($fieldIds AS $fieldId) {
				$dw = XenForo_DataWriter::create('ThemeHouse_ObjectFields_DataWriter_ObjectField');
				$dw->setOption(ThemeHouse_ObjectFields_DataWriter_ObjectField::OPTION_MASS_UPDATE, true);
				$dw->setExistingData($fieldId);

				if ($input['apply_field_group_id']) {
					$dw->set('field_group_id', $input['field_group_id']);
					if ($dw->isChanged('field_group_id')) {
						$orderChanged = true;
					}
				}

				if ($input['apply_user_group_ids']) {
					if ($input['usable_user_group_type'] == 'all') {
						$allowedGroupIds = array(-1); // -1 is a sentinel for all groups
					} else {
						$allowedGroupIds = $input['user_group_ids'];
					}

					$dw->set('allowed_user_group_ids', $allowedGroupIds);
				}

				$dw->save();

				if ($input['apply_class_ids']) {
					$this->_getFieldModel()->updateObjectFieldClassAssociationByObjectField($dw->get('field_id'), $dw->get('addon_id'), $input['class_ids']);
				}
			}

			if ($orderChanged) {
				$fieldModel->rebuildObjectFieldMaterializedOrder();
			}

			$fieldModel->rebuildObjectFieldCache();

			XenForo_Db::commit();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('object-fields') . $this->getLastHash($input['field_id'])
			);

		} else {
			if ($fieldId = $this->_input->filterSingle('field_id', XenForo_Input::STRING)) {
				if ($fieldId) {
					$addOnId = $this->_input->filterSingle('field_id', XenForo_Input::STRING);
					$field = $this->_getFieldOrError($fieldId, $addOnId);
				} else {
					$field = $fieldModel->getDefaultObjectFieldValues();
				}

				$fields = $fieldModel->getObjectFields(array('field_ids' => $fieldIds));

				$viewParams = array(
					'fieldIds' => $fieldIds,
					'fields' => $fieldModel->prepareObjectFields($fields),
				);

				return $this->_getFieldAddEditResponse($field,
					'ThemeHouse_ObjectFields_ViewAdmin_ObjectField_QuickSet_Editor',
					'object_field_quickset_editor',
					$viewParams);
			} else {
				$viewParams = array(
					'fieldIds' => $fieldIds,
					'fieldOptions' => $fieldModel->getObjectFieldOptions(array('field_ids' => $fieldIds))
				);

				return $this->responseView(
					'ThemeHouse_ObjectFields_ViewAdmin_ObjectField_QuickSet_FieldChooser',
					'object_field_quickset_field_chooser',
					$viewParams
			    );
			}
		}
	}

	public function actionGroups()
	{
		$fieldGroups = $this->_getFieldModel()->getAllObjectFieldGroups();

		$viewParams = array(
			'fieldGroups' => $this->_getFieldModel()->prepareObjectFieldGroups($fieldGroups)
		);

		return $this->responseView('ThemeHouse_ObjectFields_ViewAdmin_ObjectField_Group_List', 'object_field_group_list', $viewParams);
	}

	protected function _getFieldGroupAddEditResponse(array $fieldGroup)
	{
		if (!empty($fieldGroup['field_group_id'])) {
			$masterTitle = $this->_getPhraseModel()->getMasterPhraseValue(
				$this->_getFieldModel()->getObjectFieldGroupTitlePhraseName($fieldGroup['field_group_id'])
			);
		} else {
			$masterTitle = '';
		}

		$viewParams = array(
			'fieldGroup' => $fieldGroup,
			'masterTitle' => $masterTitle
		);

		return $this->responseView('ThemeHouse_ObjectFields_ViewAdmin_ObjectField_Group_Edit', 'object_field_group_edit', $viewParams);
	}

	public function actionAddGroup()
	{
		return $this->_getFieldGroupAddEditResponse(array(
			'display_order' => 1
		));
	}

	public function actionEditGroup()
	{
		$fieldGroupId = $this->_input->filterSingle('field_group_id', XenForo_Input::UINT);
		$fieldGroup = $this->_getFieldGroupOrError($fieldGroupId);

		return $this->_getFieldGroupAddEditResponse($fieldGroup);
	}

	public function actionSaveGroup()
	{
		$this->_assertPostOnly();

		$fieldGroupId = $this->_input->filterSingle('field_group_id', XenForo_Input::UINT);

		$input = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT
		));

		$dw = XenForo_DataWriter::create('ThemeHouse_ObjectFields_DataWriter_ObjectFieldGroup');
		if ($fieldGroupId) {
			$dw->setExistingData($fieldGroupId);
		}
		$dw->set('display_order', $input['display_order']);
		$dw->setExtraData(ThemeHouse_ObjectFields_DataWriter_ObjectField::DATA_TITLE, $input['title']);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('object-fields') . $this->getLastHash('group_' . $dw->get('field_group_id'))
		);
	}

	public function actionDeleteGroup()
	{
		$fieldGroupId = $this->_input->filterSingle('field_group_id', XenForo_Input::UINT);

		if ($this->isConfirmedPost()) {
			$dw = XenForo_DataWriter::create('ThemeHouse_ObjectFields_DataWriter_ObjectFieldGroup');
			$dw->setExistingData($fieldGroupId);
			$dw->delete();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('object-fields'));
		} else {
			$viewParams = array(
				'fieldGroup' => $this->_getFieldGroupOrError($fieldGroupId)
			);

			return $this->responseView(
				'ThemeHouse_ObjectFields_ViewAdmin_ObjectField_Group_Delete',
				'object_field_group_delete', $viewParams
			);
		}
	}

	/**
	 * Gets a valid field group or throws an exception.
	 *
	 * @param integer $fieldGroupId
	 *
	 * @return array
	 */
	protected function _getFieldGroupOrError($fieldGroupId)
	{
		$info = $this->_getFieldModel()->getObjectFieldGroupById($fieldGroupId);
		if (!$info) {
			throw $this->responseException($this->responseError(new XenForo_Phrase('requested_object_field_group_not_found'), 404));
		}

		return $this->_getFieldModel()->prepareObjectFieldGroup($info);
	}

	/**
	 * Gets the specified field or throws an exception.
	 *
	 * @param string $fieldId
	 * @param string $addOnId
	 *
	 * @return array
	 */
	protected function _getFieldOrError($fieldId, $addOnId)
	{
		$field  = $this->_getFieldModel()->getObjectFieldInAddOnById($fieldId, $addOnId);
		if (!$field) {
			throw $this->responseException($this->responseError(new XenForo_Phrase('requested_field_not_found'), 404));
		}

		return $this->_getFieldModel()->prepareObjectField($field);
	}

	/**
	 * @return XenForo_Model_AddOn
	 */
	protected function _getAddOnModel()
	{
		return $this->getModelFromCache('XenForo_Model_AddOn');
	}

	/**
	 * @return ThemeHouse_ObjectFields_Model_ObjectField
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('ThemeHouse_ObjectFields_Model_ObjectField');
	}

	/**
	 * @return ThemeHouse_Objects_Model_Class
	 */
	protected function _getClassModel()
	{
		return $this->getModelFromCache('ThemeHouse_Objects_Model_Class');
	}

	/**
	 * @return XenForo_Model_UserGroup
	 */
	protected function _getUserGroupModel()
	{
		return $this->getModelFromCache('XenForo_Model_UserGroup');
	}

	/**
	 * @return XenForo_Model_Phrase
	 */
	protected function _getPhraseModel()
	{
		return $this->getModelFromCache('XenForo_Model_Phrase');
	}
}