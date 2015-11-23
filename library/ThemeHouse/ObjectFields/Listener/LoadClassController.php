<?php

class ThemeHouse_ObjectFields_Listener_LoadClassController extends ThemeHouse_Listener_LoadClass
{
	/**
	 * Gets the classes that are extended for this add-on. See parent for explanation.
	 *
	 * @return array
	 */
	protected function _getExtends()
	{
		return array(
			'ThemeHouse_Objects_ControllerAdmin_Class' => 'ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_ControllerAdmin_Class',
			'ThemeHouse_Objects_ControllerAdmin_Object' => 'ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_ControllerAdmin_Object',
			'ThemeHouse_Objects_ControllerPublic_Object' => 'ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_ControllerPublic_Object',
			'ThemeHouse_Objects_ControllerPublic_Class' => 'ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_ControllerPublic_Class',
		);
	} /* END ThemeHouse_ObjectFields_Listener_LoadClassController::_getExtends */

	public static function loadClassController($class, array &$extend)
	{
		$loadClassController = new ThemeHouse_ObjectFields_Listener_LoadClassController($class, $extend);
		$extend = $loadClassController->run();
	} /* END ThemeHouse_ObjectFields_Listener_LoadClassController::loadClassController */
}