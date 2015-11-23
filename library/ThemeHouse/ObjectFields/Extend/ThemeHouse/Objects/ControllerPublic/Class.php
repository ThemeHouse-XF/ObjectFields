<?php

class ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_ControllerPublic_Class extends XFCP_ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_ControllerPublic_Class
{
	public function actionAdd()
	{
		$response = parent::actionAdd();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$class = $response->params['class'];

			$classRequiredFields = array();
			if ($class['required_fields']) $classRequiredFields = unserialize($response->params['class']['required_fields']);

			$fieldValues = array();
			if (isset($class['custom_fields']) && $class['custom_fields'])
			{
				$fieldValues = unserialize($class['custom_fields']);
			}

			$response->params['customFields'] = $this->_getFieldModel()->prepareGroupedObjectFields(
				$this->_getFieldModel()->getUsableObjectFieldsInClasses(array($class['class_id'])),
				true,
				$fieldValues,
				true,
				$classRequiredFields
			);
		}

		return $response;
	}

	/**
	 * @return ThemeHouse_ObjectFields_Model_ObjectField
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('ThemeHouse_ObjectFields_Model_ObjectField');
	}
}