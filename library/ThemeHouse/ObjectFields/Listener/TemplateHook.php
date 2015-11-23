<?php

class ThemeHouse_ObjectFields_Listener_TemplateHook extends ThemeHouse_Listener_TemplateHook
{
	public function run() {
		switch ($this->_hookName)
		{
			case 'admin_class_edit_tabs':
				$this->_adminClassEditTabs();
				break;
			case 'admin_object_edit_tabs':
				$this->_adminObjectEditTabs();
				break;
			case 'admin_class_edit_panes':
				$this->_adminClassEditPanes();
				break;
			case 'admin_object_edit_panes':
				$this->_adminObjectEditPanes();
				break;
			case 'admin_object_edit_basic_info_pane':
				$this->_adminObjectEditBasicInfoPane();
				break;
			case 'object_edit':
				$this->_objectEdit();
				break;
		}
		return parent::run();
	}
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		$templateHook = new ThemeHouse_ObjectFields_Listener_TemplateHook($hookName, $contents, $hookParams, $template);
		$contents = $templateHook->run();
	}

	protected function _adminClassEditTabs()
	{
		$this->_appendTemplate('th_class_edit_tabs_customfields');
	}

	protected function _adminObjectEditTabs()
	{
		$this->_appendTemplate('th_object_edit_tabs_customfields');
	}

	protected function _adminClassEditPanes()
	{
		$this->_appendTemplate('th_class_edit_panes_customfields');
	
		$viewParams = $this->_fetchViewParams();
		$customFields = $viewParams['customFields'];
		foreach ($customFields as $field)
		{
			if ($field['field_type'] == 'callback')
			{
				$this->_replaceAtCodeSnippet(
						'<input type="hidden" name="custom_fields_shown[]" value="'.$field['field_id'].'" />',
						call_user_func_array(
								array($field['field_callback_class'], $field['field_callback_method']),
								array($this->_template, $field)
						)->render()
				);
			}
		}
	}
	
	protected function _adminObjectEditPanes()
	{
		$this->_appendTemplate('th_object_edit_panes_customfields');
		
		$viewParams = $this->_fetchViewParams();
		$customFields = $viewParams['customFields'];
		foreach ($customFields as $customFieldGroup)
		{
			foreach ($customFieldGroup['fields'] as $field)
			{
				if ($field['field_type'] == 'callback')
				{
					$this->_replaceAtCodeSnippet(
							'<input type="hidden" name="custom_fields_shown[]" value="'.$field['field_id'].'" />',
							call_user_func_array(
									array($field['field_callback_class'], $field['field_callback_method']),
									array($this->_template, $field)
							)->render()
					);
				}
			}
		}
	}

	protected function _adminObjectEditBasicInfoPane()
	{
		$this->_replaceWithTemplate('th_object_basic_info_edit_pane_customfields');
	}

	protected function _objectEdit()
	{
		$this->_replaceWithTemplate('th_object_edit_customfields');
		$viewParams = $this->_fetchViewParams();
		$customFields = $viewParams['customFields'];
		foreach ($customFields as $customFieldGroup)
		{
			$viewParams['customFields'] = $customFieldGroup['fields'];
			$this->_appendTemplate('custom_fields_edit', $viewParams);
			foreach ($customFieldGroup['fields'] as $field)
			{
				if ($field['field_type'] == 'callback')
				{
					$this->_replaceAtCodeSnippet(
							'<input type="hidden" name="custom_fields_shown[]" value="'.$field['field_id'].'" />',
							call_user_func_array(
									array($field['field_callback_class'], $field['field_callback_method']),
									array($this->_template, $field)
							)->render()
					);
				}
			}
		}
	}
}