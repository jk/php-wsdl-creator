<?php

// This is an example specially for the REST server

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

// Run the PhpWsdl server in quick mode
/*PhpWsdl::$Debugging=true;
PhpWsdl::$DebugFile=PhpWsdl::$CacheFolder.'/debug.log';*/
PhpWsdl::RunQuickMode('class.restdemo.php');
