<?php

// This example shows you how to simply forward all requests to another target 
// URI. This requires CURL access in PHP. This may be the better solution if 
// you simply want to forward your requests. One disadvantage is that nothing 
// will be cached.

// Load the webservice class and run the forwarder service
require_once('class.phpwsdlajax.php');// This PHP script will load the PhpWsdl framework, if it's not loaded already
PhpWsdlAjax::RunForwarder('http://www.webservicex.net/geoipservice.asmx');// The parameter is the endpoint URI of the target SOAP webservice
