<?php

class ThemeHouse_ObjectFields_Listener_LoadClassModel extends ThemeHouse_Listener_LoadClass
{
    /**
     * Gets the classes that are extended for this add-on. See parent for explanation.
     *
     * @return array
     */
    protected function _getExtends()
    {
        return array(
            'ThemeHouse_Objects_Model_Class' => 'ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_Model_Class',
            'ThemeHouse_Objects_Model_Object' => 'ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_Model_Object',
        );
    } /* END ThemeHouse_ObjectFields_Listener_LoadClassModel::_getExtends */

    public static function loadClassModel($class, array &$extend)
    {
        $loadClassModel = new ThemeHouse_ObjectFields_Listener_LoadClassModel($class, $extend);
        $extend = $loadClassModel->run();
    } /* END ThemeHouse_ObjectFields_Listener_LoadClassModel::loadClassModel */
}