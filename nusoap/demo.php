<?php

// This demonstrates the usage of the NuSOAP adapter. It requires the 
// PhpWsdl framework files to be in the same folder as this file.

// Load NoSOAP
require_once('nusoap.php');// Change this to the location of your NuSOAP installation

// Load PhpWsdl
require_once('class.phpwsdl.php');

// Load the NuSOAP extension, if PhpWsdl could not do it 
// (because the "glob" function may be disabled in your PHP installation)
// If "glob" is working, you don't need the following two lines:
if(!class_exists('PhpWsdlNuSOAP')) 
	require_once('class.phpwsdl.nusoap.php');

// Run the SOAP server in quick mode
$soap=PhpWsdl::CreateInstance(
	true,								// PhpWsdl will determine a good namespace
	Array(								// All files with WSDL definitions in comments
		'class.soapdemo.php',
		'class.complextypedemo.php'
	),
	'./cache'							// Change this to a folder with write access
);

// I was able to use this webservice with SoapUI. But with Visual Studio 2010 
// I didn't receive the response. I think the problem may be the dot in the 
// response XML tag names that are produced by NuSOAP when registering a 
// method of a class. But without the dot NuSOAP won't find the method. There 
// is no way to change the class->method delimiter in NuSOAP (or you need to 
// touch their code). So I'm sorry, but this may not work with .NET clients...
//
// By the way: NuSOAP has a horrible UTF-8 support.
