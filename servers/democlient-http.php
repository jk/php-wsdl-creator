<?php

// This demonstrates the usage of the http server. It requires the 
// PhpWsdl framework files to be in the same folder as this file.
//
// Per default parameter types that are not contained in the 
// PhpWsdl::$BasicTypes array needs JSON encoding. The same is for the return 
// value: If its type isn't declared as basic type, it'll be JSON encoded.
// To implement a different handling, use the HttpParametersHook and the 
// HttpResponseHook (see class.phpwsdl.servers.php -> HandleHttpRequest) and 
// return FALSE in your handler methods to prevent the default handling.

// This is the URI to the http server
$endPoint=((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']).'/demoserver.php';

// This will call the method "SayHello"
$param=Array();
$param['call']='SayHello';	// The method name
$param['name']='you';		// The parameter "name"

// Encode the parameters for a http request
$temp=Array();
$i=-1;
$keys=array_keys($param);
$len=sizeof($keys);
while(++$i<$len)
	$temp[]=urlencode($keys[$i]).'='.urlencode($param[$keys[$i]]);
$param=implode('&',$temp);

// Call the webservice
$res=file_get_contents($endPoint.'?'.$param);

// Display the result and quit
echo htmlentities($res);
exit;
