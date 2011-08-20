<?php

// This demonstrates the usage of the XML RPC server. It requires the 
// PhpWsdl framework files to be in the same folder as this file.

// This is the URI to the http server
$endPoint=((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']).'/demoserver.php';

echo '<pre>';

// Call the method SayHello
$server=stream_context_create(Array(
	"http"			=>	Array(
		"method"			=>	"POST",
		"header"			=>	"Content-Type: text/xml",
		"content"			=>	xmlrpc_encode_request('SayHello',Array('you'))
	)
));
// Use named parameters - please note: The server has to support named 
// parameters. With the PhpWsdlServers extension you can do this by setting 
// the static PhpWsdlServers::$EnbableRpcNamedParameters to TRUE at the server 
// side
/*$server=stream_context_create(Array(
	"http"			=>	Array(
		"method"			=>	"POST",
		"header"			=>	"Content-Type: text/xml",
		"content"			=>	xmlrpc_encode_request(
			'SayHello',
			Array(
				'name'			=>	'you'
			)
		)
	)
));*/
$res=xmlrpc_decode(file_get_contents($endPoint,false,$server));
if(is_array($res)&&xmlrpc_is_fault($res))
	throw(new Exception($res["faultString"],$res["faultCode"]));
echo "SayHello:\n".htmlentities($res)."\n";

// Call the method GetComplexType
$server=stream_context_create(Array(
	"http"			=>	Array(
		"method"			=>	"POST",
		"header"			=>	"Content-Type: text/xml",
		"content"			=>	xmlrpc_encode_request('GetComplexType',Array())
	)
));
$res=xmlrpc_decode(file_get_contents($endPoint,false,$server));
if(is_array($res)&&xmlrpc_is_fault($res))
	throw(new Exception($res["faultString"],$res["faultCode"]));
echo "\nGetComplexType:\n".htmlentities(print_r($res,true))."\n";

// Call the method PrintComplexType
$server=stream_context_create(Array(
	"http"			=>	Array(
		"method"			=>	"POST",
		"header"			=>	"Content-Type: text/xml",
		"content"			=>	xmlrpc_encode_request('PrintComplexType',Array($res))
	)
));
$res=xmlrpc_decode(file_get_contents($endPoint,false,$server));
if(is_array($res)&&xmlrpc_is_fault($res))
	throw(new Exception($res["faultString"],$res["faultCode"]));
echo "\nPrintComplexType:\n".htmlentities($res)."\n";

echo '</pre>';
exit;
