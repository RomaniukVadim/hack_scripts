<?php
/****
 * vB Optimise
 * Copyright 2010; Deceptor
 * All Rights Reserved
 * Code may not be copied, in whole or part without written permission
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

class vB_Template
{
	public static function create($template = '')
	{
		return new vB_Template_Object($template);
	}
}

class vB_Template_Object extends vB_Template
{
	protected $template = '';
	protected $registered = array();

	public function __construct($template = '')
	{
		$this->template = $template;
	}

	public function register($var = '', $value)
	{
		$this->registered[$var] = $value;
	}
	
	public function quickRegister($array)
	{
		foreach ($array as $var => $value)
		{
			$this->register($var, $value);
		}
	}

	public function is_registered($var)
	{
		return isset($this->registered[$var]);
	}

	public function unregister($var)
	{
		unset($this->registered[$var]);
	}
	
	public function register_page_templates()
	{
		$this->register('footer', $GLOBALS['footer']);
		$this->register('header', $GLOBALS['header']);
		$this->register('headinclude', $GLOBALS['headinclude']);
		$this->register('headinclude_bottom', $GLOBALS['headinclude_bottom']);
	}	

	public function render()
	{
		global $instance, $stylevar, $vbphrase, $session, $template_hook;
		global $bbuserinfo, $vboptions, $vbulletin, $css, $show, $cells;
		
		if (sizeof($this->registered))
		{
			foreach ($this->registered as $var => $value)
			{
				$$var = $value;
			}
		}
		
		eval('$template = "' . fetch_template($this->template) . '";');

		return $template;
	}
}