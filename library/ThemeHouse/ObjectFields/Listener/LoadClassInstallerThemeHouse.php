<?php

class ThemeHouse_ObjectFields_Listener_LoadClassInstallerThemeHouse extends ThemeHouse_Listener_LoadClass
{
	/**
	 * Gets the classes that are extended for this add-on. See parent for explanation.
	 *
	 * @return array
	 */
	protected function _getExtends()
	{
		return array(
		    'ThemeHouse_Objects_Install' => 'ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_Install',
		);
	} /* END ThemeHouse_ObjectFields_Listener_LoadClassInstallerThemeHouse::_getExtends */

	public static function loadClassInstallerThemeHouse($class, array &$extend)
	{
		$loadClassInstallerThemeHouse = new ThemeHouse_ObjectFields_Listener_LoadClassInstallerThemeHouse($class, $extend);
		$extend = $loadClassInstallerThemeHouse->run();
	} /* END ThemeHouse_ObjectFields_Listener_LoadClassInstallerThemeHouse::loadClassInstallerThemeHouse */
}