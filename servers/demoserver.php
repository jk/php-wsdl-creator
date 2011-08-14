<?php

// This demonstrates the usage of the servers extension. It requires the 
// PhpWsdl framework files to be in the same folder as this file.

// Load PhpWsdl
require_once('class.phpwsdl.php');
PhpWsdl::$HTML2PDFLicenseKey='4f5f1f6537afea2a10f5c4889b8397959e69e634';

// Load the servers extension, if PhpWsdl could not do it 
// (because the "glob" function may be disabled in your PHP installation)
// If "glob" is working, you don't need the following two lines:
if(!class_exists('PhpWsdlServers')) 
	require_once('class.phpwsdl.servers.php');

// This would enable the client cache
//PhpWsdlServers::$DisableClientCache=false;

// Run the PhpWsdl server in quick mode
/*PhpWsdl::$Debugging=true;
PhpWsdl::$DebugFile=PhpWsdl::$CacheFolder.'/debug.log';*/
$soap=PhpWsdl::RunQuickMode(
	Array(								// All files with WSDL definitions in comments
		'class.soapdemo.php',
		'class.complextypedemo.php'
	)
);

// This extension will determine which type of server should run. Currently 
// supported are: JSON, http and REST. See the democlient-*.php for examples 
// how to do the different request types.
