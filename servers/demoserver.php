<?php

// This demonstrates the usage of the servers extension. It requires the 
// PhpWsdl framework files to be in the same folder as this file.

// Load PhpWsdl
require_once('class.phpwsdl.php');

// Load the servers extension, if PhpWsdl could not do it 
// (because the "glob" function may be disabled in your PHP installation)
// If "glob" is working, you don't need those lines:
if(!class_exists('PhpWsdlServers')) 
	require_once('class.phpwsdl.servers.php');
if(!class_exists('PhpWsdlJavaScriptPacker')) 
	require_once('class.phpwsdl.servers-jspacker.php');

// This disables response compression (some servers don't support that)
PhpWsdlServers::$EnableCompression=false;

// This Would disable some servers
//PhpWsdlServers::$EnableHttp=false;// Disable the http webservice
//PhpWsdlServers::$EnableJson=false;// Disable the JSON webservice
//PhpWsdlServers::$EnableRest=false;// Disable the REST webservice
//PhpWsdlServers::$EnableRpc=false;// Disable the XML RPC webservice

// This would enable the client cache
//PhpWsdlServers::$DisableClientCache=false;

// This would enable named parameters for a XML RPC client
//PhpWsdlServers::$EnableRpcNamedParameters=true;

// This would enable attaching the JavaScript clients to the PDF documentation
//PhpWsdlServers::$AttachJsInPdf=true;

// Run the PhpWsdl server in quick mode
/*PhpWsdl::$Debugging=true;
PhpWsdl::$DebugFile=PhpWsdl::$CacheFolder.'/debug.log';*/
PhpWsdl::RunQuickMode(
	Array(								// All files with WSDL definitions in comments
		'class.soapdemo.php',
		'class.complextypedemo.php'
	)
);

// This extension will determine which type of server should run. Currently 
// supported are: JSON, http, XML RPC and REST. See the democlient-*.php for 
// examples how to do the different request types.
