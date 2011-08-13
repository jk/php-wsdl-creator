<?php

// This example shows you how you can create a proxy SOAP webservice to use foreign locations 
// of SOAP webservies from an AJAX SOAP client. It requires the PhpWsdl framework files to be 
// in the same folder as this file is.
//
// I strongly recommend to enable the PhpWsdl cache when using this AJAX proxy!

// Load the webservice class and run the service
require_once('class.phpwsdlajax.php');// This PHP script will load the PhpWsdl framework, if it's not loaded already
PhpWsdlAjax::RunProxy('http://www.webservicex.net/geoipservice.asmx?WSDL');// The parameter is the WSDL URI of the target SOAP webservice

// Note: The proxy can only handle simple SOAP webservices that only use the basic method and 
// complex types PhpWsdl supports!
