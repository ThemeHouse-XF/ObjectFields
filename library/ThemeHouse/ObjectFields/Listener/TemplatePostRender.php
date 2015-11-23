<?php

class ThemeHouse_ObjectFields_Listener_TemplatePostRender extends ThemeHouse_Listener_TemplatePostRender
{
	public function run() {
		switch ($this->_templateName)
		{
			case 'object_field_edit':
				$this->_fieldEdit();
				break;
			case 'tools_rebuild':
				$this->_toolsRebuild();
				break;
		}
		return parent::run();
	}

	public static function templatePostRender($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		$templatePostRender = new ThemeHouse_ObjectFields_Listener_TemplatePostRender($templateName, $content, $containerData, $template);
		list($content, $containerData) = $templatePostRender->run();
	}

	protected function _fieldEdit()
	{
	    $pattern = '#<li>\s*<label for="ctrl_field_type_callback">\s*<input type="radio" name="field_type" value="callback" id="ctrl_field_type_callback"[^>]*>[^<]*</label>\s*</li>#Us';
	    $replacement = $this->_render('th_field_edit_php_callback_customfields');
	    $this->_contents = preg_replace($pattern, $replacement, $this->_contents);

	    $viewParams = $this->_fetchViewParams();
	    $pattern = '#<ul class="FieldChoices">.*</ul>\s*<input[^>]*>\s*<p class="explain">[^<]*</p>#Us';
	    preg_match($pattern, $this->_contents, $matches);
	    if (isset($matches[0])) {
	        $viewParams['contents'] = $matches[0];
	        $replacement = $this->_render('th_field_edit_choice_customfields', $viewParams);
	        $this->_contents = preg_replace($pattern, $replacement, $this->_contents);
	    }

	    $pattern = '#</li>\s*</ul>\s*<dl class="ctrlUnit submitUnit">#';
	    $replacement = $this->_render('th_field_edit_panes_customfields') . '${0}';
	    $this->_contents = preg_replace($pattern, $replacement, $this->_contents);

	    $pattern = '#<dl class="ctrlUnit">\s*<dt><label for="ctrl_display_template">.*</dl>#Us';
	    $replacement = $this->_render('th_field_edit_value_display_customfields');
	    $this->_contents = preg_replace($pattern, $replacement, $this->_contents);
	}

	protected function _toolsRebuild()
	{
		$this->_appendTemplate('th_tools_rebuild_customfields');
	}
}