<?php

// This demonstrates how I get around a really nasty problem: If the 
// SOAP client won't send a parameter when the value is NULL, the 
// SoapServer will fail with "Missing parameter" when giving the WSDL 
// in the first parameter of the SoapServer constructor. If you give 
// NULL as first parameter to the SoapServer constructor, your method 
// will be called with a wrong parameter order.
// 
// With the PhpWsdlProxy class I try to get around the problem, but 
// complex type return values must be returned with PHPs SoapVar 
// object then. Primitive return types like string, int or boolean don't 
// need a special handling.

// Include the demonstration classes
require_once('class.soapdemo.php');
require_once('class.complextypedemo.php');

// Initialize the PhpWsdl class
require_once('class.phpwsdl.php');
$soap=new PhpWsdl(
	null,								// This demo uses the default namespace http://tempuri.org/
	null,								// Change this to your SOAP endpoint URI (or keep it NULL and PhpWsdl will determine it)
	'./cache/',							// Change this to a folder with write access
	Array(								// All files with WSDL definitions in comments
		'class.soapdemo.php',
		'class.complextypedemo.php'
	),
	null,								// The name of the class that serves the webservice will be determined by PhpWsdl
	null,								// This demo contains all method definitions in comments
	null,								// This demo contains all complex types in comments
	false,								// Don't send WSDL right now
	false);								// Don't start the SOAP server right now

// Disable caching for demonstration
ini_set('soap.wsdl_cache_enabled',0);	// Disable caching in PHP
$soap->CacheTime=0;						// Disable caching in PhpWsdl

// Run the SOAP server
if($soap->IsWsdlRequested())
	$soap->Optimize=false;				// Don't optimize WSDL to send it human readable to the browser
$soap->RunServer(						// Finally, run the server and enable the proxy
	null,
	Array(								// Use an array for this parameter to enable the proxy
		'SoapDemo',						// The name of the target class that will handle SOAP requests
		new SoapDemo()					// An instance of the target class that will handle SOAP requests
	)
);
