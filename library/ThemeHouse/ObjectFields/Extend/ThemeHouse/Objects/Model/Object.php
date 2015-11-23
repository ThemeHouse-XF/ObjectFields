<?php

class ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_Model_Object extends XFCP_ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_Model_Object
{
	protected $_joinClasses = array();

	protected function _prepareWhereClause($whereClause)
	{
		$sqlConditions = explode("AND", $whereClause);
		foreach ($sqlConditions as &$sqlCondition)
		{
			preg_match("#\s*\(((?:\w*\.)?\w*)\s*(=|IN)\s*(.*)\)\s*#", $sqlCondition, $matches);
			if (!empty($matches))
			{
				$sqlCondition = "(" . $this->_convertFieldName($matches[1]) . " " . $matches[2] . " " . $matches[3] . ")";
			}
		}

		return implode("AND", $sqlConditions);
	}

	/**
	 * Gets objects that match the specified criteria.
	 *
	 * @param string $classId
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array [object id] => info
	 */
	public function get($classId, array $conditions = array(), array $fetchOptions = array())
	{
		$className = str_replace(" ", "", ucwords(str_replace("_", " ", $classId)));

		$whereClause = call_user_func_array(array($this, 'prepare' . $className . 'Conditions'), array($conditions, &$fetchOptions));
		$joinOptions = call_user_func_array(array($this, 'prepare' . $className . 'FetchOptions'), array(&$fetchOptions));

		$whereClause = $this->_prepareWhereClause($whereClause);

		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->prepareObjects($this->fetchAllKeyed($this->limitQueryResults('
				SELECT ' . $classId . '.*
				' . $joinOptions['selectFields'] . '
				FROM xf_object AS ' . $classId . '
				' . $joinOptions['joinTables'] . '
				WHERE ' . $classId . '.class_id = ? AND ' . $whereClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'object_id', $classId));
	}

	/**
	 * Gets objects that match the specified criteria.
	 *
	 * @param string $classId
	 * @param string $objectId
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array [object id] => info
	 */
	public function getById($classId, $objectId, array $conditions = array(), array $fetchOptions = array())
	{
		$className = str_replace(" ", "", ucwords(str_replace("_", " ", $classId)));

		$whereClause = call_user_func_array(array($this, 'prepare' . $className . 'Conditions'), array($conditions, &$fetchOptions));
		$joinOptions = call_user_func_array(array($this, 'prepare' . $className . 'FetchOptions'), array(&$fetchOptions));

		$whereClause = $this->_prepareWhereClause($whereClause);

		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->prepareObject($this->_getDb()->fetchRow($this->limitQueryResults('
				SELECT ' . $classId . '.*
				' . $joinOptions['selectFields'] . '
				FROM xf_object AS ' . $classId . '
				' . $joinOptions['joinTables'] . '
				WHERE object_id = ? AND ' . $whereClause . '
				', $limitOptions['limit'], $limitOptions['offset']
		), $objectId));
	}

	public function prepareFetchOptions($classId, array &$fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$orderBy = '';

		$classModel = $this->_getClassModel();
		$classes = $classModel->getAllClasses();

		$className = str_replace(" ", "", ucwords(str_replace("_", " ", $classId)));
		call_user_func_array(array($this, '_prepare' . $className . 'FetchOptions'), array(&$fetchOptions, &$selectFields, &$joinTables));

		$this->_joinClasses[$classId] = $classes[$classId];

		preg_match_all("#(LEFT |INNER )?JOIN\s(\w*)\s*(?:AS\s*(\w*)\s*)?ON\s*\((.*)\)#", $joinTables, $matches);
		if (!empty($matches[0]))
		{
			foreach ($matches[0] as $id => $match)
			{
				foreach ($classes as $classId => $class)
				{
					if ('xf_'.$classId == $matches[2][$id])
					{
						$this->_joinClasses[$classId] = $class;
						$matches[2][$id] = 'xf_object';
						if (!$matches[3][$id]) $matches[3][$id] = 'xf_'.$classId;
//						$preparedFetchOptions['joinClasses'][$matches[3][$id]] = $class;

						preg_match("#((?:\w*\.)?(?:\w*))\s*=\s*((?:\w*\.)?(?:\w*))#", $matches[4][$id], $onMatches);
						if (!empty($onMatches))
						{
							$onMatches[1] = $this->_convertFieldName($onMatches[1]);
							$onMatches[2] = $this->_convertFieldName($onMatches[2]);
						}
						$matches[4][$id] = $onMatches[1] . ' = ' . $onMatches[2];
					}
				}
				$matches[0][$id] = $matches[1][$id].' JOIN '.$matches[2][$id].' AS '.$matches[3][$id].' ON ('.$matches[4][$id].')';
			}
		}

		$joinTables = implode("\n", $matches[0]);

		$selectFields = explode(",", $selectFields);
		foreach ($selectFields as &$selectField)
		{
			if ($selectField)
			{
				preg_match("#\s*((?:\w*\.)?(\w*))\s*(?:AS\s*(\w*)\s*)?#", $selectField, $matches);
				if (!isset($matches[3]) || !$matches[3]) $matches[3] = $matches[2];
				$matches[1] = $this->_convertFieldName($matches[1]);
				$selectField = $matches[1]." AS ".$matches[3];
			}
		}
		unset($selectField);
		$selectFields = implode(",", $selectFields);

		return array(
				'selectFields' => $selectFields,
				'joinTables'   => $joinTables,
				'orderClause'  => ($orderBy ? "ORDER BY $orderBy" : '')
		);
	}

	protected function _convertFieldName($fieldName)
	{
		preg_match("#(?:(\w*)\.)?(\w*)#", $fieldName, $matches);
		if (!empty($matches))
		{
			if ($matches[1] && array_key_exists($matches[1], $this->_joinClasses))
			{
				if ($matches[2] == $matches[1] . '_id')
				{
					$fieldName = $matches[1] . '.object_id';
				}
				else
				{
					$keys = $this->_joinClasses[$matches[1]]['keys'];
					if ($keys)
					{
						$keys = unserialize($keys);
						if (in_array($matches[2], $keys))
						{
							$fieldName = $matches[1].'.key_'.array_search($matches[2], $keys);
						}
					}
				}
			}
		}
		return $fieldName;
	}

	public function prepareObject($object)
	{
		$classModel = $this->_getClassModel();
		$classes = $classModel->getAllClasses();

		$object[$object['class_id'].'_id'] = $object['object_id'];

		if (isset($object['class_id']) && isset($classes[$object['class_id']]))
		{
			$class = $classes[$object['class_id']];

			$customFields = $class['custom_fields'];
			if ($customFields)
			{
				$customFields = unserialize($customFields);
			}
			if (is_array($customFields))
			{
				foreach ($customFields as $customFieldId => $customField)
				{
					if (!isset($object[$customFieldId]))
					{
						$object[$customFieldId] = $customField;
					}
				}
			}

			$customFields = $object['custom_fields'];
			if ($customFields)
			{
				$customFields = unserialize($customFields);
			}
			if (is_array($customFields))
			{
				foreach ($customFields as $customFieldId => $customField)
				{
					$object[$customFieldId] = $customField;
				}
			}
			unset($object['custom_fields']);
			unset($object['field_id']);
			unset($object['field_value']);
		}
		return $object;
	}

	public function buildObjectTitle(array $object, $type="title")
	{
		if (!isset($object['title'])) $object['title'] = '';
		$classes = $this->_getClassModel()->getAllClasses();
		if (empty($object)) throw new Exception('SHIT!!');
		if (isset($classes[$object['class_id']]))
		{
			if ($classes[$object['class_id']][$type.'_field'])
			{
				$titleField = $classes[$object['class_id']][$type.'_field'];
				$customFields = array();
				if (isset($object['custom_fields']) && $object['custom_fields'])
				{
					$customFields = unserialize($object['custom_fields']);
				}

				if (isset($customFields[$titleField]))
				{
					$objectFieldModel = XenForo_Model::create('ThemeHouse_CustomFields_Model_ObjectField');
					$objectFields = $objectFieldModel->getAllObjectFields();

					if (isset($objectFields[$titleField]))
					{
						$objectField = $objectFields[$titleField];

						if ($objectField['field_choices_class_id'])
						{
							$object = $this->getObjectById($customFields[$titleField]);
							if ($object)
							{
								return $this->buildObjectTitle($object);
							}
							else
							{
//								throw new Exception('test');
							}
						}
					}
					return $customFields[$titleField];
				}
			}
		}
		return $object['title'];
	}

	/**
	 * @return ThemeHouse_Objects_Model_Class
	 */
	protected function _getClassModel()
	{
		return $this->getModelFromCache('ThemeHouse_Objects_Model_Class');
	}
}