<?php

class ThemeHouse_ObjectFields_Install_Controller extends ThemeHouse_Install
{
	protected function _getTables()
	{
		return array(
    		'xf_object_field' => array(
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
    		),
			'xf_object_field_group' => array(
				'field_group_id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
				'display_order' => 'INT(10) UNSIGNED NOT NULL',
			),
			'xf_object_class_field' => array(
				'object_class_id' => 'VARCHAR(25) NOT NULL',
				'field_id' => 'VARCHAR(64) NOT NULL',
				'field_value' => 'MEDIUMTEXT NOT NULL',
			),
			'xf_object_field_value' => array(
				'object_id' => 'INT(10) UNSIGNED NOT NULL',
				'field_id' => 'VARCHAR(64) NOT NULL',
				'field_value' => 'MEDIUMTEXT NOT NULL',
			),
		);
	}
	
	protected function _getTableChanges()
	{
	    return array(
			'xf_object_class' => array(
				'field_cache' => 'MEDIUMBLOB NULL COMMENT \'Serialized data from xf_class_field, [group_id][field_id] => field_id\'',
				'custom_fields' => 'MEDIUMBLOB NULL',
				'required_fields' => 'MEDIUMBLOB NULL',
				'title_field' => 'VARCHAR(64) NOT NULL DEFAULT \'\'',
				'subtitle_field' => 'VARCHAR(64) NOT NULL DEFAULT \'\'',
				'keys' => 'MEDIUMBLOB NULL',
				'unique_keys' => 'MEDIUMBLOB NULL',
				'primary_key' => 'VARCHAR(64) NOT NULL DEFAULT \'\'',
			),
			'xf_object' => array(
				'custom_fields' => 'MEDIUMBLOB NULL',
			),
		);
	}
	
	protected function _getPrimaryKeys()
	{
		return array(
			'xf_object_field' => array('field_id', 'addon_id'),
			'xf_object_field_value' => array('object_id', 'field_id'),
			'xf_object_class_field' => array('object_class_id', 'field_id'),
		);
	}

	protected function _getKeys()
	{
        return array(
			'xf_object_field_value' => array(
				'field_id' => array('field_id')
			),
		);
	}
}