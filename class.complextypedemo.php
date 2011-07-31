<?php

if(basename($_SERVER['SCRIPT_FILENAME'])==basename(__FILE__))
	exit;

// The @pw_element and @pw_complex are non-standard keywords for documentaion 
// I had to define to support those complex types for WSDL generation. The  
// "pw" stands for "PhpWsdl". But who cares?

/**
 * This is how to define a complex type f.e.
 *
 * @pw_element string $StringA A string with a value
 * @pw_element string $StringB A string with a NULL value
 * @pw_set nillable=false Not NULL
 * @pw_element int $Integer An integer
 * @pw_set nillable=false Not NULL
 * @pw_element boolean $Boolean A boolean
 * @pw_complex ComplexTypeDemo The complex type name definition
 */
class ComplexTypeDemo{
	public $StringA='String A';
	public $StringB=null;
	public $Integer=123;
	public $Boolean=true;
}

// You can also create array types as complex type. Here for the string type and the ComplexTypeDemo complex type.
// As you can see you simply need to add "Array" to the name of the type. Not one line of code.

/**
 * @pw_complex stringArray A string array type
 */
/**
 * @pw_complex ComplexTypeDemoArray An array of ComplexTypeDemo
 */
