<?php

// This example shows you how you can create a proxy SOAP webservice to use foreign locations 
// of SOAP webservies from a AJAX SOAP client. It requires the PhpWsdl framework files to be 
// in the same folder as this file is.
//
// To avoid an open proxy, this demonstration restricts requests only to the server running 
// this demo.
//
// I recommend to enable the PhpWsdl cache when using this proxy.

// Load PhpWsdl (uncomment these two lines of code to enable the PhpWsdl cache in advance)
//require_once('class.phpwsdl.php');
//PhpWsdl::$CacheFolder='./cache';// Set this to a writeable location

// Load the webservice class
require_once('class.phpwsdlajax.php');

// Restrict access to the server that is hosting this demonstration
PhpWsdlAjax::$Restrict[]=((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'];

// Load PhpWsdl (if not loaded yet) and run the SOAP proxy webservice
PhpWsdlAjax::Run();
