<?php

class ThemeHouse_ObjectFields_Listener_InitDependencies extends ThemeHouse_Listener_InitDependencies
{
    public function run()
    {
    	$allowObjects = false;
    	if (isset(self::$_data['codeEventListeners']['load_class_model']))
    	{
    		foreach (self::$_data['codeEventListeners']['load_class_model'] as $codeEventListener)
    		{
    			if ($codeEventListener[0] == "ThemeHouse_Objects_Listener_LoadClassModel")
	    		{
	    			$allowObjects = true;
	    			break;
	    		}
    		}
    	}

    	if ($allowObjects)
    	{
	    	/* @var $classModel ThemeHouse_Objects_Model_Class */
	    	$classModel = XenForo_Model::create('ThemeHouse_Objects_Model_Class');

	    	$classes = $classModel->getAllClasses();

	    	if (self::$_dependencies instanceof XenForo_Dependencies_Public)
	    	{
	    		$routesPublic = self::$_data['routesPublic'];
	    	}

	    	foreach ($classes as $classId => $class)
	    	{
	    		$className = str_replace(" ", "", ucwords(str_replace("_", " ", $classId)));
	    		// TODO
	   			XenForo_Model::create('ThemeHouse_Objects_Model_Object');
	    		if (file_exists(XenForo_Autoloader::getInstance()->autoloaderClassToFile($class['addon_id'].'_Model_' . $className))) {
	    			eval('class XFCP_'.$class['addon_id'].'_Model_' . $className . ' extends ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_Model_Object {}');
	  			} else {
	   				eval('class '.$class['addon_id'].'_Model_' . $className . ' extends ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_Model_Object {}');
	   			}

		   		XenForo_DataWriter::create('ThemeHouse_Objects_DataWriter_Object');
		   		if (file_exists(XenForo_Autoloader::getInstance()->autoloaderClassToFile($class['addon_id'].'_DataWriter_' . $className))) {
	    			eval('class XFCP_ThemeHouse_'.$class['addon_id'].'_DataWriter_' . $className . ' extends ThemeHouse_ObjectFields_DataWriter_Object
{
    protected $_objectCustomFields = array();

    public function __construct($errorHandler = self::ERROR_ARRAY, array $inject = null)
	{
    	parent::__construct($errorHandler, $inject);

    	$this->setExtraData(\'forceSet\', true);
    	$this->set(\'class_id\', \''. $classId .'\');
    	$this->setExtraData(\'forceSet\', false);
    }

    protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields[\'xf_object\'][\'class_id\'] = array(\'type\' => self::TYPE_STRING, \'default\' => \''. $classId .'\');

		return $fields;
	}

    public function get($field, $tableName = "")
   	{
    	if ($field != "custom_fields")
    	{
    		$customFields = $this->get("custom_fields");
    		if ($customFields)
    		{
    			$customFields = unserialize($customFields);

		    	$fields = $this->_getObjectFieldDefinitions();
    			if (isset($fields[$field]))
    			{
    				if (isset($this->_objectCustomFields[$field]))
    				{
    					return $this->_objectCustomFields[$field];
    				}
		   			else if (isset($customFields[$field]))
	    			{
    					return $customFields[$field];
					}
    			}
    		}
    	}
    	return parent::get($field, $tableName);
	}

    public function set($field, $value, $tableName = \'\', array $options = null)
   	{
	   	$fields = $this->_getObjectFieldDefinitions();
	   	if (isset($fields[$field]) && !$this->getExtraData(\'forceSet\'))
   		{
    		$this->_objectCustomFields[$field] = $value;
       		return true;
		}
    	return parent::set($field, $value, $tableName, $options);
	}

    public function preSave()
    {
    	$this->setCustomFields($this->_objectCustomFields);
    	parent::preSave();
   	}
}');
	    		} else {
	    			eval('class ThemeHouse_'.$class['addon_id'].'_DataWriter_' . $className . ' extends ThemeHouse_ObjectFields_Extend_ThemeHouse_Objects_DataWriter_Object
{
    protected $_objectCustomFields = array();

    public function __construct($errorHandler = self::ERROR_ARRAY, array $inject = null)
	{
    	parent::__construct($errorHandler, $inject);

    	$this->setExtraData(\'forceSet\', true);
    	$this->set(\'class_id\', \''. $classId .'\');
    	$this->setExtraData(\'forceSet\', false);
    }

    protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields[\'xf_object\'][\'class_id\'] = array(\'type\' => self::TYPE_STRING, \'default\' => \''. $classId .'\');

		return $fields;
	}

    public function get($field, $tableName = "")
   	{
    	if ($field != "custom_fields")
    	{
    		$customFields = $this->get("custom_fields");
    		if ($customFields)
    		{
    			$customFields = unserialize($customFields);

		    	$fields = $this->_getObjectFieldDefinitions();
    			if (isset($fields[$field]))
    			{
    				if (isset($this->_objectCustomFields[$field]))
    				{
    					return $this->_objectCustomFields[$field];
    				}
		   			else if (isset($customFields[$field]))
	    			{
    					return $customFields[$field];
					}
    			}
    		}
    	}
    	return parent::get($field, $tableName);
	}

    public function set($field, $value, $tableName = \'\', array $options = null)
   	{
	   	$fields = $this->_getObjectFieldDefinitions();
	   	if (isset($fields[$field]) && !$this->getExtraData(\'forceSet\'))
   		{
    		$this->_objectCustomFields[$field] = $value;
       		return true;
		}
    	return parent::set($field, $value, $tableName, $options);
	}

    public function preSave()
    {
    	$this->setCustomFields($this->_objectCustomFields);
    	parent::preSave();
    }
}');
	    		}

				$routePrefix = str_replace("_", "-", $classId) . 's';
	       		if (isset($routesPublic) && !isset($routesPublic[$routePrefix])) {
					$routesPublic[$routePrefix] = array(
							'build_link'  => 'all',
							'route_class' => 'ThemeHouse_Objects_Route_Prefix_Objects'
					);
				}
	    	}

	    	if (isset($routesPublic)) {
	    		XenForo_Link::setHandlerInfoForGroup('public', $routesPublic);
	    	}
    	}

    	$cacheRebuilders = array(
    	    'CustomFields' => 'ThemeHouse_ObjectFields_CacheRebuilder_CustomFields',
    	);
    	$this->addCacheRebuilders($cacheRebuilders);
    }

    public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        $initDependencies = new ThemeHouse_ObjectFields_Listener_InitDependencies($dependencies, $data);
        $initDependencies->run();
    }
}