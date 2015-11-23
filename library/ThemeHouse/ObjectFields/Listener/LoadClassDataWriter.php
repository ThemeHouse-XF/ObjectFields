<?php

class ThemeHouse_ObjectFields_Listener_LoadClassDataWriter extends ThemeHouse_Listener_LoadClass
{
    /**
     * Gets the classes that are extended for this add-on. See parent for explanation.
     *
     * @return array
     */
    protected function _getExtends()
    {
        return array(
            'ThemeHouse_Objects_DataWriter_Class' => 'ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Class',
            'ThemeHouse_Objects_DataWriter_Object' => 'ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Object',
        );
    } /* END ThemeHouse_ObjectFields_Listener_LoadClassDataWriter::_getExtends */

    public static function loadClassDataWriter($class, array &$extend)
    {
        $loadClassDataWriter = new ThemeHouse_ObjectFields_Listener_LoadClassDataWriter($class, $extend);
        $extend = $loadClassDataWriter->run();
    } /* END ThemeHouse_ObjectFields_Listener_LoadClassDataWriter::loadClassDataWriter */
}