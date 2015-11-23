<?php

/**
 * Exports an object field as XML.
 */
class ThemeHouse_ObjectFields_ViewAdmin_ObjectField_Export extends XenForo_ViewAdmin_Base
{
	public function renderXml()
	{
		$this->setDownloadFileName('field-' . $this->_params['field']['field_id'] . '.xml');
		return $this->_params['xml']->saveXml();
	}
}