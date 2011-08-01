<?php

// This example shows you how you can create a proxy SOAP webservice to use foreign locations 
// of SOAP webservies from a AJAX SOAP client. It requires the PhpWsdl framework files to be 
// in the same folder as this file is.
//
// To avoid an open proxy, this demonstration restricts requests only to the server running 
// this demo.

// Load the webservice class
require_once('class.phpwsdlajax.php');

// Restrict access to this SOAP webservice location
PhpWsdlAjax::$Restrict[]=((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'];

// Load PhpWsdl and run the SOAP proxy webservice
PhpWsdlAjax::Run();
