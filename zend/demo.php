<?php

// This demonstrates the usage of the Zend adapter. It requires the 
// PhpWsdl framework files to be in the same folder as this file.

// Load PhpWsdl
require_once('class.phpwsdl.php');

// Load the Zend extension, if PhpWsdl could not do it 
// (because the "glob" function may be disabled in your PHP installation)
// If "glob" is working, you don't need the following two lines:
if(!class_exists('PhpWsdlZend')) 
	require_once('class.phpwsdl.zend.php');

// Run the SOAP server in quick mode
PhpWsdl::CreateInstance(
	true,
	Array(
		'class.soapdemo.php',
		'class.complextypedemo.php'
	)
);
