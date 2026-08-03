<?php

class Wheeliamss extends PerchAPI_Factory
{
    protected $table     = 'wheeliams';
	protected $pk        = 'wheeliamsID';
	protected $singular_classname = 'Wheeliams';
	
	protected $default_sort_column = 'wheeliamsID';
	
	public $static_fields   = array();	
	
}