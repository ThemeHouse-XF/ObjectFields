<?php

class ThemeHouse_ObjectFields_InstallerThemeHouse_Objects extends XFCP_ThemeHouse_ObjectFields_InstallerThemeHouse_Objects
{
	protected function _getTables()
	{
		$tables = parent::_getTables();
		$tables['xf_object_class'] = array_merge($tables['xf_object_class'], array(
			'field_cache' => 'MEDIUMBLOB NULL COMMENT \'Serialized data from xf_object_class_field, [group_id][field_id] => field_id\'',
			'custom_fields' => 'MEDIUMBLOB NULL',
			'required_fields' => 'MEDIUMBLOB NULL',
			'title_field' => 'VARCHAR(64) NOT NULL DEFAULT \'\'',
			'subtitle_field' => 'VARCHAR(64) NOT NULL DEFAULT \'\'',
			'keys' => 'MEDIUMBLOB NULL',
			'unique_keys' => 'MEDIUMBLOB NULL',
			'primary_key' => 'VARCHAR(64) NOT NULL DEFAULT \'\'',
		));
		$tables['xf_object'] = array_merge($tables['xf_object'], array(
			'custom_fields' => 'MEDIUMBLOB NULL',
		));
		$tables['xf_object_field'] = array(
			'field_id' => 'VARCHAR(64) NOT NULL',
			'field_group_id' => 'INT(10) UNSIGNED NOT NULL',
			'display_order' => 'INT(10) UNSIGNED NOT NULL DEFAULT \'0\'',
			'materialized_order' => 'INT(10) UNSIGNED NOT NULL DEFAULT \'0\'',
			'field_type' => 'ENUM(\'textbox\',\'textarea\',\'select\',\'radio\',\'checkbox\',\'multiselect\',\'callback\') NOT NULL DEFAULT \'textbox\'',
			'field_choices' => 'BLOB NOT NULL',
			'match_type' => 'ENUM(\'none\',\'number\',\'alphanumeric\',\'email\',\'url\',\'regex\',\'callback\') NOT NULL DEFAULT \'none\'',
			'match_regex' => 'VARCHAR(250) NOT NULL DEFAULT \'\'',
			'match_callback_class' => 'VARCHAR(75) NOT NULL DEFAULT \'\'',
			'match_callback_method' => 'VARCHAR(75) NOT NULL DEFAULT  \'\'',
			'max_length' => 'INT(10) UNSIGNED NOT NULL DEFAULT \'0\'',
			'allowed_user_group_ids' => 'BLOB NOT NULL',
			'addon_id' => 'VARCHAR(25) NOT NULL DEFAULT \'\'',
			'field_choices_callback_class' => 'VARCHAR(75) NOT NULL DEFAULT \'\'',
			'field_choices_callback_method' => 'VARCHAR(75) NOT NULL DEFAULT \'\'',
			'field_callback_class' => 'VARCHAR(75) NOT NULL DEFAULT \'\'',
			'field_callback_method' => 'VARCHAR(75) NOT NULL DEFAULT  \'\'',
			'pre_save_callback_class' => 'VARCHAR(75) NOT NULL DEFAULT \'\'',
			'pre_save_callback_method' => 'VARCHAR(75) NOT NULL DEFAULT  \'\'',
			'post_save_callback_class' => 'VARCHAR(75) NOT NULL DEFAULT \'\'',
			'post_save_callback_method' => 'VARCHAR(75) NOT NULL DEFAULT  \'\'',
			'export_callback_class' => 'VARCHAR(75) NOT NULL DEFAULT \'\'',
			'export_callback_method' => 'VARCHAR(75) NOT NULL DEFAULT  \'\'',
		);
		$tables['xf_object_field_group'] = array(
			'field_group_id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
			'display_order' => 'INT(10) UNSIGNED NOT NULL',
		);
		$tables['xf_object_field_value'] = array(
			'object_id' => 'INT(10) UNSIGNED NOT NULL',
			'field_id' => 'VARCHAR(64) NOT NULL',
			'field_value' => 'MEDIUMTEXT NOT NULL',
		);
		$tables['xf_object_class_field'] = array(
			'object_class_id' => 'INT(10) UNSIGNED NOT NULL',
			'field_id' => 'VARCHAR(64) NOT NULL',
			'field_value' => 'MEDIUMTEXT NOT NULL',
		);		
		$tables['xf_object_field_value'] = array(
			'object_id' => 'INT(10) UNSIGNED NOT NULL',
			'field_id' => 'VARCHAR(64) NOT NULL',
			'field_value' => 'MEDIUMTEXT NOT NULL',
		);
		return $tables;
	}
	
	protected function _getPrimaryKeys()
	{
		$primaryKeys = parent::_getPrimaryKeys();
		$primaryKeys['xf_object_field'] = array('field_id', 'addon_id');
		$primaryKeys['xf_object_field_value'] = array('object_id', 'field_id');
		$primaryKeys['xf_object_class_field'] = array('object_class_id', 'field_id');
		return $primaryKeys;
	}
	
	protected function _getKeys()
	{
		$keys = parent::_getKeys();
		$keys['xf_object_field_value'] = array('field_id' => array('field_id'));
		return $keys;
	}
}