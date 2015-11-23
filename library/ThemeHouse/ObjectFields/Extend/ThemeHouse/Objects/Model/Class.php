<?php

class ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_Model_Class extends XFCP_ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_Model_Class
{
	protected $_importDocument;

	public function getAllClasses()
	{
		try {
			$classes = XenForo_Application::get('classes');
		} catch (Exception $e) {
			$classes = XenForo_Model::create('XenForo_Model_DataRegistry')->get('classes');
		}
		if (!is_array($classes))
		{
			$classes = XenForo_Model::create('ThemeHouse_Objects_Model_Class')->rebuildClassCache();
		}
		XenForo_Application::set('classes', $classes);

		return $classes;
	}

	/**
	 * @return array
	 */
	public function rebuildClassCache()
	{
		$this->resetLocalCacheData('classes');
		$classes = $this->getClasses(array(), array('join' => self::FETCH_ADDON, 'order' => 'addon_title'));
		XenForo_Model::create('XenForo_Model_DataRegistry')->set('classes', $classes);

		return $classes;
	}

	public function rebuildKeyColumns()
	{
		$db = $this->_getDb();

		$maxKey = $this->getMaxKey();

		$addKeys = array();
		for ($i=0; $i < $maxKey; $i++)
		{
			$addKeys[$i] = true;
		}

		$describeTable = $db->describeTable('xf_object');
		$keys = array_keys($describeTable);

		$dropKeys = array();
		foreach ($keys as $key)
		{
			preg_match('#key_([0-9]+)#', $key, $matches);
			if (!empty($matches))
			{
				if ($key > $maxKey)
				{
					$dropKeys[$matches[1]] = true;
				}
				else
				{
					unset($addKeys[$matches[1]]);
				}
			}
		}

		if (!empty($addKeys) || !empty($dropKeys))
		{
			$sql = "ALTER TABLE `xf_object` ";
			$sqlAdd = array();
			foreach ($addKeys as $key => $null)
			{
				$sqlAdd[] = "ADD `key_" . $key . "` MEDIUMTEXT";
			}
			foreach ($dropKeys as $dropKey => $null)
			{
				$sqlAdd[] = "DROP `key_".$dropKey."";
			}
			$sql .= implode(", ", $sqlAdd);
			$db->query($sql);
		}
	}

	public function getMaxKey()
	{
		$classes = $this->getAllClasses();

		$maxKey = 0;
		foreach ($classes as $class)
		{
			if ($class['keys'])
			{
				$keys = unserialize($class['keys']);
				if (count($keys) > $maxKey)
				{
					$maxKey = count($keys);
				}
			}
		}

		return $maxKey;
	}

	/**
	 * @param DOMElement $rootNode
	 * @param array $class
	 */
	protected function _appendClassXml(DOMElement $rootNode, $class)
	{
		parent::_appendClassXml($rootNode, $class);

		$document = $rootNode->ownerDocument;

		$customFieldsNode = $document->createElement('custom_fields');
		$rootNode->appendChild($customFieldsNode);

		$availableFields = array();
		if ($class['custom_fields'])
		{
			$defaultValues = unserialize($class['custom_fields']);
		}

		$requiredFields = array();
		if ($class['required_fields'])
		{
			$requiredFields = unserialize($class['required_fields']);
		}

		$keys = array();
		if ($class['keys'])
		{
			$keys = unserialize($class['keys']);
		}

		$uniqueKeys = array();
		if ($class['unique_keys'])
		{
			$uniqueKeys = unserialize($class['unique_keys']);
		}

		if ($class['field_cache'])
		{
			$fieldCache = unserialize($class['field_cache']);

			foreach ($fieldCache as $customFieldGroupId => $customFields)
			{
				foreach ($customFields as $customField)
				{
					$customFieldNode = $document->createElement('custom_field');
					$customFieldNode->setAttribute('is_primary', ($customField == $class['primary_key']));
					$customFieldNode->setAttribute('is_subtitle', ($customField == $class['subtitle_field']));
					$customFieldNode->setAttribute('is_title', ($customField == $class['title_field']));
					$customFieldNode->setAttribute('is_unique', in_array($customField, $uniqueKeys));
					$customFieldNode->setAttribute('is_key', in_array($customField, $keys));
					$customFieldNode->setAttribute('is_required', in_array($customField, $requiredFields));
					$customFieldNode->setAttribute('field_id', $customField);
					$customFieldsNode->appendChild($customFieldNode);
				}
			}
		}
	}

	/**
	 * @return array List of cache rebuilders to run
	 */
	public function importClassXml(SimpleXMLElement $document)
	{
		$this->_importDocument = $document;

		$GLOBALS['ThemeHouse_ObjectFields_Model_Class'] = $this;

		return parent::importClassXml($document);
	}

	public function processCustomFieldValues(ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Class $dw)
	{
		/* @var $document SimpleXMLElement */
		$document = $this->_importDocument;

		$customFields = XenForo_Helper_DevelopmentXml::fixPhpBug50670($document->custom_fields->custom_field);

		$requiredFields = array();
		$keys = array();
		$uniqueKeys = array();
		$customFieldsShown = array();
		foreach ($customFields as $customField)
		{
			if ($customField && $customField['field_id'])
			{
				$fieldId = (string)$customField['field_id'];
				$customFieldsShown[] = $fieldId;
				if ((int)$customField['is_required'] == 1)
				{
					$requiredFields[] = $fieldId;
				}
				if ((int)$customField['is_key'] == 1)
				{
					$keys[] = $fieldId;
				}
				if ((int)$customField['is_unique'] == 1)
				{
					$uniqueKeys[] = $fieldId;
				}
				if ((int)$customField['is_title'] == 1)
				{
					$dw->set('title_field', $fieldId);
				}
				if ((int)$customField['is_subtitle'] == 1)
				{
					$dw->set('subtitle_field', $fieldId);
				}
				if ((int)$customField['is_primary'] == 1)
				{
					$dw->set('primary_key', $fieldId);
				}
			}
		}
		$customFields = array();
		if ($dw->get('custom_fields'))
		{
			$customFields = unserialize($dw->get('custom_fields'));
		}
		$dw->setCustomFields($customFields, $customFieldsShown);
		$dw->set('required_fields', $requiredFields);
		$dw->set('keys', $keys);
		$dw->set('unique_keys', $uniqueKeys);
	}

	public function processCustomFields(ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Class $dw)
	{
		/* @var $document SimpleXMLElement */
		$document = $this->_importDocument;

		$customFields = XenForo_Helper_DevelopmentXml::fixPhpBug50670($document->custom_fields->custom_field);
		$availableFields = array();
		foreach ($customFields as $customField)
		{
			if ($customField && $customField['field_id'])
			{
				$availableFields[] = (string)$customField['field_id'];
			}
		}
		$this->_getFieldModel()->updateObjectFieldClassAssociationByClass($dw->get('object_class_id'), $availableFields);
	}

	/**
	 * @return ThemeHouse_ObjectFields_Model_ObjectField
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('ThemeHouse_ObjectFields_Model_ObjectField');
	}
}