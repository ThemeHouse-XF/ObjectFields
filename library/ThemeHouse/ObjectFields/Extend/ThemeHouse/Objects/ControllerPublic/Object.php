<?php

class ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_ControllerPublic_Object extends XFCP_ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_ControllerPublic_Object
{
	/**
	 * Displays a form to create a new thread in this post.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEdit()
	{
		$response = parent::actionEdit();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$classId = $response->params['object']['class_id'];

			$classRequiredFields = array();
			if ($response->params['class']['required_fields']) $classRequiredFields = unserialize($response->params['class']['required_fields']);

			$fieldValues = array();
			if (isset($response->params['object']['custom_fields']) && $response->params['object']['custom_fields'])
			{
				$fieldValues = unserialize($response->params['object']['custom_fields']);
			}

			$response->params['customFields'] = $this->_getFieldModel()->prepareGroupedObjectFields(
				$this->_getFieldModel()->getUsableObjectFieldsInClasses(array($classId)),
				true,
				$fieldValues,
				true,
				$classRequiredFields
			);
		}

		return $response;
	}

	public function actionSave()
	{
		$GLOBALS['ThemeHouse_Objects_ControllerPublic_Object'] = $this;

		return parent::actionSave();
	}

	/**
	 * @return ThemeHouse_ObjectFields_Model_ObjectField
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('ThemeHouse_ObjectFields_Model_ObjectField');
	}
}