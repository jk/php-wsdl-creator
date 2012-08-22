<?php

// This demonstrates how to mix up global methods and methods from multiple 
// handler classes without using the PhpWsdlProxy class.

require_once('class.phpwsdl.php');
/*PhpWsdl::$Debugging=true;
PhpWsdl::$DebugFile='./cache/debug.log';*/
PhpWsdlMethod::$DefaultException='SoapFault';// This will set SoapFault as exception type for all methods
ini_set('soap.wsdl_cache_enabled',0);	// Disable caching in PHP
PhpWsdl::$CacheTime=0;					// Disable caching in PhpWsdl
$soap->CreateHandler=true;				// Enable creating a PhpWsdlHandler class at runtime (this does the trick, finally)
$soap=PhpWsdl::CreateInstance();
$soap->Files=Array(						// Define all files with WSDL definitions, classes and methods the webservice requires to run
	'class.soapdemo.php',
	'class.complextypedemo.php',
	__FILE__
);
$soap->RunServer();

/**
 * This is how to define a global method for WSDL.
 * 
 * @return string Response
 * @pw_set global=1 -> Tell PhpWsdl to serve this as global method (outside of a class)
 */
function GlobalMethodDemo(){
	return utf8_encode('Response of the global method demo');
}

class SecondClass{
	/**
	 * This method is in another class
	 * 
	 * @param string $str A string
	 * @return string The input string
	 * @pw_set class=SecondClass Required to tell PhpWsdl the location of this method that is not included in the main webservice handler class "SoapDemo"
	 */
	public function AnotherDemoMethod($str){
		return $str;
	}
}

// Note: If you want to add class methods from another class than your main 
// webservice handler class, you have to specify the methods class name by 
// using the @pw_set keyword. If you want to use an existing instance of the 
// class, you need to set up the PhpWsdlMethod->Class property manually after 
// PhpWsdl parsed your definitions (then setting a class with @pw_set isn't 
// required anymore).
